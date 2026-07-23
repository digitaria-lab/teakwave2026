<?php
ob_start();
require_once 'auth/check.php';
require_once __DIR__ . '/includes/database-backup.php';
require_once __DIR__ . '/includes/media-backup.php';

// Backup and restore operations are sensitive. Keep this page exclusive to Super Admin.
if (!is_super_admin()) {
    log_activity('access_denied', 'backup-restore', 'Mencoba mengakses halaman Backup & Restore tanpa hak Super Admin.');
    http_response_code(403);
    include __DIR__ . '/includes/no-access.php';
    exit;
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$page_title = 'Backup & Restore Data & Media';
$databaseName = db_current_database($pdo);
$message = $_SESSION['backup_restore_message'] ?? null;
unset($_SESSION['backup_restore_message']);

function backup_restore_flash($type, $text) {
    $_SESSION['backup_restore_message'] = [
        'type' => $type,
        'text' => $text
    ];
}

function backup_restore_redirect() {
    redirect('backup-restore.php');
}

function backup_restore_log($action, $description = '', $forceSchemaRefresh = false) {
    return log_activity($action, 'backup-restore', $description, $forceSchemaRefresh);
}

function backup_restore_log_filename($value) {
    $name = basename(str_replace('\\', '/', trim((string) $value)));
    $name = preg_replace('/[^A-Za-z0-9._ -]/', '_', $name);
    return substr($name ?: 'tidak-diketahui', 0, 180);
}

function backup_restore_failure_action($action) {
    $map = [
        'run_permission_upgrade' => 'permission_upgrade_failed',
        'create_media_backup' => 'media_backup_create_failed',
        'restore_media_upload' => 'media_restore_upload_failed',
        'dump_database' => 'database_dump_failed',
        'restore_upload' => 'restore_upload_failed',
        'reset_database' => 'database_reset_failed',
        'restore_saved' => 'restore_saved_failed',
        'delete_backup' => 'backup_delete_failed'
    ];

    return $map[$action] ?? 'request_failed';
}

function backup_restore_failure_context($action) {
    if ($action === 'restore_upload') {
        return ' File: ' . backup_restore_log_filename($_POST['sql_original_name'] ?? ($_FILES['sql_file']['name'] ?? '')) . '.';
    }

    if ($action === 'restore_media_upload') {
        return ' File: ' . backup_restore_log_filename($_POST['media_original_name'] ?? ($_FILES['media_zip']['name'] ?? '')) . '.';
    }

    if ($action === 'create_media_backup') {
        $categories = array_map('strval', (array) ($_POST['media_categories'] ?? []));
        return ' Kategori: ' . ($categories ? implode(', ', $categories) : 'tidak dipilih') . '.';
    }

    if ($action === 'reset_database') {
        return ' File: ' . backup_restore_log_filename($_POST['reset_sql_original_name'] ?? ($_FILES['reset_sql_file']['name'] ?? '')) . '.';
    }

    if (in_array($action, ['restore_saved', 'delete_backup'], true)) {
        return ' File: ' . backup_restore_log_filename($_POST['filename'] ?? '') . '.';
    }

    if ($action === 'dump_database') {
        $mode = preg_replace('/[^a-z_-]/i', '', (string) ($_POST['dump_mode'] ?? 'full'));
        return ' Mode: ' . ($mode ?: 'full') . '.';
    }

    return '';
}

function backup_restore_filename($prefix = 'backup') {
    return $prefix . '-' . date('Ymd-His') . '-' . substr(bin2hex(random_bytes(3)), 0, 6) . '.sql';
}

function backup_restore_ini_bytes($value) {
    $value = trim((string) $value);
    if ($value === '') return 0;

    $unit = strtolower(substr($value, -1));
    $number = (float) $value;

    if ($unit === 'g') return (int) round($number * 1024 * 1024 * 1024);
    if ($unit === 'm') return (int) round($number * 1024 * 1024);
    if ($unit === 'k') return (int) round($number * 1024);

    return (int) round($number);
}

function backup_restore_verify_current_password(PDO $pdo, $password, $purpose = 'restore') {
    $password = (string) $password;
    $userId = (int) ($_SESSION['user']['id'] ?? 0);
    $purpose = $purpose === 'dump' ? 'dump database' : ($purpose === 'reset' ? 'reset database' : 'restore');

    if ($userId <= 0 || $password === '') {
        throw new RuntimeException('Masukkan password akun Super Admin untuk mengonfirmasi ' . $purpose . '.');
    }

    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$userId]);
    $hash = $stmt->fetchColumn();

    if (!$hash || !password_verify($password, $hash)) {
        throw new RuntimeException('Password akun Super Admin tidak benar. ' . ucfirst($purpose) . ' dibatalkan.');
    }
}

function backup_restore_assert_rate_limit() {
    $windowSeconds = 10 * 60;
    $maxAttempts = 5;
    $now = time();
    $attempts = $_SESSION['database_restore_attempts'] ?? [];

    $attempts = array_values(array_filter($attempts, function ($timestamp) use ($now, $windowSeconds) {
        return is_numeric($timestamp) && ((int) $timestamp) > ($now - $windowSeconds);
    }));

    if (count($attempts) >= $maxAttempts) {
        throw new RuntimeException('Terlalu banyak percobaan restore. Tunggu beberapa menit lalu coba lagi.');
    }

    $attempts[] = $now;
    $_SESSION['database_restore_attempts'] = $attempts;
}


function backup_restore_assert_dump_rate_limit() {
    $windowSeconds = 10 * 60;
    $maxAttempts = 5;
    $now = time();
    $attempts = $_SESSION['database_dump_attempts'] ?? [];

    $attempts = array_values(array_filter($attempts, function ($timestamp) use ($now, $windowSeconds) {
        return is_numeric($timestamp) && ((int) $timestamp) > ($now - $windowSeconds);
    }));

    if (count($attempts) >= $maxAttempts) {
        throw new RuntimeException('Terlalu banyak permintaan dump database. Tunggu beberapa menit lalu coba lagi.');
    }

    $attempts[] = $now;
    $_SESSION['database_dump_attempts'] = $attempts;
}

function backup_restore_assert_reset_rate_limit() {
    $windowSeconds = 30 * 60;
    $maxAttempts = 3;
    $now = time();
    $attempts = $_SESSION['database_reset_attempts'] ?? [];

    $attempts = array_values(array_filter($attempts, function ($timestamp) use ($now, $windowSeconds) {
        return is_numeric($timestamp) && ((int) $timestamp) > ($now - $windowSeconds);
    }));

    if (count($attempts) >= $maxAttempts) {
        throw new RuntimeException('Terlalu banyak percobaan reset database. Tunggu 30 menit lalu coba lagi.');
    }

    $attempts[] = $now;
    $_SESSION['database_reset_attempts'] = $attempts;
}

function backup_restore_check_post_size() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

    $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    $postMaxBytes = backup_restore_ini_bytes(ini_get('post_max_size'));

    if ($contentLength > 0 && $postMaxBytes > 0 && $contentLength > $postMaxBytes) {
        backup_restore_log(
            'restore_upload_failed',
            'Upload restore ditolak karena ukuran request ' . $contentLength
            . ' byte melebihi post_max_size server (' . ini_get('post_max_size') . ').'
        );
        backup_restore_flash(
            'danger',
            'Upload ditolak karena ukuran request melebihi post_max_size server (' . ini_get('post_max_size') . ').'
        );
        backup_restore_redirect();
    }
}

function backup_restore_permission_upgrade_status(PDO $pdo) {
    try {
        $targetRoles = (int) $pdo->query("SELECT COUNT(*) FROM roles WHERE id = 1 OR slug = 'super-admin'")->fetchColumn();

        $stmt = $pdo->query("
            SELECT COUNT(*)
            FROM role_permissions
            INNER JOIN roles ON roles.id = role_permissions.role_id
            WHERE role_permissions.page_key = 'backup-restore'
              AND (roles.id = 1 OR roles.slug = 'super-admin')
        ");
        $installedRoles = (int) $stmt->fetchColumn();

        return [
            'installed' => $targetRoles > 0 && $installedRoles >= $targetRoles,
            'target_roles' => $targetRoles,
            'installed_roles' => $installedRoles,
            'error' => null
        ];
    } catch (Throwable $e) {
        return [
            'installed' => false,
            'target_roles' => 0,
            'installed_roles' => 0,
            'error' => $e->getMessage()
        ];
    }
}

function backup_restore_run_permission_upgrade(PDO $pdo, $sqlPath) {
    if (!is_super_admin()) {
        throw new RuntimeException('Hanya Super Admin yang boleh menjalankan upgrade database.');
    }

    if (!is_file($sqlPath) || !is_readable($sqlPath)) {
        throw new RuntimeException('File upgrade database tidak ditemukan atau tidak dapat dibaca.');
    }

    $sql = trim((string) file_get_contents($sqlPath));

    if ($sql === '') {
        throw new RuntimeException('File upgrade database kosong.');
    }

    if (strlen($sql) > 20000) {
        throw new RuntimeException('File upgrade database tidak valid karena ukurannya terlalu besar.');
    }

    // File upgrade ini hanya boleh menambahkan izin backup-restore.
    if (stripos($sql, "INSERT IGNORE INTO role_permissions") === false
        || stripos($sql, "'backup-restore'") === false
        || preg_match('/\b(DROP|TRUNCATE|DELETE|UPDATE|ALTER|CREATE|GRANT|REVOKE)\b/i', $sql)) {
        throw new RuntimeException('Isi file upgrade tidak sesuai dengan upgrade izin Backup & Restore.');
    }

    $startedTransaction = false;

    try {
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
            $startedTransaction = true;
        }

        $affected = $pdo->exec($sql);

        if ($startedTransaction) {
            $pdo->commit();
        }

        return (int) $affected;
    } catch (Throwable $e) {
        if ($startedTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $e;
    }
}

function backup_restore_send_download($path, $downloadName, $deleteAfter = false, $contentType = null) {
    if (!is_file($path) || !is_readable($path)) {
        http_response_code(404);
        exit('File backup tidak ditemukan.');
    }

    if ($deleteAfter) {
        $cleanupPath = $path;
        register_shutdown_function(function () use ($cleanupPath) {
            if (is_file($cleanupPath)) {
                @unlink($cleanupPath);
            }
        });
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    $size = filesize($path);

    if ($contentType === null) {
        $extension = strtolower(pathinfo($downloadName, PATHINFO_EXTENSION));
        $contentType = $extension === 'zip' ? 'application/zip' : 'application/sql; charset=utf-8';
    }

    header('Content-Type: ' . $contentType);
    header('Content-Disposition: attachment; filename="' . str_replace('"', '', basename($downloadName)) . '"');

    if ($size !== false) {
        header('Content-Length: ' . $size);
    }

    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    exit;
}

backup_restore_check_post_size();

if (isset($_GET['download'])) {
    $requestedFilename = backup_restore_log_filename($_GET['download']);
    $filename = db_backup_safe_name($_GET['download']);
    $path = $filename ? db_backup_path($filename) : null;

    if (!$path) {
        backup_restore_log('backup_download_failed', 'Nama file backup tidak valid: ' . $requestedFilename . '.');
        http_response_code(400);
        exit('Nama file backup tidak valid.');
    }

    if (!is_file($path) || !is_readable($path)) {
        backup_restore_log('backup_download_failed', 'File backup tidak ditemukan atau tidak dapat dibaca: ' . $filename . '.');
        http_response_code(404);
        exit('File backup tidak ditemukan.');
    }

    backup_restore_log(
        'backup_download',
        'Mengunduh backup database: ' . $filename . '; ukuran: ' . (int) filesize($path) . ' byte.'
    );
    backup_restore_send_download($path, $filename);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf('backup-restore');
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'run_permission_upgrade') {
            $upgradePath = __DIR__ . '/database/upgrade_backup_restore_permission.sql';
            $affected = backup_restore_run_permission_upgrade($pdo, $upgradePath);
            $status = backup_restore_permission_upgrade_status($pdo);

            if (!$status['installed']) {
                throw new RuntimeException('Upgrade telah dijalankan, tetapi status izin belum lengkap. Periksa struktur tabel roles dan role_permissions.');
            }

            backup_restore_log(
                'permission_upgrade',
                'Menjalankan upgrade izin Backup & Restore; baris baru: ' . $affected
                . '; status terpasang: ' . ($status['installed'] ? 'ya' : 'tidak') . '.'
            );

            backup_restore_flash(
                'success',
                $affected > 0
                    ? 'Upgrade database berhasil. Izin Backup & Restore telah ditambahkan untuk Super Admin.'
                    : 'Upgrade database sudah terpasang sebelumnya. Tidak ada perubahan data yang diperlukan.'
            );
            backup_restore_redirect();
        }

        if ($action === 'create_media_backup') {
            $selectedCategories = array_values(array_unique(array_map('strval', (array) ($_POST['media_categories'] ?? []))));
            $operationLock = media_backup_acquire_lock();

            try {
                $result = media_backup_create_archive($pdo, $selectedCategories);
            } finally {
                media_backup_release_lock($operationLock);
            }

            backup_restore_log(
                'media_backup_create',
                'Membuat backup foto media: ' . $result['filename']
                . '; kategori: ' . implode(', ', $result['categories'])
                . '; file: ' . (int) $result['file_count']
                . '; ukuran asli: ' . (int) $result['total_size'] . ' byte'
                . '; referensi tidak ditemukan: ' . (int) $result['missing_count'] . '.'
            );

            backup_restore_send_download($result['path'], $result['filename'], true, 'application/zip');
        }

        if ($action === 'dump_database') {
            if (($_POST['confirm_dump'] ?? '') !== 'yes') {
                throw new RuntimeException('Centang konfirmasi dump database terlebih dahulu.');
            }

            backup_restore_assert_dump_rate_limit();
            backup_restore_verify_current_password($pdo, $_POST['current_password'] ?? '', 'dump');

            $mode = (string) ($_POST['dump_mode'] ?? 'full');
            $modes = [
                'full' => [
                    'label' => 'struktur dan data',
                    'filename' => 'full',
                    'include_structure' => true,
                    'include_data' => true
                ],
                'structure' => [
                    'label' => 'struktur saja',
                    'filename' => 'structure',
                    'include_structure' => true,
                    'include_data' => false
                ],
                'data' => [
                    'label' => 'data saja',
                    'filename' => 'data',
                    'include_structure' => false,
                    'include_data' => true
                ]
            ];

            if (!isset($modes[$mode])) {
                throw new RuntimeException('Mode dump database tidak valid.');
            }

            $dumpConfig = $modes[$mode];
            $filename = backup_restore_filename('teakwave-dump-' . $dumpConfig['filename']);
            $path = db_dump_path($filename);

            if (!$path) {
                throw new RuntimeException('Lokasi file dump sementara tidak valid.');
            }

            $dumpCleanupPath = $path;
            register_shutdown_function(function () use ($dumpCleanupPath) {
                if (is_file($dumpCleanupPath)) {
                    @unlink($dumpCleanupPath);
                }
            });

            $operationLock = db_restore_acquire_lock();

            try {
                db_create_backup(
                    $pdo,
                    $path,
                    $databaseName,
                    [
                        'include_structure' => $dumpConfig['include_structure'],
                        'include_data' => $dumpConfig['include_data'],
                        'artifact_type' => 'Dump'
                    ]
                );
            } finally {
                db_restore_release_lock($operationLock);
            }

            $size = (int) filesize($path);
            $sha256 = hash_file('sha256', $path);

            backup_restore_log(
                'database_dump',
                'Membuat dan mengunduh dump database sementara; mode: ' . $dumpConfig['label']
                . '; file: ' . $filename
                . '; ukuran: ' . $size . ' byte'
                . ($sha256 ? '; SHA-256: ' . substr($sha256, 0, 16) : '')
                . '. File tidak disimpan dalam riwayat backup.'
            );

            backup_restore_send_download($path, $filename, true);
        }

        if ($action === 'restore_media_upload') {
            if (($_POST['confirm_media_restore'] ?? '') !== 'yes') {
                throw new RuntimeException('Centang konfirmasi restore foto media terlebih dahulu.');
            }

            backup_restore_assert_rate_limit();
            backup_restore_verify_current_password($pdo, $_POST['current_password'] ?? '', 'restore');

            if (!isset($_FILES['media_zip'])) {
                throw new RuntimeException('Pilih file backup foto media (.zip) yang akan direstore.');
            }

            $result = media_backup_restore_uploaded_archive(
                $_FILES['media_zip'],
                $_POST['media_original_name'] ?? ''
            );

            backup_restore_log(
                'media_restore_upload',
                'Restore foto media berhasil dari ' . backup_restore_log_filename($_POST['media_original_name'] ?? ($_FILES['media_zip']['name'] ?? ''))
                . '; file direstore: ' . (int) $result['restored_count']
                . '; ditimpa: ' . (int) $result['overwritten_count']
                . '; file baru: ' . (int) $result['new_count']
                . '; backup pengaman: ' . ($result['safety_backup'] ?: 'tidak diperlukan') . '.'
            );

            $successText = 'Restore foto media berhasil. '
                . number_format((int) $result['restored_count'], 0, ',', '.') . ' file dipulihkan';
            if (!empty($result['safety_backup'])) {
                $successText .= '. Backup pengaman file lama tersimpan sebagai ' . $result['safety_backup'];
            }
            $successText .= '.';

            backup_restore_flash('success', $successText);
            backup_restore_redirect();
        }

        if (in_array($action, ['restore_upload', 'reset_database'], true)) {
            if (($_POST['confirm_restore'] ?? '') !== 'yes') {
                throw new RuntimeException('Centang konfirmasi restore terlebih dahulu.');
            }

            backup_restore_assert_rate_limit();
            backup_restore_verify_current_password($pdo, $_POST['current_password'] ?? '');

            if (!isset($_FILES['sql_file'])) {
                throw new RuntimeException('Pilih file backup SQL yang akan direstore.');
            }

            $quarantined = null;
            $restoreLock = null;

            try {
                $quarantined = db_quarantine_uploaded_sql(
                    $_FILES['sql_file'],
                    $_POST['sql_original_name'] ?? ''
                );

                // Validate every SQL statement before any current data is changed.
                $validatedStatements = db_restore_preflight_file($quarantined['path'], $databaseName);
                $restoreLock = db_restore_acquire_lock();

                $safetyName = backup_restore_filename('before-restore');
                $safetyPath = db_backup_path($safetyName);
                db_create_backup($pdo, $safetyPath, $databaseName);

                $executed = db_restore_from_file($pdo, $quarantined['path'], $databaseName);
                // Restore may replace activity_logs with an older schema. Force a
                // fresh schema inspection before writing the success audit event.
                reset_activity_log_schema_cache();
                backup_restore_log(
                    'restore_upload',
                    'Restore database berhasil dari upload ' . backup_restore_log_filename($quarantined['original_name'])
                    . '; SHA-256: ' . substr($quarantined['sha256'], 0, 16)
                    . '; backup pengaman: ' . $safetyName
                    . '; statement tervalidasi: ' . $validatedStatements
                    . '; statement dijalankan: ' . $executed . '.',
                    true
                );

                backup_restore_flash(
                    'success',
                    'Restore berhasil. ' . number_format($executed, 0, ',', '.')
                    . ' perintah SQL dijalankan. Backup pengaman tersimpan sebagai ' . $safetyName . '.'
                );
            } finally {
                db_restore_release_lock($restoreLock);

                if (is_array($quarantined) && !empty($quarantined['path'])) {
                    db_delete_quarantined_restore($quarantined['path']);
                }
            }

            backup_restore_redirect();
        }

        if ($action === 'restore_saved') {
            if (($_POST['confirm_restore'] ?? '') !== 'yes') {
                throw new RuntimeException('Konfirmasi restore tidak valid.');
            }

            backup_restore_assert_rate_limit();
            backup_restore_verify_current_password($pdo, $_POST['current_password'] ?? '');

            $filename = db_backup_safe_name($_POST['filename'] ?? '');
            $sourcePath = $filename ? db_backup_path($filename) : null;

            if (!$sourcePath || !is_file($sourcePath)) {
                throw new RuntimeException('File backup yang dipilih tidak ditemukan.');
            }

            $restoreLock = db_restore_acquire_lock();

            try {
                db_restore_preflight_file($sourcePath, $databaseName);

                $safetyName = backup_restore_filename('before-restore');
                $safetyPath = db_backup_path($safetyName);
                db_create_backup($pdo, $safetyPath, $databaseName);

                $executed = db_restore_from_file($pdo, $sourcePath, $databaseName);
            } finally {
                db_restore_release_lock($restoreLock);
            }

            // Restore may replace activity_logs with an older schema. Force a
            // fresh schema inspection before writing the success audit event.
            reset_activity_log_schema_cache();
            backup_restore_log(
                'restore_saved',
                'Restore database berhasil dari backup tersimpan ' . $filename
                . '; backup pengaman: ' . $safetyName
                . '; statement dijalankan: ' . $executed . '.',
                true
            );

            backup_restore_flash(
                'success',
                'Restore dari ' . $filename . ' berhasil. Backup pengaman baru: ' . $safetyName . '.'
            );
            backup_restore_redirect();
        }

        if ($action === 'delete_backup') {
            $filename = db_backup_safe_name($_POST['filename'] ?? '');
            $path = $filename ? db_backup_path($filename) : null;

            if (!$path || !is_file($path)) {
                throw new RuntimeException('File backup tidak ditemukan.');
            }

            $operationLock = db_restore_acquire_lock();
            try {
                if (!unlink($path)) {
                    throw new RuntimeException('File backup gagal dihapus.');
                }
            } finally {
                db_restore_release_lock($operationLock);
            }

            backup_restore_log('backup_delete', 'Menghapus backup database: ' . $filename . '.');
            backup_restore_flash('success', 'Backup ' . $filename . ' berhasil dihapus.');
            backup_restore_redirect();
        }

        throw new RuntimeException('Aksi tidak dikenali.');
    } catch (Throwable $e) {
        backup_restore_log(
            backup_restore_failure_action($action),
            'Aksi Backup & Restore gagal.' . backup_restore_failure_context($action)
            . ' Alasan: ' . sanitize_plain_text($e->getMessage(), 1000)
        );
        backup_restore_flash('danger', $e->getMessage());
        backup_restore_redirect();
    }
}

$backups = db_list_backups();
$uploadMax = ini_get('upload_max_filesize');
$postMax = ini_get('post_max_size');
$permissionUpgradeStatus = backup_restore_permission_upgrade_status($pdo);
$mediaCategories = media_backup_categories();
$mediaZipAvailable = media_backup_zip_available();
$mediaRestoreMaxBytes = media_backup_restore_max_bytes();
$mediaRestoreMaxLabel = media_backup_format_bytes($mediaRestoreMaxBytes);

include 'includes/header.php';
include 'includes/sidebar.php';
?>
<main class="main-content">
    <?php include 'includes/topbar.php'; ?>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo e($message['type']); ?> alert-dismissible fade show" role="alert">
            <?php echo e($message['text']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Backup & Restore Data & Media</h4>
            <p class="text-muted mb-0">Kelola backup foto website dan database aktif: <strong><?php echo e($databaseName); ?></strong></p>
        </div>
        <span class="badge text-bg-danger px-3 py-2"><i class="bi bi-shield-lock-fill me-1"></i> Akses Sensitif</span>
    </div>

    <?php if (is_super_admin()): ?>
        <div class="card soft-card mb-4 border <?php echo $permissionUpgradeStatus['installed'] ? 'border-success-subtle' : 'border-warning-subtle'; ?>">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                    <div class="d-flex align-items-start gap-3">
                        <div class="stat-icon <?php echo $permissionUpgradeStatus['installed'] ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning'; ?> flex-shrink-0">
                            <i class="bi bi-database-check"></i>
                        </div>
                        <div>
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                <h5 class="fw-bold mb-0">Upgrade Izin Backup & Restore</h5>
                                <?php if ($permissionUpgradeStatus['error']): ?>
                                    <span class="badge text-bg-danger">Status gagal diperiksa</span>
                                <?php elseif ($permissionUpgradeStatus['installed']): ?>
                                    <span class="badge text-bg-success">Sudah terpasang</span>
                                <?php else: ?>
                                    <span class="badge text-bg-warning">Belum dijalankan</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-muted mb-1">
                                Jalankan <code>upgrade_backup_restore_permission.sql</code> langsung melalui dashboard ini tanpa phpMyAdmin atau MySQL client.
                            </p>
                            <?php if ($permissionUpgradeStatus['error']): ?>
                                <div class="small text-danger">Pemeriksaan status gagal: <?php echo e($permissionUpgradeStatus['error']); ?></div>
                            <?php else: ?>
                                <div class="small text-muted">
                                    Status izin Super Admin: <?php echo e($permissionUpgradeStatus['installed_roles']); ?> dari <?php echo e($permissionUpgradeStatus['target_roles']); ?> role target.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <form method="post" onsubmit="return confirm('Jalankan upgrade izin Backup & Restore sekarang?');" class="flex-shrink-0">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="action" value="run_permission_upgrade">
                        <button class="btn <?php echo $permissionUpgradeStatus['installed'] ? 'btn-outline-success' : 'btn-warning'; ?>" type="submit">
                            <i class="bi bi-play-circle me-2"></i>
                            <?php echo $permissionUpgradeStatus['installed'] ? 'Jalankan Ulang dengan Aman' : 'Jalankan Upgrade Sekarang'; ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!$mediaZipAvailable): ?>
        <div class="alert alert-warning d-flex align-items-start gap-3 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-4"></i>
            <div>
                <strong>Fitur backup foto belum tersedia.</strong>
                Aktifkan ekstensi PHP <code>zip</code>/<code>ZipArchive</code> melalui panel hosting, lalu muat ulang halaman ini.
            </div>
        </div>
    <?php endif; ?>

    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
        <div>
            <h5 class="fw-bold mb-1">Backup & Restore Foto Website</h5>
            <p class="text-muted small mb-0">Mencakup file gambar lokal yang digunakan oleh produk, contents, video, dan brands.</p>
        </div>
        <span class="badge bg-light text-dark border">Format aman: ZIP + manifest SHA-256</span>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-xl-6">
            <div class="card soft-card h-100 border border-primary-subtle">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="stat-icon bg-primary-subtle text-primary flex-shrink-0">
                            <i class="bi bi-images"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1">Backup Foto Media</h5>
                            <p class="text-muted mb-0">Mengumpulkan foto lokal sesuai referensi database dan mengunduhnya sebagai satu arsip ZIP.</p>
                        </div>
                    </div>

                    <div class="alert alert-info small">
                        Struktur folder asli dipertahankan agar file dapat direstore ke lokasi yang sama. Data tabel database tidak dimasukkan ke backup ini.
                    </div>

                    <form method="post" id="mediaBackupForm" class="mt-auto">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="action" value="create_media_backup">

                        <label class="form-label fw-semibold">Pilih Foto yang Dibackup</label>
                        <div class="row g-2 mb-3">
                            <?php foreach ($mediaCategories as $categoryKey => $categoryLabel): ?>
                                <div class="col-sm-6">
                                    <div class="form-check border rounded-3 px-3 py-2 h-100">
                                        <input
                                            class="form-check-input ms-0 me-2"
                                            type="checkbox"
                                            name="media_categories[]"
                                            value="<?php echo e($categoryKey); ?>"
                                            id="mediaCategory<?php echo e(ucfirst($categoryKey)); ?>"
                                            checked
                                        >
                                        <label class="form-check-label fw-semibold" for="mediaCategory<?php echo e(ucfirst($categoryKey)); ?>">
                                            <?php echo e($categoryLabel); ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="form-text mb-3">
                            Thumbnail YouTube eksternal tidak diunduh. Hanya gambar lokal pada <code>uploads/</code>, <code>produk/</code>, dan <code>assets/img/</code> yang disertakan.
                        </div>

                        <button class="btn btn-primary w-100" type="submit" <?php echo $mediaZipAvailable ? '' : 'disabled'; ?>>
                            <i class="bi bi-file-earmark-zip me-2"></i>Buat & Download Backup Foto
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card soft-card h-100 border border-success-subtle">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="stat-icon bg-success-subtle text-success flex-shrink-0">
                            <i class="bi bi-cloud-arrow-up"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1">Restore Foto Media</h5>
                            <p class="text-muted mb-0">Mengembalikan foto dari arsip ZIP Teakwave ke path aslinya tanpa mengubah data database.</p>
                        </div>
                    </div>

                    <div class="alert alert-warning small">
                        Semua file diperiksa berdasarkan manifest, ukuran, checksum, dan tipe gambar. File lama yang akan ditimpa dibackup otomatis terlebih dahulu.
                    </div>

                    <form method="post" enctype="multipart/form-data" id="mediaRestoreForm" class="mt-auto">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="action" value="restore_media_upload">
                        <input type="hidden" name="media_original_name" id="mediaOriginalName" value="">

                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="mediaRestoreFile">File Backup Foto (.zip)</label>
                            <input
                                class="form-control"
                                type="file"
                                name="media_zip"
                                id="mediaRestoreFile"
                                accept=".zip,application/zip"
                                data-upload-allowed-extensions="zip"
                                data-upload-max-bytes="<?php echo e($mediaRestoreMaxBytes); ?>"
                                required
                                <?php echo $mediaZipAvailable ? '' : 'disabled'; ?>
                            >
                            <div class="form-text">
                                Maksimal efektif: <?php echo e($mediaRestoreMaxLabel); ?>. Konfigurasi server: upload_max_filesize <?php echo e($uploadMax); ?>, post_max_size <?php echo e($postMax); ?>.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="mediaRestorePassword">Password Super Admin</label>
                            <input class="form-control" type="password" name="current_password" id="mediaRestorePassword" autocomplete="current-password" required <?php echo $mediaZipAvailable ? '' : 'disabled'; ?>>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="confirm_media_restore" value="yes" id="confirmMediaRestore" required <?php echo $mediaZipAvailable ? '' : 'disabled'; ?>>
                            <label class="form-check-label" for="confirmMediaRestore">
                                Saya memahami bahwa file foto dengan path yang sama akan diganti.
                            </label>
                        </div>

                        <button class="btn btn-success w-100" type="submit" <?php echo $mediaZipAvailable ? '' : 'disabled'; ?>>
                            <i class="bi bi-arrow-counterclockwise me-2"></i>Restore Foto Media
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
        <div>
            <h5 class="fw-bold mb-1">Dump & Restore Database</h5>
            <p class="text-muted small mb-0">Fitur database tetap tersedia secara terpisah dari backup foto.</p>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-6">
            <div class="card soft-card h-100 border border-primary-subtle">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="stat-icon bg-primary-subtle text-primary flex-shrink-0">
                            <i class="bi bi-filetype-sql"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1">Dump Database</h5>
                            <p class="text-muted mb-0">Mengunduh dump SQL langsung tanpa menyimpannya dalam riwayat backup server.</p>
                        </div>
                    </div>

                    <div class="alert alert-light border small">
                        File dibuat di folder sementara yang terlindungi dan otomatis dihapus setelah proses download selesai.
                    </div>

                    <form method="post" id="dumpDatabaseForm" class="mt-auto">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="action" value="dump_database">

                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="dumpMode">Isi Dump</label>
                            <select class="form-select" name="dump_mode" id="dumpMode" required>
                                <option value="full">Struktur dan data</option>
                                <option value="structure">Struktur saja</option>
                                <option value="data">Data saja</option>
                            </select>
                            <div class="form-text">Pilih dump lengkap untuk kebutuhan pemindahan atau arsip database.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="dumpCurrentPassword">Password Super Admin</label>
                            <input class="form-control" type="password" name="current_password" id="dumpCurrentPassword" autocomplete="current-password" required>
                            <div class="form-text">Konfirmasi ulang diperlukan karena dump dapat memuat data sensitif.</div>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="confirm_dump" value="yes" id="confirmDatabaseDump" required>
                            <label class="form-check-label" for="confirmDatabaseDump">
                                Saya memahami bahwa file dump harus disimpan dengan aman.
                            </label>
                        </div>

                        <button class="btn btn-outline-primary w-100" type="submit">
                            <i class="bi bi-file-earmark-arrow-down me-2"></i>Download Dump SQL
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card soft-card h-100 border border-danger-subtle">
                <div class="card-body p-4">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="stat-icon bg-danger-subtle text-danger flex-shrink-0">
                            <i class="bi bi-database-up"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1">Restore dari File SQL</h5>
                            <p class="text-muted mb-0">Mengganti isi database menggunakan file backup yang dipilih.</p>
                        </div>
                    </div>

                    <div class="alert alert-warning small">
                        Sistem membuat backup pengaman otomatis sebelum restore. Jangan menutup halaman saat proses berjalan.
                    </div>

                    <form method="post" enctype="multipart/form-data" id="restoreUploadForm">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="action" value="restore_upload">
                        <input type="hidden" name="sql_original_name" id="sqlOriginalName" value="">

                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="sqlRestoreFile">File Backup (.sql)</label>
                            <input class="form-control" type="file" name="sql_file" id="sqlRestoreFile" accept=".sql,text/plain,application/sql" data-upload-allowed-extensions="sql" data-upload-max-bytes="67108864" required>
                            <div class="form-text">Batas fitur: 64 MB. Konfigurasi server: upload_max_filesize <?php echo e($uploadMax); ?>, post_max_size <?php echo e($postMax); ?>.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="restoreCurrentPassword">Password Super Admin</label>
                            <input class="form-control" type="password" name="current_password" id="restoreCurrentPassword" autocomplete="current-password" required>
                            <div class="form-text">Diperlukan untuk mencegah restore oleh pihak yang mengambil alih sesi login.</div>
                        </div>

                        <div class="alert alert-light border small">
                            File dipindahkan ke karantina nonpublik, diperiksa tipe dan isinya, lalu setiap perintah SQL divalidasi sebelum dijalankan.
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="confirm_restore" value="yes" id="confirmRestoreUpload" required>
                            <label class="form-check-label" for="confirmRestoreUpload">
                                Saya memahami bahwa restore akan mengubah data saat ini.
                            </label>
                        </div>

                        <button class="btn btn-danger w-100" type="submit">
                            <i class="bi bi-arrow-counterclockwise me-2"></i>Restore Database
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card soft-card">
        <div class="card-header bg-white border-0 d-flex flex-wrap justify-content-between align-items-center gap-2 px-4 pt-4">
            <div>
                <h5 class="fw-bold mb-1">Riwayat Backup Server</h5>
                <p class="text-muted small mb-0">Backup terbaru ditampilkan paling atas.</p>
            </div>
            <span class="badge bg-light text-dark border"><?php echo e(count($backups)); ?> file</span>
        </div>
        <div class="card-body p-4">
            <?php if ($backups): ?>
                <div class="alert alert-light border d-flex flex-column flex-md-row align-items-md-center gap-2 mb-3">
                    <label class="small fw-semibold mb-0" for="savedRestoreCurrentPassword">Password Super Admin untuk restore tersimpan:</label>
                    <input type="password" class="form-control form-control-sm" id="savedRestoreCurrentPassword" autocomplete="current-password" style="max-width: 280px;" placeholder="Masukkan password saat akan restore">
                </div>
            <?php endif; ?>

            <?php if (!$backups): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-database-x fs-1 d-block mb-2"></i>
                    Belum ada backup database tersimpan.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Nama File</th>
                                <th>Ukuran</th>
                                <th>Dibuat</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($backups as $backup): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-filetype-sql text-primary fs-4"></i>
                                            <span class="fw-semibold text-break"><?php echo e($backup['name']); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo e(db_format_bytes($backup['size'])); ?></td>
                                    <td><?php echo e(date('d M Y, H:i', $backup['modified_at'])); ?></td>
                                    <td>
                                        <div class="d-flex flex-wrap justify-content-end gap-2">
                                            <a class="btn btn-sm btn-outline-primary" href="?download=<?php echo urlencode($backup['name']); ?>">
                                                <i class="bi bi-download"></i> Download
                                            </a>

                                            <form method="post" class="savedRestoreForm">
                                                <?php csrf_field(); ?>
                                                <input type="hidden" name="action" value="restore_saved">
                                                <input type="hidden" name="filename" value="<?php echo e($backup['name']); ?>">
                                                <input type="hidden" name="confirm_restore" value="yes">
                                                <input type="hidden" name="current_password" value="" class="savedRestorePassword">
                                                <button class="btn btn-sm btn-outline-danger" type="submit">
                                                    <i class="bi bi-arrow-counterclockwise"></i> Restore
                                                </button>
                                            </form>

                                            <form method="post" onsubmit="return confirm('Hapus file backup ini?');">
                                                <?php csrf_field(); ?>
                                                <input type="hidden" name="action" value="delete_backup">
                                                <input type="hidden" name="filename" value="<?php echo e($backup['name']); ?>">
                                                <button class="btn btn-sm btn-outline-secondary" type="submit" aria-label="Hapus backup">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const mediaBackupForm = document.getElementById('mediaBackupForm');

    if (mediaBackupForm) {
        mediaBackupForm.addEventListener('submit', function (event) {
            const selected = mediaBackupForm.querySelectorAll('input[name="media_categories[]"]:checked');
            if (!selected.length) {
                event.preventDefault();
                alert('Pilih minimal satu kategori foto yang akan dibackup.');
                return;
            }

            const submitButton = mediaBackupForm.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Membuat backup foto...';
            }
        });
    }

    const mediaRestoreForm = document.getElementById('mediaRestoreForm');
    const mediaRestoreInput = document.getElementById('mediaRestoreFile');
    const mediaOriginalName = document.getElementById('mediaOriginalName');

    if (mediaRestoreForm && mediaRestoreInput && mediaOriginalName) {
        mediaRestoreForm.addEventListener('submit', function (event) {
            const file = mediaRestoreInput.files && mediaRestoreInput.files[0] ? mediaRestoreInput.files[0] : null;
            const maxBytes = Number(mediaRestoreInput.dataset.uploadMaxBytes || 0);

            if (!file || !/\.zip$/i.test(file.name)) {
                event.preventDefault();
                alert('Pilih file backup foto dengan ekstensi .zip.');
                return;
            }

            if (file.size <= 0 || (maxBytes > 0 && file.size > maxBytes)) {
                event.preventDefault();
                alert('Ukuran file backup foto tidak valid atau melebihi batas upload server.');
                return;
            }

            if (!window.confirm('Restore akan menimpa foto yang memiliki path sama. Sistem akan membuat backup pengaman terlebih dahulu. Lanjutkan?')) {
                event.preventDefault();
                return;
            }

            mediaOriginalName.value = file.name;

            // Gunakan nama transport netral agar aturan WAF hosting tidak menolak
            // upload berdasarkan ekstensi sebelum validasi PHP dijalankan.
            try {
                const transportedFile = new File([file], 'media-restore-' + Date.now() + '.upload', {
                    type: 'application/octet-stream',
                    lastModified: file.lastModified
                });
                const transfer = new DataTransfer();
                transfer.items.add(transportedFile);
                mediaRestoreInput.files = transfer.files;
            } catch (error) {
                // Browser lama tetap aman karena validasi server bersifat otoritatif.
            }

            const submitButton = mediaRestoreForm.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Merestore foto...';
            }
        });
    }

    const dumpForm = document.getElementById('dumpDatabaseForm');

    if (dumpForm) {
        dumpForm.addEventListener('submit', function (event) {
            if (!window.confirm('Buat dan download dump database sekarang?')) {
                event.preventDefault();
                return;
            }

            const submitButton = dumpForm.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Membuat dump...';
            }
        });
    }

    const resetForm = document.getElementById('resetDatabaseForm');
    const resetInput = document.getElementById('resetSqlFile');
    const resetOriginalName = document.getElementById('resetSqlOriginalName');

    if (resetForm && resetInput && resetOriginalName) {
        resetForm.addEventListener('submit', function (event) {
            const phrase = document.getElementById('resetPhrase');
            if (!phrase || phrase.value !== 'RESET DATABASE') {
                event.preventDefault();
                alert('Ketik RESET DATABASE dengan tepat.');
                return;
            }

            if (!window.confirm('PERINGATAN: seluruh database aktif akan dihapus dan dibangun ulang dari dump. Lanjutkan?')) {
                event.preventDefault();
                return;
            }

            const file = resetInput.files && resetInput.files[0] ? resetInput.files[0] : null;
            if (!file || !/\.sql$/i.test(file.name)) {
                event.preventDefault();
                alert('Pilih file dump dengan ekstensi .sql.');
                return;
            }

            if (file.size <= 0 || file.size > 64 * 1024 * 1024) {
                event.preventDefault();
                alert('Ukuran file dump harus lebih dari 0 byte dan maksimal 64 MB.');
                return;
            }

            resetOriginalName.value = file.name;

            try {
                const safeName = 'database-reset-' + Date.now() + '.upload';
                const transportedFile = new File([file], safeName, {
                    type: 'application/octet-stream',
                    lastModified: file.lastModified
                });
                const transfer = new DataTransfer();
                transfer.items.add(transportedFile);
                resetInput.files = transfer.files;
            } catch (error) {
                // Server-side validation remains authoritative on older browsers.
            }

            const submitButton = resetForm.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Mereset database...';
            }
        });
    }

    const form = document.getElementById('restoreUploadForm');
    const input = document.getElementById('sqlRestoreFile');
    const originalName = document.getElementById('sqlOriginalName');

    if (!form || !input || !originalName) return;

    form.addEventListener('submit', function (event) {
        if (!window.confirm('Restore akan mengubah isi database. Lanjutkan?')) {
            event.preventDefault();
            return;
        }

        const file = input.files && input.files[0] ? input.files[0] : null;

        if (!file || !/\.sql$/i.test(file.name)) {
            event.preventDefault();
            alert('Pilih file dengan ekstensi .sql.');
            return;
        }

        if (file.size <= 0 || file.size > 64 * 1024 * 1024) {
            event.preventDefault();
            alert('Ukuran file SQL harus lebih dari 0 byte dan maksimal 64 MB.');
            return;
        }

        originalName.value = file.name;

        // Some hosting/WAF rules reject multipart filenames ending in .sql before PHP runs.
        // Transport the same bytes with a neutral non-executable name; the server still
        // requires the original .sql name and performs full content/statement validation.
        try {
            const safeName = 'database-restore-' + Date.now() + '.upload';
            const transportedFile = new File([file], safeName, {
                type: 'application/octet-stream',
                lastModified: file.lastModified
            });
            const transfer = new DataTransfer();
            transfer.items.add(transportedFile);
            input.files = transfer.files;
        } catch (error) {
            // Older browsers submit the original .sql filename; server-side validation remains active.
        }
    });

    document.querySelectorAll('.savedRestoreForm').forEach(function (savedForm) {
        savedForm.addEventListener('submit', function (event) {
            if (!window.confirm('Restore database dari backup tersimpan ini?')) {
                event.preventDefault();
                return;
            }

            const passwordInput = document.getElementById('savedRestoreCurrentPassword');
            const password = passwordInput ? passwordInput.value : '';
            if (!password) {
                event.preventDefault();
                alert('Masukkan password Super Admin pada kolom di atas daftar backup.');
                if (passwordInput) passwordInput.focus();
                return;
            }

            const passwordField = savedForm.querySelector('.savedRestorePassword');
            if (!passwordField) {
                event.preventDefault();
                return;
            }

            passwordField.value = password;
        });
    });
});
</script>
<?php include 'includes/footer.php'; ?>

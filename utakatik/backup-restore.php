<?php
ob_start();
require_once 'auth/check.php';
require_once __DIR__ . '/includes/database-backup.php';

$page_title = 'Backup & Restore Database';
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

function backup_restore_filename($prefix = 'backup') {
    return $prefix . '-' . date('Ymd-His') . '-' . substr(bin2hex(random_bytes(3)), 0, 6) . '.sql';
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

function backup_restore_send_download($path, $downloadName) {
    if (!is_file($path) || !is_readable($path)) {
        http_response_code(404);
        exit('File backup tidak ditemukan.');
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: application/sql; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . str_replace('"', '', basename($downloadName)) . '"');
    header('Content-Length: ' . filesize($path));
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    exit;
}

if (isset($_GET['download'])) {
    $filename = db_backup_safe_name($_GET['download']);
    $path = $filename ? db_backup_path($filename) : null;

    if (!$path) {
        http_response_code(400);
        exit('Nama file backup tidak valid.');
    }

    log_activity('download', 'database-backup', 'Mengunduh backup database: ' . $filename);
    backup_restore_send_download($path, $filename);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'run_permission_upgrade') {
            $upgradePath = __DIR__ . '/database/upgrade_backup_restore_permission.sql';
            $affected = backup_restore_run_permission_upgrade($pdo, $upgradePath);
            $status = backup_restore_permission_upgrade_status($pdo);

            if (!$status['installed']) {
                throw new RuntimeException('Upgrade telah dijalankan, tetapi status izin belum lengkap. Periksa struktur tabel roles dan role_permissions.');
            }

            log_activity(
                'upgrade',
                'database-backup',
                'Menjalankan upgrade_backup_restore_permission.sql melalui dashboard; baris baru: ' . $affected
            );

            backup_restore_flash(
                'success',
                $affected > 0
                    ? 'Upgrade database berhasil. Izin Backup & Restore telah ditambahkan untuk Super Admin.'
                    : 'Upgrade database sudah terpasang sebelumnya. Tidak ada perubahan data yang diperlukan.'
            );
            backup_restore_redirect();
        }

        if ($action === 'create_backup') {
            $filename = backup_restore_filename('teakwave-db');
            $path = db_backup_path($filename);
            db_create_backup($pdo, $path, $databaseName);
            log_activity('backup', 'database-backup', 'Membuat backup database: ' . $filename);
            backup_restore_send_download($path, $filename);
        }

        if ($action === 'restore_upload') {
            if (($_POST['confirm_restore'] ?? '') !== 'yes') {
                throw new RuntimeException('Centang konfirmasi restore terlebih dahulu.');
            }

            if (!isset($_FILES['sql_file']) || $_FILES['sql_file']['error'] === UPLOAD_ERR_NO_FILE) {
                throw new RuntimeException('Pilih file backup SQL yang akan direstore.');
            }

            if ($_FILES['sql_file']['error'] !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Upload file SQL gagal. Kode error: ' . $_FILES['sql_file']['error']);
            }

            $originalName = basename((string) $_FILES['sql_file']['name']);
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $size = (int) ($_FILES['sql_file']['size'] ?? 0);

            if ($extension !== 'sql') {
                throw new RuntimeException('File restore harus menggunakan ekstensi .sql.');
            }

            if ($size <= 0 || $size > 64 * 1024 * 1024) {
                throw new RuntimeException('Ukuran file SQL harus antara 1 byte sampai 64 MB.');
            }

            $tmpPath = $_FILES['sql_file']['tmp_name'];
            if (!is_uploaded_file($tmpPath)) {
                throw new RuntimeException('File upload tidak valid.');
            }

            $safetyName = backup_restore_filename('before-restore');
            $safetyPath = db_backup_path($safetyName);
            db_create_backup($pdo, $safetyPath, $databaseName);

            $executed = db_restore_from_file($pdo, $tmpPath, $databaseName);
            log_activity(
                'restore',
                'database-backup',
                'Restore database dari upload ' . $originalName . '; safety backup: ' . $safetyName . '; statements: ' . $executed
            );

            backup_restore_flash(
                'success',
                'Restore berhasil. ' . number_format($executed, 0, ',', '.') . ' perintah SQL dijalankan. Backup pengaman tersimpan sebagai ' . $safetyName . '.'
            );
            backup_restore_redirect();
        }

        if ($action === 'restore_saved') {
            if (($_POST['confirm_restore'] ?? '') !== 'yes') {
                throw new RuntimeException('Centang konfirmasi restore terlebih dahulu.');
            }

            $filename = db_backup_safe_name($_POST['filename'] ?? '');
            $sourcePath = $filename ? db_backup_path($filename) : null;

            if (!$sourcePath || !is_file($sourcePath)) {
                throw new RuntimeException('File backup yang dipilih tidak ditemukan.');
            }

            $safetyName = backup_restore_filename('before-restore');
            $safetyPath = db_backup_path($safetyName);
            db_create_backup($pdo, $safetyPath, $databaseName);

            $executed = db_restore_from_file($pdo, $sourcePath, $databaseName);
            log_activity(
                'restore',
                'database-backup',
                'Restore database dari backup tersimpan ' . $filename . '; safety backup: ' . $safetyName . '; statements: ' . $executed
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

            if (!unlink($path)) {
                throw new RuntimeException('File backup gagal dihapus.');
            }

            log_activity('delete', 'database-backup', 'Menghapus backup database: ' . $filename);
            backup_restore_flash('success', 'Backup ' . $filename . ' berhasil dihapus.');
            backup_restore_redirect();
        }

        throw new RuntimeException('Aksi tidak dikenali.');
    } catch (Throwable $e) {
        log_activity('error', 'database-backup', $e->getMessage());
        backup_restore_flash('danger', $e->getMessage());
        backup_restore_redirect();
    }
}

$backups = db_list_backups();
$uploadMax = ini_get('upload_max_filesize');
$postMax = ini_get('post_max_size');
$permissionUpgradeStatus = backup_restore_permission_upgrade_status($pdo);

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
            <h4 class="fw-bold mb-1">Backup & Restore Database</h4>
            <p class="text-muted mb-0">Database aktif: <strong><?php echo e($databaseName); ?></strong></p>
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

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card soft-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="stat-icon bg-primary-subtle text-primary flex-shrink-0">
                            <i class="bi bi-database-down"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1">Backup Database</h5>
                            <p class="text-muted mb-0">Membuat salinan struktur dan seluruh data dalam format SQL.</p>
                        </div>
                    </div>

                    <div class="alert alert-info small">
                        File disimpan di server sebagai riwayat backup dan otomatis diunduh ke perangkat Anda.
                    </div>

                    <form method="post">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="action" value="create_backup">
                        <button class="btn btn-primary w-100" type="submit">
                            <i class="bi bi-download me-2"></i>Buat & Download Backup
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
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

                    <form method="post" enctype="multipart/form-data" onsubmit="return confirm('Restore akan mengubah isi database. Lanjutkan?');">
                        <?php csrf_field(); ?>
                        <input type="hidden" name="action" value="restore_upload">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">File Backup (.sql)</label>
                            <input class="form-control" type="file" name="sql_file" accept=".sql,application/sql,text/plain" required>
                            <div class="form-text">Batas fitur: 64 MB. Konfigurasi server: upload_max_filesize <?php echo e($uploadMax); ?>, post_max_size <?php echo e($postMax); ?>.</div>
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

                                            <form method="post" onsubmit="return confirm('Restore database dari backup ini?');">
                                                <?php csrf_field(); ?>
                                                <input type="hidden" name="action" value="restore_saved">
                                                <input type="hidden" name="filename" value="<?php echo e($backup['name']); ?>">
                                                <input type="hidden" name="confirm_restore" value="yes">
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
<?php include 'includes/footer.php'; ?>

<?php
require_once 'auth/check.php';
$page_title = 'Website Front Settings';

$timezones = DateTimeZone::listIdentifiers(DateTimeZone::ALL);


function normalize_external_link($value, $label, $allowPhoneNumber = false) {
    $value = trim((string) $value);

    if ($allowPhoneNumber && preg_match('/^\+?[0-9][0-9\s().-]{7,}$/', $value)) {
        $digits = preg_replace('/\D+/', '', $value);
        return 'https://wa.me/' . $digits;
    }

    if ($value !== '' && !preg_match('#^https?://#i', $value)) {
        $value = 'https://' . ltrim($value, '/');
    }

    if ($value === '' || !filter_var($value, FILTER_VALIDATE_URL)) {
        throw new Exception($label . ' harus berupa URL yang valid.');
    }

    $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
    if (!in_array($scheme, ['http', 'https'], true)) {
        throw new Exception($label . ' hanya boleh menggunakan http atau https.');
    }

    return $value;
}

function get_external_link_setting($key, $default, $legacyKey = null) {
    $candidates = [get_website_setting($key, '')];

    if ($legacyKey) {
        $candidates[] = get_website_setting($legacyKey, '');
    }

    foreach ($candidates as $candidate) {
        $candidate = trim((string) $candidate);
        if (filter_var($candidate, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $candidate)) {
            return $candidate;
        }
    }

    return $default;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $newFaviconPath = null;

    try {
        $site_name = sanitize_plain_text($_POST['site_name'] ?? '', 150);
        $meta_title = sanitize_plain_text($_POST['meta_title'] ?? '', 180);
        $meta_description = sanitize_plain_text($_POST['meta_description'] ?? '', 320);
        $meta_keywords = sanitize_plain_text($_POST['meta_keywords'] ?? '', 500);
        $timezone = sanitize_plain_text($_POST['timezone'] ?? 'Asia/Jakarta', 120);
        $date_format = sanitize_plain_text($_POST['date_format'] ?? 'd M Y', 50);
        $time_format = sanitize_plain_text($_POST['time_format'] ?? 'H:i', 50);
        $upload_max_filesize_mb = clean_decimal($_POST['upload_max_filesize_mb'] ?? 5, 5);
        if ($upload_max_filesize_mb <= 0) $upload_max_filesize_mb = 5;
        if ($upload_max_filesize_mb > 100) $upload_max_filesize_mb = 100;
        $upload_allowed_extensions = normalize_upload_extensions($_POST['upload_allowed_extensions'] ?? 'jpg,jpeg,png,gif,webp,pdf,ico');

        $tokopedia_url = normalize_external_link($_POST['tokopedia_url'] ?? '', 'URL Tokopedia');
        $shopee_url = normalize_external_link($_POST['shopee_url'] ?? '', 'URL Shopee');
        $whatsapp_url = normalize_external_link($_POST['whatsapp_url'] ?? '', 'URL WhatsApp', true);
        $instagram_url = normalize_external_link($_POST['instagram_url'] ?? '', 'URL Instagram');
        $facebook_url = normalize_external_link($_POST['facebook_url'] ?? '', 'URL Facebook');

        if (!in_array($timezone, $timezones, true)) {
            $timezone = 'Asia/Jakarta';
        }

        if ($site_name === '') {
            throw new Exception('Nama web tidak boleh kosong.');
        }

        $oldFaviconPath = get_website_setting('favicon', '');
        $newFaviconPath = upload_favicon_file('favicon');

        $settingsToSave = [
            'site_name' => [$site_name, 'text'],
            'meta_title' => [$meta_title, 'text'],
            'meta_description' => [$meta_description, 'textarea'],
            'meta_keywords' => [$meta_keywords, 'textarea'],
            'timezone' => [$timezone, 'text'],
            'date_format' => [$date_format, 'text'],
            'time_format' => [$time_format, 'text'],
            'upload_max_filesize_mb' => [(string) $upload_max_filesize_mb, 'number'],
            'upload_allowed_extensions' => [$upload_allowed_extensions, 'textarea'],
            'tokopedia_url' => [$tokopedia_url, 'url'],
            'shopee_url' => [$shopee_url, 'url'],
            'whatsapp_url' => [$whatsapp_url, 'url'],
            'instagram_url' => [$instagram_url, 'url'],
            'facebook_url' => [$facebook_url, 'url'],
            // Sinkron dengan key footer lama.
            'footer_instagram_url' => [$instagram_url, 'url'],
            'footer_facebook_url' => [$facebook_url, 'url'],
        ];

        if ($newFaviconPath !== null) {
            $settingsToSave['favicon'] = [$newFaviconPath, 'file'];
        }

        $pdo->beginTransaction();

        foreach ($settingsToSave as $settingKey => [$settingValue, $settingType]) {
            if (!update_website_setting($settingKey, $settingValue, $settingType)) {
                throw new RuntimeException('Gagal menyimpan setting: ' . $settingKey);
            }
        }

        $pdo->commit();

        // Hapus favicon lama hanya setelah database berhasil di-commit.
        if ($newFaviconPath !== null && $oldFaviconPath !== '' && $oldFaviconPath !== $newFaviconPath) {
            delete_local_upload($oldFaviconPath);
        }

        log_activity('update', 'website-settings', 'Mengubah konfigurasi website front.');
        redirect('website-settings.php?updated=1&v=' . time());
    } catch (Throwable $e) {
        if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        // Jangan tinggalkan file baru apabila penyimpanan database gagal.
        if ($newFaviconPath !== null) {
            delete_local_upload($newFaviconPath);
        }

        $error = $e->getMessage();
    }
}

$site_name = get_website_setting('site_name', 'Digitaria');
$meta_title = get_website_setting('meta_title', 'Digitaria - Web Design, Web Developer & Digital Agency Surabaya');
$meta_description = get_website_setting('meta_description', '');
$meta_keywords = get_website_setting('meta_keywords', '');
$favicon = get_website_setting('favicon', '');
$timezone = get_website_setting('timezone', 'Asia/Jakarta');
$date_format = get_website_setting('date_format', 'd M Y');
$time_format = get_website_setting('time_format', 'H:i');
$upload_max_filesize_mb = get_website_setting('upload_max_filesize_mb', '5');
$upload_allowed_extensions = get_website_setting('upload_allowed_extensions', 'jpg,jpeg,png,gif,webp,pdf,ico');

$tokopedia_url = get_external_link_setting('tokopedia_url', 'https://www.tokopedia.com/teakwave');
$shopee_url = get_external_link_setting('shopee_url', 'https://shopee.co.id/teakwave');
$whatsapp_url = get_external_link_setting('whatsapp_url', 'https://wa.me/6282112345678');
$instagram_url = get_external_link_setting('instagram_url', 'https://www.instagram.com/teak.wave/', 'footer_instagram_url');
$facebook_url = get_external_link_setting('facebook_url', 'https://www.facebook.com/teakwave', 'footer_facebook_url');

$faviconPreviewUrl = '';
if ($favicon !== '') {
    $normalizedFavicon = normalize_upload_storage_path($favicon);
    $faviconFile = upload_storage_filesystem_path($normalizedFavicon);
    $faviconVersion = is_file($faviconFile) ? (string) filemtime($faviconFile) : (string) time();
    $faviconPreviewUrl = $normalizedFavicon . '?v=' . rawurlencode($faviconVersion);
}

try {
    $previewDate = new DateTime('now', new DateTimeZone($timezone));
} catch (Exception $e) {
    $previewDate = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>
<main class="main-content">
<?php include 'includes/topbar.php'; ?>

<div class="row g-4">
    <div class="col-xl-8">
        <div class="card soft-card">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="fw-bold mb-0">Konfigurasi Website Front</h5>
                        <small class="text-muted">Atur identitas, meta SEO, favicon, dan setting jam website.</small>
                    </div>
                    <button type="submit" form="websiteSettingsForm" class="btn btn-primary btn-sm">
                        <i class="bi bi-check-circle"></i> Save Settings
                    </button>
                </div>

                <?php if(!empty($_GET['updated'])): ?>
                    <div class="alert alert-success">Konfigurasi website berhasil diperbarui. Favicon dan metadata frontend memakai cache-buster terbaru.</div>
                <?php endif; ?>

                <?php if(!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo e($error); ?></div>
                <?php endif; ?>

                <form id="websiteSettingsForm" method="post" enctype="multipart/form-data" autocomplete="off">
                    <?php csrf_field(); ?>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nama Web</label>
                            <input name="site_name" class="form-control" required maxlength="150" value="<?php echo e($site_name); ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Meta Title</label>
                            <input name="meta_title" class="form-control" maxlength="180" value="<?php echo e($meta_title); ?>">
                            <small class="text-muted">Rekomendasi SEO: 50–60 karakter, tetapi boleh lebih sesuai kebutuhan.</small>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Meta Description</label>
                            <textarea name="meta_description" rows="4" class="form-control" maxlength="320"><?php echo e($meta_description); ?></textarea>
                            <small class="text-muted">Rekomendasi SEO: 140–160 karakter.</small>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Meta Keyword</label>
                            <textarea name="meta_keywords" rows="3" class="form-control" maxlength="500"><?php echo e($meta_keywords); ?></textarea>
                            <small class="text-muted">Pisahkan keyword dengan koma. Contoh: web design, digital agency, surabaya</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Upload / Edit Favicon</label>
                            <input name="favicon" type="file" class="form-control" accept=".ico,.png,.jpg,.jpeg,.webp">
                            <small class="text-muted">Format aman: ICO, PNG, JPG, WEBP. Batas ukuran mengikuti Upload Settings dan konfigurasi PHP server.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Current Favicon</label>
                            <div class="favicon-preview-box">
                                <?php if($faviconPreviewUrl): ?>
                                    <img src="<?php echo e($faviconPreviewUrl); ?>" alt="Favicon aktif" width="48" height="48">
                                    <code><?php echo e(normalize_upload_storage_path($favicon)); ?></code>
                                <?php else: ?>
                                    <span class="text-muted">Belum ada favicon.</span>
                                <?php endif; ?>
                            </div>
                        </div>


                        <div class="col-12">
                            <div class="upload-settings-panel">
                                <h6 class="fw-bold mb-2">
                                    <i class="bi bi-link-45deg"></i> URL Marketplace, WhatsApp & Media Sosial
                                </h6>
                                <p class="text-muted small mb-3">
                                    Semua tombol dan ikon terkait di website frontend akan memakai URL di bawah ini.
                                </p>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">URL Tokopedia</label>
                                        <input name="tokopedia_url" type="url" class="form-control" required maxlength="500"
                                            value="<?php echo e($tokopedia_url); ?>" placeholder="https://www.tokopedia.com/namatoko">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">URL Shopee</label>
                                        <input name="shopee_url" type="url" class="form-control" required maxlength="500"
                                            value="<?php echo e($shopee_url); ?>" placeholder="https://shopee.co.id/namatoko">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">URL / Nomor WhatsApp</label>
                                        <input name="whatsapp_url" class="form-control" required maxlength="500"
                                            value="<?php echo e($whatsapp_url); ?>" placeholder="https://wa.me/628123456789 atau 628123456789">
                                        <small class="text-muted">Nomor WhatsApp juga dapat dimasukkan langsung dan akan diubah menjadi URL wa.me.</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">URL Instagram</label>
                                        <input name="instagram_url" type="url" class="form-control" required maxlength="500"
                                            value="<?php echo e($instagram_url); ?>" placeholder="https://www.instagram.com/username/">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">URL Facebook</label>
                                        <input name="facebook_url" type="url" class="form-control" required maxlength="500"
                                            value="<?php echo e($facebook_url); ?>" placeholder="https://www.facebook.com/username">
                                    </div>
                                </div>
                            </div>
                        </div>


                        <div class="col-12">
                            <div class="upload-settings-panel">
                                <h6 class="fw-bold mb-2">
                                    <i class="bi bi-cloud-arrow-up"></i> Upload Settings
                                </h6>
                                <p class="text-muted small mb-3">
                                    Setting ini berlaku untuk upload gambar Product, Content, Media, Banner, Avatar, dan Favicon.
                                </p>

                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Maksimal File Size</label>
                                        <div class="input-group">
                                            <input name="upload_max_filesize_mb" type="number" min="0.1" max="100" step="0.1" class="form-control" value="<?php echo e($upload_max_filesize_mb); ?>">
                                            <span class="input-group-text">MB</span>
                                        </div>
                                        <small class="text-muted">Contoh: 2, 5, 10. Maksimal 100MB.</small>
                                    </div>

                                    <div class="col-md-8">
                                        <label class="form-label">Extension yang Boleh Diupload</label>
                                        <input name="upload_allowed_extensions" class="form-control" value="<?php echo e($upload_allowed_extensions); ?>" placeholder="jpg,jpeg,png,gif,webp,pdf,ico">
                                        <small class="text-muted">
                                            Pisahkan dengan koma. Extension aman yang didukung: jpg, jpeg, png, gif, webp, pdf, ico.
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Setting Jam / Timezone</label>
                            <select name="timezone" class="form-select">
                                <?php foreach($timezones as $tz): ?>
                                    <option value="<?php echo e($tz); ?>" <?php echo $timezone === $tz ? 'selected' : ''; ?>>
                                        <?php echo e($tz); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Date Format</label>
                            <input name="date_format" class="form-control" value="<?php echo e($date_format); ?>">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Time Format</label>
                            <input name="time_format" class="form-control" value="<?php echo e($time_format); ?>">
                        </div>
                    </div>

                    <div class="form-action-bar">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Save Settings
                        </button>
                        <a href="index.php" class="btn btn-light">Back to Dashboard</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card soft-card">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Preview SEO</h5>

                <div class="seo-preview">
                    <div class="seo-preview-title"><?php echo e($meta_title ?: $site_name); ?></div>
                    <div class="seo-preview-url">https://domainanda.com/</div>
                    <p><?php echo e($meta_description ?: 'Meta description website akan tampil di sini.'); ?></p>
                </div>

                <hr>

                <h6 class="fw-bold">Preview Jam</h6>
                <div class="time-preview">
                    <i class="bi bi-clock-history"></i>
                    <div>
                        <strong><?php echo e($previewDate->format($date_format . ' ' . $time_format)); ?></strong><br>
                        <small class="text-muted"><?php echo e($timezone); ?></small>
                    </div>
                </div>

                <hr>

                <h6 class="fw-bold">Cara Pakai di Front Website</h6>
                <p class="text-muted small mb-2">Ambil setting dengan helper:</p>
                <pre class="settings-code">get_website_setting('site_name');
get_website_setting('meta_title');
get_website_setting('favicon');
get_website_setting('upload_max_filesize_mb');
get_website_setting('upload_allowed_extensions');
get_website_setting('tokopedia_url');
get_website_setting('shopee_url');
get_website_setting('whatsapp_url');
get_website_setting('instagram_url');
get_website_setting('facebook_url');</pre>
            </div>
        </div>
    </div>
</div>

</main>
<?php include 'includes/footer.php'; ?>

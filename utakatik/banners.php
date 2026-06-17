<?php
require_once 'auth/check.php';
$page_title = 'Banner Ads Management';

$placements = [
    'homepage' => 'Homepage Header Slider',
    'profile' => 'Profile Header',
    'product' => 'Product Header',
    'contact' => 'Contact Header',
    'sidebar' => 'Sidebar',
    'footer' => 'Footer',
    'popup' => 'Popup'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = $_POST['action'] ?? 'create';

    if ($action === 'delete') {
        $id = clean_int($_POST['id'] ?? 0);

        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM banners WHERE id = ?");
            $stmt->execute([$id]);
            log_activity('delete', 'banners', 'Menghapus banner ID: ' . $id);
        }

        redirect('banners.php');
    }

    try {
        $title = sanitize_plain_text($_POST['title'] ?? '', 200);
        $link_url = sanitize_plain_text($_POST['link_url'] ?? '', 255);
        $placement = sanitize_plain_text($_POST['placement'] ?? 'homepage', 80);
        $status = sanitize_status($_POST['status'] ?? 'active', ['active','inactive'], 'active');
        $start_date = $_POST['start_date'] ?: null;
        $end_date = $_POST['end_date'] ?: null;

        if (!isset($placements[$placement])) {
            $placement = 'homepage';
        }

        if ($title === '') {
            throw new Exception('Title banner tidak boleh kosong.');
        }

        $image = upload_file('image') ?: ($_POST['old_image'] ?? null);

        if ($action === 'update') {
            $id = clean_int($_POST['id'] ?? 0);

            if ($id <= 0) {
                throw new Exception('ID banner tidak valid.');
            }

            $stmt = $pdo->prepare("
                UPDATE banners
                SET title = ?, image = ?, link_url = ?, placement = ?, status = ?, start_date = ?, end_date = ?
                WHERE id = ?
            ");
            $stmt->execute([$title, $image, $link_url, $placement, $status, $start_date, $end_date, $id]);

            log_activity('update', 'banners', 'Mengubah banner: ' . $title);
            redirect('banners.php?updated=1');
        }

        $stmt = $pdo->prepare("
            INSERT INTO banners (title, image, link_url, placement, status, start_date, end_date)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$title, $image, $link_url, $placement, $status, $start_date, $end_date]);

        log_activity('create', 'banners', 'Menambahkan banner: ' . $title);
        redirect('banners.php?created=1');
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$edit = null;

if (!empty($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM banners WHERE id = ?");
    $stmt->execute([clean_int($_GET['edit'])]);
    $edit = $stmt->fetch();
}

$items = $pdo->query("SELECT * FROM banners ORDER BY placement ASC, id DESC")->fetchAll();

include 'includes/header.php';
include 'includes/sidebar.php';
?>
<main class="main-content">
<?php include 'includes/topbar.php'; ?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card soft-card">
            <div class="card-body">
                <h5 class="fw-bold mb-3"><?php echo $edit ? 'Edit Banner' : 'Add Banner'; ?></h5>

                <?php if(!empty($_GET['created'])): ?><div class="alert alert-success">Banner berhasil ditambahkan.</div><?php endif; ?>
                <?php if(!empty($_GET['updated'])): ?><div class="alert alert-success">Banner berhasil diperbarui.</div><?php endif; ?>
                <?php if(!empty($error)): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>

                <form method="post" enctype="multipart/form-data" autocomplete="off">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="action" value="<?php echo $edit ? 'update' : 'create'; ?>">
                    <?php if($edit): ?>
                        <input type="hidden" name="id" value="<?php echo e($edit['id']); ?>">
                        <input type="hidden" name="old_image" value="<?php echo e($edit['image']); ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input name="title" class="form-control" required value="<?php echo e($edit['title'] ?? ''); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Image</label>
                        <input name="image" type="file" class="form-control upload-validate-image" accept=".jpg,.jpeg,.png,.gif,.webp">
                        <small class="text-muted">Upload gambar baru hanya jika ingin mengganti banner.</small>
                    </div>

                    <?php if(!empty($edit['image'])): ?>
                        <div class="mb-3">
                            <label class="form-label">Current Image</label>
                            <div class="banner-current-preview">
                                <img src="<?php echo e($edit['image']); ?>" alt="<?php echo e($edit['title']); ?>">
                                <code><?php echo e($edit['image']); ?></code>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Link URL</label>
                        <input name="link_url" class="form-control" value="<?php echo e($edit['link_url'] ?? ''); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Placement</label>
                        <select name="placement" class="form-select">
                            <?php foreach($placements as $key => $label): ?>
                                <option value="<?php echo e($key); ?>" <?php echo (($edit['placement'] ?? '') === $key) ? 'selected' : ''; ?>>
                                    <?php echo e($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Homepage bisa berisi beberapa banner. Profile/Product/Contact memakai banner aktif terbaru.</small>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">Start</label>
                            <input name="start_date" type="date" class="form-control" value="<?php echo e($edit['start_date'] ?? ''); ?>">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">End</label>
                            <input name="end_date" type="date" class="form-control" value="<?php echo e($edit['end_date'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active" <?php echo (($edit['status'] ?? 'active') === 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo (($edit['status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>

                    <button class="btn btn-primary w-100"><?php echo $edit ? 'Update Banner' : 'Save Banner'; ?></button>
                    <?php if($edit): ?><a href="banners.php" class="btn btn-light w-100 mt-2">Cancel Edit</a><?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card soft-card">
            <div class="card-body">
                <h5 class="fw-bold mb-3">Banner Ads</h5>

                <?php foreach($items as $item): ?>
                    <div class="banner-row">
                        <div class="banner-preview">
                            <?php if($item['image']): ?>
                                <img src="<?php echo e($item['image']); ?>" alt="<?php echo e($item['title']); ?>">
                            <?php else: ?>
                                <i class="bi bi-badge-ad"></i>
                            <?php endif; ?>
                        </div>

                        <div class="flex-grow-1">
                            <h6><?php echo e($item['title']); ?></h6>
                            <p><?php echo e($placements[$item['placement']] ?? $item['placement']); ?> • <?php echo e($item['status']); ?></p>
                            <small><?php echo e($item['link_url']); ?></small>
                        </div>

                        <div class="d-flex gap-2">
                            <a href="banners.php?edit=<?php echo e($item['id']); ?>" class="btn btn-sm btn-outline-primary">Edit</a>

                            <form method="post" onsubmit="return confirm('Delete banner?')">
                                <?php csrf_field(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo e($item['id']); ?>">
                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if(!$items): ?>
                    <div class="alert alert-warning mb-0">Belum ada banner.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</main>
<?php include 'includes/footer.php'; ?>

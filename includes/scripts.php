<?php
if (!function_exists('teakwave_asset_url')) {
    require_once __DIR__ . '/config.php';
}
?>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= htmlspecialchars(teakwave_asset_url('assets/js/script.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
</body>
</html>

<?php
if (!function_exists('teakwave_asset_url')) {
    require_once __DIR__ . '/config.php';
}
?>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= htmlspecialchars(teakwave_asset_url('assets/js/script.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
<script>
(function () {
    'use strict';
    var sent = false;
    function collectPageView() {
        if (sent || document.visibilityState === 'prerender') return;
        sent = true;
        var payload = JSON.stringify({
            page: window.location.pathname + window.location.search,
            title: document.title || '',
            referrer: document.referrer || ''
        });
        var endpoint = 'analytics-collect.php';
        if (navigator.sendBeacon) {
            var blob = new Blob([payload], {type: 'application/json'});
            if (navigator.sendBeacon(endpoint, blob)) return;
        }
        fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            cache: 'no-store',
            keepalive: true,
            headers: {'Content-Type': 'application/json'},
            body: payload
        }).catch(function () {});
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', collectPageView, {once: true});
    } else {
        collectPageView();
    }
})();
</script>
</body>
</html>

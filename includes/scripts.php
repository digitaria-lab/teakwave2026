<?php
if (!function_exists('teakwave_asset_url')) {
    require_once __DIR__ . '/config.php';
}
?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
<script src="<?= teakwave_escape(teakwave_asset_url('assets/js/script.min.js')); ?>" defer></script>
<script>
(function () {
    'use strict';
    var sent = false;
    function collectPageView() {
        if (sent || document.visibilityState === 'prerender') return;
        sent = true;
        var payload = JSON.stringify({
            page: location.pathname + location.search,
            title: document.title || '',
            referrer: document.referrer || ''
        });
        if (navigator.sendBeacon) {
            var blob = new Blob([payload], {type: 'application/json'});
            if (navigator.sendBeacon('analytics-collect.php', blob)) return;
        }
        fetch('analytics-collect.php', {
            method: 'POST', credentials: 'same-origin', cache: 'no-store', keepalive: true,
            headers: {'Content-Type': 'application/json'}, body: payload
        }).catch(function () {});
    }
    function scheduleCollect() {
        if ('requestIdleCallback' in window) {
            requestIdleCallback(collectPageView, {timeout: 2500});
        } else {
            setTimeout(collectPageView, 1200);
        }
    }
    if (document.readyState === 'complete') scheduleCollect();
    else window.addEventListener('load', scheduleCollect, {once: true});
})();
</script>
</body>
</html>

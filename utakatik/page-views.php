<?php
require_once 'auth/check.php';
$page_title = 'Page View Statistics';
ensure_page_view_schema();

$allowedRanges = [7, 30, 90, 365];
$days = filter_input(INPUT_GET, 'days', FILTER_VALIDATE_INT);
$days = in_array($days, $allowedRanges, true) ? $days : 30;
$startDate = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));
$endDate = date('Y-m-d');

function pv_scalar(PDO $pdo, string $sql, array $params = []) {
    $stmt = $pdo->prepare($sql); $stmt->execute($params); return (int) $stmt->fetchColumn();
}

$todayViews = pv_scalar($pdo, "SELECT COUNT(*) FROM page_views WHERE view_date = CURDATE()");
$todayVisitors = pv_scalar($pdo, "SELECT COUNT(DISTINCT visitor_hash) FROM page_views WHERE view_date = CURDATE()");
$periodViews = pv_scalar($pdo, "SELECT COUNT(*) FROM page_views WHERE view_date BETWEEN ? AND ?", [$startDate, $endDate]);
$periodVisitors = pv_scalar($pdo, "SELECT COUNT(DISTINCT visitor_hash) FROM page_views WHERE view_date BETWEEN ? AND ?", [$startDate, $endDate]);

$stmt = $pdo->prepare("SELECT view_date, COUNT(*) AS views, COUNT(DISTINCT visitor_hash) AS visitors
    FROM page_views WHERE view_date BETWEEN ? AND ? GROUP BY view_date ORDER BY view_date");
$stmt->execute([$startDate, $endDate]);
$rawTrend = [];
foreach ($stmt->fetchAll() as $row) $rawTrend[$row['view_date']] = $row;
$labels=[]; $viewSeries=[]; $visitorSeries=[];
for ($i=0; $i<$days; $i++) {
    $date=date('Y-m-d', strtotime($startDate . " +$i days"));
    $labels[]=date('d M', strtotime($date));
    $viewSeries[]=(int)($rawTrend[$date]['views']??0);
    $visitorSeries[]=(int)($rawTrend[$date]['visitors']??0);
}

$stmt=$pdo->prepare("SELECT page_key, MAX(page_path) AS page_path, MAX(NULLIF(page_title,'')) AS page_title, COUNT(*) AS views,
    COUNT(DISTINCT visitor_hash) AS visitors
    FROM page_views WHERE view_date BETWEEN ? AND ?
    GROUP BY page_key ORDER BY views DESC LIMIT 15");
$stmt->execute([$startDate,$endDate]); $topPages=$stmt->fetchAll();

$stmt=$pdo->prepare("SELECT device_type, COUNT(*) AS views FROM page_views WHERE view_date BETWEEN ? AND ? GROUP BY device_type ORDER BY views DESC");
$stmt->execute([$startDate,$endDate]); $devices=$stmt->fetchAll();

$stmt=$pdo->prepare("SELECT COALESCE(NULLIF(referrer_domain,''),'Direct / Internal') AS source, COUNT(*) AS views
    FROM page_views WHERE view_date BETWEEN ? AND ? GROUP BY source ORDER BY views DESC LIMIT 10");
$stmt->execute([$startDate,$endDate]); $referrers=$stmt->fetchAll();

$collectorErrorFile = __DIR__ . '/storage/page-views/tracker-errors.log';
$collectorHasErrors = is_file($collectorErrorFile) && filesize($collectorErrorFile) > 0;

include 'includes/header.php'; include 'includes/sidebar.php';
?>
<main class="main-content">
<?php include 'includes/topbar.php'; ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div><h5 class="fw-bold mb-1">Statistik Pengunjung Unik</h5><p class="text-muted mb-0">Satu pengunjung yang membuka halaman sama berulang kali dalam satu hari tetap dihitung satu view.</p></div>
    <form method="get"><select class="form-select" name="days" onchange="this.form.submit()">
        <?php foreach ([7=>'7 hari',30=>'30 hari',90=>'90 hari',365=>'1 tahun'] as $value=>$label): ?>
        <option value="<?= $value ?>" <?= $days===$value?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?>
    </select></form>
</div>
<div class="row g-3 mb-4">
    <?php foreach ([
        ['View unik hari ini',$todayViews,'bi-eye-fill','primary'],
        ['Pengunjung hari ini',$todayVisitors,'bi-people-fill','success'],
        ["View unik $days hari",$periodViews,'bi-bar-chart-fill','warning'],
        ["Pengunjung $days hari",$periodVisitors,'bi-person-check-fill','info']
    ] as $card): ?>
    <div class="col-6 col-xl-3"><div class="card soft-card h-100"><div class="card-body d-flex align-items-center gap-3">
        <div class="stat-icon bg-<?= e($card[3]) ?>-subtle text-<?= e($card[3]) ?>"><i class="bi <?= e($card[2]) ?>"></i></div>
        <div><small class="text-muted"><?= e($card[0]) ?></small><h3 class="fw-bold mb-0"><?= number_format($card[1],0,',','.') ?></h3></div>
    </div></div></div><?php endforeach; ?>
</div>
<div class="card soft-card mb-4"><div class="card-header bg-white border-0"><h6 class="fw-bold mb-0">Tren harian</h6></div><div class="card-body"><canvas id="pageViewTrend" height="95"></canvas></div></div>
<div class="row g-4">
<div class="col-xl-8"><div class="card soft-card h-100"><div class="card-header bg-white border-0"><h6 class="fw-bold mb-0">Halaman terpopuler</h6></div><div class="card-body"><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Halaman</th><th class="text-end">View unik</th><th class="text-end">Pengunjung</th></tr></thead><tbody>
<?php if (!$topPages): ?><tr><td colspan="3" class="text-center text-muted py-4">Belum ada data.</td></tr><?php endif; ?>
<?php foreach($topPages as $row): ?><tr><td><strong><?= e($row['page_title'] ?: $row['page_path']) ?></strong><small class="d-block text-muted text-break"><?= e($row['page_key']) ?></small></td><td class="text-end fw-bold"><?= number_format($row['views'],0,',','.') ?></td><td class="text-end"><?= number_format($row['visitors'],0,',','.') ?></td></tr><?php endforeach; ?>
</tbody></table></div></div></div></div>
<div class="col-xl-4"><div class="card soft-card mb-4"><div class="card-header bg-white border-0"><h6 class="fw-bold mb-0">Perangkat</h6></div><div class="card-body"><?php if ($devices): ?><canvas id="deviceChart" height="210"></canvas><?php else: ?><div class="text-muted text-center py-5">Belum ada data perangkat.</div><?php endif; ?></div></div>
<div class="card soft-card"><div class="card-header bg-white border-0"><h6 class="fw-bold mb-0">Sumber kunjungan</h6></div><div class="card-body p-0"><div class="list-group list-group-flush"><?php foreach($referrers as $r): ?><div class="list-group-item d-flex justify-content-between gap-2"><span class="text-truncate"><?= e($r['source']) ?></span><strong><?= number_format($r['views'],0,',','.') ?></strong></div><?php endforeach; ?><?php if(!$referrers): ?><div class="p-4 text-muted text-center">Belum ada data.</div><?php endif; ?></div></div></div></div>
</div>
<?php if ($collectorHasErrors): ?><div class="alert alert-warning mt-4"><i class="bi bi-exclamation-triangle me-2"></i>Tracker pernah mengalami kegagalan penulisan. Periksa <code>utakatik/storage/page-views/tracker-errors.log</code>.</div><?php endif; ?><div class="alert alert-light border mt-4 mb-0"><i class="bi bi-shield-check me-2"></i>Tracker menggunakan beacon browser yang tidak di-cache. ID pengunjung disimpan sebagai hash; alamat IP tidak disimpan. Bot umum dan halaman dashboard/API tidak dihitung.</div>
</main>
<script>
document.addEventListener('DOMContentLoaded',function(){
new Chart(document.getElementById('pageViewTrend'),{type:'line',data:{labels:<?= json_encode($labels) ?>,datasets:[{label:'View unik',data:<?= json_encode($viewSeries) ?>,tension:.35,fill:true},{label:'Pengunjung',data:<?= json_encode($visitorSeries) ?>,tension:.35}]},options:{responsive:true,interaction:{mode:'index',intersect:false},scales:{y:{beginAtZero:true,ticks:{precision:0}}}}});
<?php if ($devices): ?>new Chart(document.getElementById('deviceChart'),{type:'doughnut',data:{labels:<?= json_encode(array_column($devices,'device_type')) ?>,datasets:[{data:<?= json_encode(array_map('intval',array_column($devices,'views'))) ?>}]},options:{responsive:true,plugins:{legend:{position:'bottom'}}}});<?php endif; ?>
});
</script>
<?php include 'includes/footer.php'; ?>

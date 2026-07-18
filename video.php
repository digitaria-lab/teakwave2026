<?php
require_once __DIR__ . '/utakatik/config/database.php';
require_once __DIR__ . '/includes/video-public.php';
require_once __DIR__ . '/includes/config.php';

$limit = 10;
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
$search = public_video_search_query($_GET['q'] ?? '');
$pageTitle = $search !== '' ? 'Pencarian Video: ' . $search : 'Video Teakwave';
$activePage = 'video';
$totalItems = 0;
$totalPages = 1;
$videos = [];
$loadError = false;
$settings = public_video_settings($pdo);
$extraHead = '<link href="' . public_video_escape(teakwave_asset_url('assets/css/video.css')) . '" rel="stylesheet">';

try {
    $whereSql = '';
    if ($search !== '') {
        $whereSql = " WHERE LOCATE(:search_title, title) > 0 OR LOCATE(:search_tag, COALESCE(tag, '')) > 0";
    }

    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM videos' . $whereSql);
    if ($search !== '') {
        $countStmt->bindValue(':search_title', $search, PDO::PARAM_STR);
        $countStmt->bindValue(':search_tag', $search, PDO::PARAM_STR);
    }
    $countStmt->execute();
    $totalItems = (int) $countStmt->fetchColumn();

    $totalPages = max(1, (int) ceil($totalItems / $limit));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $limit;

    $listSql = 'SELECT id, title, youtube_id, thumbnail, tag, published_at FROM videos'
        . $whereSql
        . ' ORDER BY published_at DESC, id DESC LIMIT :limit OFFSET :offset';
    $stmt = $pdo->prepare($listSql);
    if ($search !== '') {
        $stmt->bindValue(':search_title', $search, PDO::PARAM_STR);
        $stmt->bindValue(':search_tag', $search, PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $loadError = true;
}

require __DIR__ . '/includes/header.php';
?>
<main class="video-page" id="video-page">
    <section class="video-hero-section">
        <div class="container">
            <div class="video-hero-card reveal">
                <div>
                    <span class="video-kicker"><i class="bi bi-play-btn-fill"></i> Video</span>
                    <h1>Galeri Video <em>Teakwave</em></h1>
                    <p>Temukan informasi produk, tutorial, dan pembaruan terbaru melalui video kami.</p>
                </div>
                <div class="video-hero-icon" aria-hidden="true"><i class="bi bi-play-circle-fill"></i></div>
            </div>
        </div>
    </section>

    <section class="video-list-section pb-5">
        <div class="container">
            <div class="video-list-toolbar reveal">
                <div class="video-list-heading">
                    <span class="video-list-eyebrow"><?= $search !== '' ? 'Hasil pencarian' : 'Koleksi pilihan'; ?></span>
                    <div class="video-list-title-row">
                        <h2><?= $search !== '' ? 'Hasil untuk “' . public_video_escape($search) . '”' : 'Video terbaru'; ?></h2>
                        <?php if (!$loadError): ?>
                            <span class="video-result-count"><i class="bi bi-collection-play"></i> <?= number_format($totalItems, 0, ',', '.'); ?> video</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="video-search-panel">
                    <form class="video-search-form" method="get" action="video.php" role="search">
                        <label class="visually-hidden" for="video-search-input">Cari video berdasarkan judul atau tag</label>
                        <div class="video-search-field">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <input
                                id="video-search-input"
                                name="q"
                                type="search"
                                value="<?= public_video_escape($search); ?>"
                                placeholder="Cari judul atau tag..."
                                maxlength="100"
                                autocomplete="off"
                            >
                        </div>
                        <button class="video-search-submit" type="submit" aria-label="Cari video">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <span>Cari</span>
                        </button>
                        <?php if ($search !== ''): ?>
                            <a class="video-search-reset" href="video.php" aria-label="Hapus pencarian">
                                <i class="bi bi-x-lg" aria-hidden="true"></i>
                                <span>Reset</span>
                            </a>
                        <?php endif; ?>
                    </form>
                    <p class="video-search-hint">
                        <i class="bi bi-info-circle" aria-hidden="true"></i>
                        Cari berdasarkan judul atau tag.
                    </p>
                </div>
            </div>

            <?php if ($loadError): ?>
                <div class="video-public-alert" role="alert">
                    <i class="bi bi-exclamation-circle"></i>
                    <span>Daftar video belum dapat dimuat. Silakan coba kembali.</span>
                </div>
            <?php elseif (!$videos): ?>
                <div class="video-empty-state reveal">
                    <i class="bi <?= $search !== '' ? 'bi-search' : 'bi-camera-video'; ?>"></i>
                    <?php if ($search !== ''): ?>
                        <h2>Video tidak ditemukan</h2>
                        <p>Tidak ada video yang cocok dengan pencarian “<?= public_video_escape($search); ?>”. Coba gunakan kata kunci lain.</p>
                        <a class="video-primary-btn" href="video.php"><i class="bi bi-arrow-counterclockwise"></i> Tampilkan semua video</a>
                    <?php else: ?>
                        <h2>Belum ada video</h2>
                        <p>Video yang ditambahkan melalui dashboard akan muncul di halaman ini.</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="video-public-grid">
                    <?php foreach ($videos as $video): ?>
                        <?php
                        $videoId = (int) $video['id'];
                        $youtubeId = public_video_youtube_id($video['youtube_id'] ?? '');
                        $thumbnail = public_video_thumbnail_url($video);
                        $tags = public_video_tags($video['tag'] ?? '');
                        $publishedAt = public_video_format_datetime($video['published_at'] ?? '', $settings);
                        ?>
                        <article class="video-public-card reveal">
                            <a class="video-card-link" href="<?= public_video_escape(public_video_detail_url($videoId, $video['title'])); ?>" aria-label="Tonton <?= public_video_escape($video['title']); ?>">
                                <div class="video-card-thumbnail">
                                    <img src="<?= public_video_escape($thumbnail); ?>" alt="Thumbnail <?= public_video_escape($video['title']); ?>" loading="lazy" decoding="async">
                                    <?php if ($tags): ?><span class="video-card-category">#<?= public_video_escape($tags[0]); ?></span><?php endif; ?>
                                    <span class="video-card-play" aria-hidden="true"><i class="bi bi-play-fill"></i></span>
                                </div>
                                <div class="video-card-content">
                                    <?php if ($publishedAt !== ''): ?>
                                        <time class="video-card-date" datetime="<?= public_video_escape($video['published_at']); ?>">
                                            <i class="bi bi-calendar3"></i> <?= public_video_escape($publishedAt); ?>
                                        </time>
                                    <?php endif; ?>
                                    <h2><?= public_video_highlight($video['title'], $search); ?></h2>
                                    <?php if ($tags): ?>
                                        <div class="video-tag-list" aria-label="Tag video">
                                            <?php foreach (array_slice($tags, 0, 4) as $tag): ?>
                                                <span>#<?= public_video_escape($tag); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    <span class="video-watch-link">Tonton video <i class="bi bi-arrow-right"></i></span>
                                </div>
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav class="video-pagination" aria-label="Navigasi halaman video">
                        <?php if ($page > 1): ?>
                            <a class="video-page-btn video-page-arrow" href="<?= public_video_escape(public_video_page_url($page - 1, $search)); ?>" aria-label="Halaman sebelumnya"><i class="bi bi-chevron-left"></i></a>
                        <?php endif; ?>

                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        if ($startPage > 1):
                        ?>
                            <a class="video-page-btn" href="<?= public_video_escape(public_video_page_url(1, $search)); ?>">1</a>
                            <?php if ($startPage > 2): ?><span class="video-page-dots">…</span><?php endif; ?>
                        <?php endif; ?>

                        <?php for ($pageNumber = $startPage; $pageNumber <= $endPage; $pageNumber++): ?>
                            <a class="video-page-btn<?= $pageNumber === $page ? ' active' : ''; ?>" href="<?= public_video_escape(public_video_page_url($pageNumber, $search)); ?>"<?= $pageNumber === $page ? ' aria-current="page"' : ''; ?>><?= $pageNumber; ?></a>
                        <?php endfor; ?>

                        <?php if ($endPage < $totalPages): ?>
                            <?php if ($endPage < $totalPages - 1): ?><span class="video-page-dots">…</span><?php endif; ?>
                            <a class="video-page-btn" href="<?= public_video_escape(public_video_page_url($totalPages, $search)); ?>"><?= $totalPages; ?></a>
                        <?php endif; ?>

                        <?php if ($page < $totalPages): ?>
                            <a class="video-page-btn video-page-arrow" href="<?= public_video_escape(public_video_page_url($page + 1, $search)); ?>" aria-label="Halaman berikutnya"><i class="bi bi-chevron-right"></i></a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>
</main>
<?php
$footerClass = 'video-footer-space';
include __DIR__ . '/includes/footer.php';
include __DIR__ . '/includes/floating-actions.php';
include __DIR__ . '/includes/scripts.php';
?>

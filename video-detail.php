<?php
require_once __DIR__ . '/utakatik/config/database.php';
require_once __DIR__ . '/includes/video-public.php';
require_once __DIR__ . '/includes/config.php';

$videoId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
$requestedSlug = trim((string) ($_GET['slug'] ?? ''));
if ($requestedSlug !== '' && !preg_match('/^[a-z0-9-]{1,120}$/', $requestedSlug)) {
    $requestedSlug = '';
}
$video = null;
$relatedVideos = [];
$settings = public_video_settings($pdo);

if ($videoId > 0) {
    try {
        $stmt = $pdo->prepare('SELECT id, title, url, youtube_id, thumbnail, tag, published_at FROM videos WHERE id = ? LIMIT 1');
        $stmt->execute([$videoId]);
        $video = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        if ($video) {
            $relatedStmt = $pdo->prepare('SELECT id, title, youtube_id, thumbnail, published_at FROM videos WHERE id <> ? ORDER BY published_at DESC, id DESC LIMIT 4');
            $relatedStmt->execute([$videoId]);
            $relatedVideos = $relatedStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Throwable $e) {
        $video = null;
    }
}

$validYoutubeId = $video ? public_video_youtube_id($video['youtube_id'] ?? '') : '';
if (!$video || $validYoutubeId === '') {
    http_response_code(404);
    $pageTitle = 'Video Tidak Ditemukan - Teakwave';
    $extraHead = '<meta name="robots" content="noindex,follow">';
} else {
    $canonicalSlug = public_video_slug($video['title']);
    $canonicalRelativeUrl = public_video_detail_url((int) $video['id'], $video['title']);

    // Redirect old query-string URLs and stale slugs to one canonical SEO-friendly URL.
    if ($requestedSlug !== $canonicalSlug) {
        header('Location: ' . $canonicalRelativeUrl, true, 301);
        exit;
    }

    $pageTitle = $video['title'] . ' - Video Teakwave';
    $canonicalUrl = public_video_absolute_url($canonicalRelativeUrl);
    $metaDescription = 'Tonton video ' . $video['title'] . ' dari Teakwave.';
    $metaThumbnail = public_video_thumbnail_url($video);
    if (!preg_match('#^https://#i', $metaThumbnail)) {
        $metaThumbnail = public_video_absolute_url($metaThumbnail);
    }

    $extraHead = '<meta name="description" content="' . public_video_escape($metaDescription) . '">' . PHP_EOL
        . '<link rel="canonical" href="' . public_video_escape($canonicalUrl) . '">' . PHP_EOL
        . '<meta property="og:type" content="video.other">' . PHP_EOL
        . '<meta property="og:title" content="' . public_video_escape($video['title']) . '">' . PHP_EOL
        . '<meta property="og:description" content="' . public_video_escape($metaDescription) . '">' . PHP_EOL
        . '<meta property="og:url" content="' . public_video_escape($canonicalUrl) . '">' . PHP_EOL
        . '<meta property="og:image" content="' . public_video_escape($metaThumbnail) . '">';
}

$extraHead = ($extraHead ?? '') . PHP_EOL . '<link href="' . public_video_escape(teakwave_asset_url('assets/css/video.css')) . '" rel="stylesheet">';

$activePage = 'video';
require __DIR__ . '/includes/header.php';
?>
<main class="video-page video-detail-page">
    <section class="video-detail-section">
        <div class="container">
            <?php if (!$video || $validYoutubeId === ''): ?>
                <div class="video-not-found reveal">
                    <i class="bi bi-camera-video-off"></i>
                    <h1>Video tidak ditemukan</h1>
                    <p>Video mungkin telah dihapus atau alamat yang dibuka tidak valid.</p>
                    <a href="video.php" class="video-primary-btn"><i class="bi bi-arrow-left"></i> Kembali ke daftar video</a>
                </div>
            <?php else: ?>
                <?php
                $tags = public_video_tags($video['tag'] ?? '');
                $publishedAt = public_video_format_datetime($video['published_at'] ?? '', $settings);
                $embedUrl = 'https://www.youtube-nocookie.com/embed/' . rawurlencode($validYoutubeId) . '?rel=0';
                ?>
                <nav class="video-breadcrumb reveal" aria-label="Breadcrumb">
                    <a href="index.php">Home</a>
                    <i class="bi bi-chevron-right"></i>
                    <a href="video.php">Video</a>
                    <i class="bi bi-chevron-right"></i>
                    <span aria-current="page"><?= public_video_escape($video['title']); ?></span>
                </nav>

                <article class="video-detail-card reveal">
                    <div class="video-player-wrap">
                        <iframe
                            src="<?= public_video_escape($embedUrl); ?>"
                            title="<?= public_video_escape($video['title']); ?>"
                            loading="lazy"
                            referrerpolicy="strict-origin-when-cross-origin"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            allowfullscreen></iframe>
                    </div>
                    <div class="video-detail-content">
                        <?php if ($publishedAt !== ''): ?>
                            <time class="video-detail-date" datetime="<?= public_video_escape($video['published_at']); ?>">
                                <i class="bi bi-calendar3"></i> <?= public_video_escape($publishedAt); ?>
                            </time>
                        <?php endif; ?>
                        <h1><?= public_video_escape($video['title']); ?></h1>
                        <?php if ($tags): ?>
                            <div class="video-detail-tags" aria-label="Tag video">
                                <?php foreach ($tags as $tag): ?>
                                    <span>#<?= public_video_escape($tag); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <a class="video-youtube-btn" href="https://www.youtube.com/watch?v=<?= rawurlencode($validYoutubeId); ?>" target="_blank" rel="noopener noreferrer">
                            <i class="bi bi-youtube"></i> Buka di YouTube
                        </a>
                    </div>
                </article>

                <?php if ($relatedVideos): ?>
                    <section class="related-video-section" aria-labelledby="relatedVideoTitle">
                        <div class="related-video-heading reveal">
                            <div>
                                <span>Video lainnya</span>
                                <h2 id="relatedVideoTitle">Lanjutkan menonton</h2>
                            </div>
                            <a href="video.php">Lihat semua <i class="bi bi-arrow-right"></i></a>
                        </div>
                        <div class="related-video-grid">
                            <?php foreach ($relatedVideos as $related): ?>
                                <?php
                                $relatedId = (int) $related['id'];
                                $relatedDate = public_video_format_datetime($related['published_at'] ?? '', $settings);
                                ?>
                                <a class="related-video-card reveal" href="<?= public_video_escape(public_video_detail_url($relatedId, $related['title'])); ?>">
                                    <div class="related-video-thumb">
                                        <img src="<?= public_video_escape(public_video_thumbnail_url($related)); ?>" alt="Thumbnail <?= public_video_escape($related['title']); ?>" loading="lazy" decoding="async">
                                        <i class="bi bi-play-fill" aria-hidden="true"></i>
                                    </div>
                                    <div>
                                        <h3><?= public_video_escape($related['title']); ?></h3>
                                        <?php if ($relatedDate !== ''): ?><time><?= public_video_escape($relatedDate); ?></time><?php endif; ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </section>
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

<?php

if (!function_exists('public_video_escape')) {
    function public_video_escape($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('public_video_youtube_id')) {
    function public_video_youtube_id($value) {
        $value = trim((string) $value);
        return preg_match('/^[A-Za-z0-9_-]{11}$/', $value) ? $value : '';
    }
}

if (!function_exists('public_video_asset_url')) {
    function public_video_asset_url($path) {
        $path = trim(str_replace('\\', '/', (string) $path));

        if ($path === '') {
            return '';
        }

        if (preg_match('#^https://#i', $path)) {
            return $path;
        }

        $path = preg_replace('#^(?:\.\./)+#', '', $path);
        $path = ltrim($path, '/');

        if (str_starts_with($path, 'utakatik/assets/uploads/')) {
            return 'uploads/' . rawurlencode(basename($path));
        }

        if (str_starts_with($path, 'assets/uploads/')) {
            return 'uploads/' . rawurlencode(basename($path));
        }

        if (str_starts_with($path, 'uploads/')) {
            return 'uploads/' . rawurlencode(basename($path));
        }

        return '';
    }
}

if (!function_exists('public_video_thumbnail_url')) {
    function public_video_thumbnail_url(array $video) {
        $custom = public_video_asset_url($video['thumbnail'] ?? '');
        if ($custom !== '') {
            return $custom;
        }

        $youtubeId = public_video_youtube_id($video['youtube_id'] ?? '');
        return $youtubeId !== ''
            ? 'https://i.ytimg.com/vi/' . rawurlencode($youtubeId) . '/hqdefault.jpg'
            : 'assets/img/banner-profil.webp';
    }
}

if (!function_exists('public_video_tags')) {
    function public_video_tags($value) {
        $items = preg_split('/[,;]+/u', (string) $value);
        $tags = [];

        foreach ($items as $item) {
            $item = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $item)));
            if ($item === '') {
                continue;
            }

            $tags[] = function_exists('mb_substr') ? mb_substr($item, 0, 40, 'UTF-8') : substr($item, 0, 40);
            if (count($tags) >= 20) {
                break;
            }
        }

        return $tags;
    }
}

if (!function_exists('public_video_settings')) {
    function public_video_settings(PDO $pdo) {
        $settings = [
            'timezone' => 'Asia/Jakarta',
            'date_format' => 'd M Y',
            'time_format' => 'H:i'
        ];

        try {
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM website_settings WHERE setting_key IN ('timezone', 'date_format', 'time_format')");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                if (array_key_exists($row['setting_key'], $settings) && trim((string) $row['setting_value']) !== '') {
                    $settings[$row['setting_key']] = trim((string) $row['setting_value']);
                }
            }
        } catch (Throwable $e) {
            // Keep safe defaults when the settings table is not available.
        }

        try {
            new DateTimeZone($settings['timezone']);
        } catch (Throwable $e) {
            $settings['timezone'] = 'Asia/Jakarta';
        }

        return $settings;
    }
}

if (!function_exists('public_video_format_datetime')) {
    function public_video_format_datetime($value, array $settings) {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        try {
            $timezone = new DateTimeZone($settings['timezone'] ?? 'Asia/Jakarta');
            $date = new DateTimeImmutable($value, $timezone);
            $format = trim((string) ($settings['date_format'] ?? 'd M Y')) . ', ' . trim((string) ($settings['time_format'] ?? 'H:i'));
            return $date->format($format);
        } catch (Throwable $e) {
            return '';
        }
    }
}

if (!function_exists('public_video_search_query')) {
    function public_video_search_query($value) {
        if (is_array($value) || is_object($value)) {
            return '';
        }

        $value = trim(strip_tags((string) $value));
        $value = preg_replace('/\s+/u', ' ', $value);
        if (!is_string($value)) {
            return '';
        }

        return function_exists('mb_substr')
            ? mb_substr($value, 0, 100, 'UTF-8')
            : substr($value, 0, 100);
    }
}

if (!function_exists('public_video_highlight')) {
    /**
     * Highlight an exact search phrase while keeping every title character escaped.
     * Only the generated <mark> wrapper is trusted HTML.
     */
    function public_video_highlight($value, $query) {
        $text = (string) $value;
        $query = public_video_search_query($query);

        if ($text === '' || $query === '') {
            return public_video_escape($text);
        }

        $pattern = '/(' . preg_quote($query, '/') . ')/iu';
        $parts = @preg_split($pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE);

        if (!is_array($parts) || count($parts) < 2) {
            return public_video_escape($text);
        }

        $output = '';
        foreach ($parts as $index => $part) {
            if ($part === '') {
                continue;
            }

            $escapedPart = public_video_escape($part);
            $output .= ($index % 2 === 1)
                ? '<mark class="video-search-highlight">' . $escapedPart . '</mark>'
                : $escapedPart;
        }

        return $output;
    }
}

if (!function_exists('public_video_page_url')) {
    function public_video_page_url($page, $search = '') {
        $query = ['page' => max(1, (int) $page)];
        $search = public_video_search_query($search);

        if ($search !== '') {
            $query['q'] = $search;
        }

        return 'video' . ($query ? '?' . http_build_query($query) : '');
    }
}

if (!function_exists('public_video_slug')) {
    function public_video_slug($value) {
        $value = trim(strip_tags((string) $value));
        if ($value === '') {
            return 'video';
        }

        if (function_exists('iconv')) {
            $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if (is_string($transliterated) && $transliterated !== '') {
                $value = $transliterated;
            }
        }

        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value);
        $value = trim((string) $value, '-');
        $value = preg_replace('/-+/', '-', $value);

        if ($value === '') {
            return 'video';
        }

        return substr($value, 0, 100);
    }
}

if (!function_exists('public_video_detail_url')) {
    function public_video_detail_url($id, $title) {
        $id = max(1, (int) $id);
        return 'video-' . $id . '-' . public_video_slug($title) . '.html';
    }
}

if (!function_exists('public_video_absolute_url')) {
    function public_video_absolute_url($relativeUrl) {
        $relativeUrl = ltrim((string) $relativeUrl, '/');
        $host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));

        if ($host === '' || !preg_match('/^[A-Za-z0-9.-]+(?::[0-9]{1,5})?$/', $host)) {
            return $relativeUrl;
        }

        $https = (string) ($_SERVER['HTTPS'] ?? '');
        $scheme = ($https !== '' && strtolower($https) !== 'off') ? 'https' : 'http';
        $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/'));
        $basePath = trim(str_replace('\\', '/', dirname($scriptName)), '/.');
        $path = ($basePath !== '' ? '/' . $basePath : '') . '/' . $relativeUrl;

        return $scheme . '://' . $host . $path;
    }
}

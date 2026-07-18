<?php
/**
 * Privacy-friendly page-view analytics.
 * One visitor + one canonical page + one calendar day = one unique view.
 */

if (!function_exists('teakwave_page_view_error_log')) {
    function teakwave_page_view_error_log(Throwable $error): void
    {
        $directory = __DIR__ . '/../utakatik/storage/page-views';
        if (!is_dir($directory)) {
            @mkdir($directory, 0750, true);
        }
        if (is_dir($directory) && is_writable($directory)) {
            @file_put_contents(
                $directory . '/tracker-errors.log',
                '[' . date('c') . '] ' . $error->getMessage() . PHP_EOL,
                FILE_APPEND | LOCK_EX
            );
        }
    }
}

if (!function_exists('teakwave_ensure_page_view_schema')) {
    function teakwave_ensure_page_view_schema(PDO $pdo): void
    {
        static $checkedConnections = [];
        $connectionId = spl_object_id($pdo);
        if (isset($checkedConnections[$connectionId])) {
            return;
        }

        $pdo->exec("CREATE TABLE IF NOT EXISTS page_views (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            view_date DATE NOT NULL,
            visitor_hash CHAR(64) NOT NULL,
            page_hash CHAR(64) NOT NULL,
            page_key VARCHAR(700) NOT NULL,
            page_path VARCHAR(500) NOT NULL,
            page_title VARCHAR(255) DEFAULT NULL,
            referrer_domain VARCHAR(255) DEFAULT NULL,
            device_type VARCHAR(20) NOT NULL DEFAULT 'desktop',
            browser VARCHAR(40) DEFAULT NULL,
            first_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_daily_page_visitor (view_date, visitor_hash, page_hash),
            KEY view_date_index (view_date),
            KEY page_path_index (page_path(191)),
            KEY visitor_hash_index (visitor_hash),
            KEY device_type_index (device_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $checkedConnections[$connectionId] = true;
    }
}

if (!function_exists('teakwave_is_bot')) {
    function teakwave_is_bot(string $ua): bool
    {
        return $ua === '' || (bool) preg_match(
            '/bot|crawl|spider|slurp|facebookexternalhit|telegrambot|headless|lighthouse|pagespeed|uptime|monitoring/i',
            $ua
        );
    }
}

if (!function_exists('teakwave_device_type')) {
    function teakwave_device_type(string $ua): string
    {
        if (preg_match('/ipad|tablet|kindle|silk|playbook/i', $ua)) {
            return 'tablet';
        }
        if (preg_match('/mobile|android|iphone|ipod|blackberry|opera mini|iemobile/i', $ua)) {
            return 'mobile';
        }
        return 'desktop';
    }
}

if (!function_exists('teakwave_browser_name')) {
    function teakwave_browser_name(string $ua): string
    {
        foreach ([
            'Edge' => '/Edg\//i',
            'Opera' => '/OPR\//i',
            'Chrome' => '/Chrome\//i',
            'Firefox' => '/Firefox\//i',
            'Safari' => '/Safari\//i',
        ] as $name => $pattern) {
            if (preg_match($pattern, $ua)) {
                return $name;
            }
        }
        return 'Other';
    }
}

if (!function_exists('teakwave_normalize_page_key')) {
    function teakwave_normalize_page_key(string $requestUri): array
    {
        $parts = parse_url($requestUri);
        $path = '/' . ltrim((string) ($parts['path'] ?? '/'), '/');
        $path = preg_replace('#/+#', '/', $path) ?: '/';
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        $query = [];
        parse_str((string) ($parts['query'] ?? ''), $query);
        foreach (array_keys($query) as $key) {
            if (preg_match('/^(utm_|fbclid$|gclid$|msclkid$|ref$)/i', (string) $key)) {
                unset($query[$key]);
            }
        }
        ksort($query);
        $queryString = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        $pageKey = $path . ($queryString !== '' ? '?' . $queryString : '');

        return [substr($pageKey, 0, 700), substr($path, 0, 500)];
    }
}

if (!function_exists('teakwave_page_key')) {
    function teakwave_page_key(): array
    {
        return teakwave_normalize_page_key((string) ($_SERVER['REQUEST_URI'] ?? '/'));
    }
}

if (!function_exists('teakwave_visitor_token')) {
    function teakwave_visitor_token(): string
    {
        $cookieName = 'tw_visitor';
        $visitorToken = (string) ($_COOKIE[$cookieName] ?? '');
        if (preg_match('/^[a-f0-9]{64}$/', $visitorToken)) {
            return $visitorToken;
        }

        $visitorToken = bin2hex(random_bytes(32));
        $_COOKIE[$cookieName] = $visitorToken;
        if (!headers_sent()) {
            setcookie($cookieName, $visitorToken, [
                'expires' => time() + 31536000,
                'path' => '/',
                'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
        return $visitorToken;
    }
}

if (!function_exists('teakwave_external_referrer_domain')) {
    function teakwave_external_referrer_domain(string $referrer): ?string
    {
        if ($referrer === '') {
            return null;
        }
        $host = strtolower((string) parse_url($referrer, PHP_URL_HOST));
        $currentHost = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        $currentHost = preg_replace('/:\d+$/', '', $currentHost) ?: $currentHost;
        if ($host === '' || $host === $currentHost) {
            return null;
        }
        return substr($host, 0, 255);
    }
}

if (!function_exists('teakwave_store_page_view')) {
    function teakwave_store_page_view(
        PDO $pdo,
        string $requestUri,
        string $pageTitle,
        string $referrer,
        string $userAgent
    ): bool {
        try {
            [$pageKey, $pagePath] = teakwave_normalize_page_key($requestUri);
            if (preg_match('#^/(utakatik|api)(/|$)#i', $pagePath)) {
                return false;
            }
            if (in_array($pagePath, ['/analytics-collect.php'], true)) {
                return false;
            }
            if (teakwave_is_bot($userAgent)) {
                return false;
            }

            teakwave_ensure_page_view_schema($pdo);
            $visitorHash = hash('sha256', teakwave_visitor_token());
            $stmt = $pdo->prepare("INSERT INTO page_views
                (view_date, visitor_hash, page_hash, page_key, page_path, page_title, referrer_domain, device_type, browser, first_seen_at, last_seen_at)
                VALUES (CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    page_title = VALUES(page_title),
                    referrer_domain = COALESCE(page_views.referrer_domain, VALUES(referrer_domain)),
                    device_type = VALUES(device_type),
                    browser = VALUES(browser),
                    last_seen_at = NOW()");
            return $stmt->execute([
                $visitorHash,
                hash('sha256', $pageKey),
                $pageKey,
                $pagePath,
                substr(trim(strip_tags($pageTitle)), 0, 255),
                teakwave_external_referrer_domain($referrer),
                teakwave_device_type($userAgent),
                teakwave_browser_name($userAgent),
            ]);
        } catch (Throwable $error) {
            teakwave_page_view_error_log($error);
            return false;
        }
    }
}

if (!function_exists('teakwave_track_page_view')) {
    function teakwave_track_page_view(PDO $pdo, string $pageTitle = ''): void
    {
        if (PHP_SAPI === 'cli' || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            return;
        }
        teakwave_store_page_view(
            $pdo,
            (string) ($_SERVER['REQUEST_URI'] ?? '/'),
            $pageTitle,
            (string) ($_SERVER['HTTP_REFERER'] ?? ''),
            substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 1000)
        );
    }
}

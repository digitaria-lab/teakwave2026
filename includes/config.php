<?php
$siteName = 'Teakwave';
$defaultWhatsappUrl = 'https://wa.me/6282112345678';


function teakwave_asset_url($path) {
    $cleanPath = ltrim((string) $path, '/');
    $filePath = __DIR__ . '/../' . $cleanPath;
    $version = file_exists($filePath) ? filemtime($filePath) : time();

    return $cleanPath . '?v=' . $version;
}
?>

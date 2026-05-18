<?php

/**
 * Trindade Router
 *
 * Serve React build from studio-ui/dist/ for /studio/* paths.
 * Everything else goes through index.php.
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Static files from React build
if (str_starts_with($uri, '/studio/')) {
    $file = __DIR__ . '/../studio-ui/dist' . substr($uri, 7); // remove /studio
    if (file_exists($file) && !is_dir($file)) {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $mimes = [
            'js' => 'application/javascript',
            'css' => 'text/css',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'ico' => 'image/x-icon',
            'woff2' => 'font/woff2',
            'woff' => 'font/woff',
        ];

        header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
        header('Cache-Control: public, max-age=31536000');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        return true;
    }
}

// Everything else: route through index.php
return false;

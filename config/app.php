<?php
if (!function_exists('dtd_normalize_path')) {
    function dtd_normalize_path($path)
    {
        return str_replace('\\', '/', (string)$path);
    }
}

if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', realpath(__DIR__ . '/..'));
}

if (!defined('BASE_URL')) {
    $projectRoot = dtd_normalize_path(PROJECT_ROOT ?: dirname(__DIR__));
    $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : null;
    $documentRoot = $documentRoot ? dtd_normalize_path($documentRoot) : '';

    $basePath = '';
    if ($documentRoot !== '' && strpos($projectRoot, $documentRoot) === 0) {
        $basePath = substr($projectRoot, strlen($documentRoot));
    }

    $basePath = '/' . trim((string)$basePath, '/');
    if ($basePath === '/') {
        $basePath = '';
    }

    define('BASE_URL', $basePath);
}

if (!defined('BASE_URL_ABSOLUTE')) {
    $scheme = 'http';
    if (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (isset($_SERVER['SERVER_PORT']) && (string)$_SERVER['SERVER_PORT'] === '443')
    ) {
        $scheme = 'https';
    }

    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('BASE_URL_ABSOLUTE', $scheme . '://' . $host . (BASE_URL !== '' ? BASE_URL : ''));
}

if (!function_exists('app_url')) {
    function app_url($path = '')
    {
        $path = ltrim((string)$path, '/');
        $prefix = BASE_URL !== '' ? BASE_URL : '';

        if ($path === '') {
            return $prefix !== '' ? $prefix . '/' : '/';
        }

        return $prefix . '/' . $path;
    }
}

if (!function_exists('asset_url')) {
    function asset_url($path = '')
    {
        return app_url($path);
    }
}

if (!function_exists('redirect_to')) {
    function redirect_to($path = '')
    {
        header('Location: ' . app_url($path));
        exit;
    }
}

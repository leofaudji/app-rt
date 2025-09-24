<?php
require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/functions.php';

// Define project root path for reliable file includes.
if (!defined('PROJECT_ROOT')) {
    define('PROJECT_ROOT', dirname(__DIR__));
}

// Define base path dynamically so it's available globally.
if (!defined('BASE_PATH')) {
    // This is a more robust way to determine the base path,
    // as it doesn't rely on SCRIPT_NAME which can be inconsistent across server configurations.
    // It calculates the path relative to the document root.
    $projectRoot = str_replace('\\', '/', PROJECT_ROOT);
    // Ensure DOCUMENT_ROOT has no trailing slash for consistency.
    $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');

    $basePath = str_replace($docRoot, '', $projectRoot);

    define('BASE_PATH', rtrim($basePath, '/')); // Should correctly resolve to "/app-rt"
}

// Load environment variables from the root directory
try {
    Config::load(PROJECT_ROOT . '/.env');
} catch (\Exception $e) {
    die('Error: Could not load configuration. Make sure a .env file exists in the root directory. Details: ' . $e->getMessage());
}
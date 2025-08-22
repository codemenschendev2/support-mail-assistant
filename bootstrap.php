<?php

declare(strict_types=1);

/**
 * Bootstrap file for Support Mail Assistant
 * Initialize application, load dependencies, and configure environment
 */

// Load Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Load all helper files
require_once __DIR__ . '/helpers/Env.php';
require_once __DIR__ . '/helpers/Html.php';
require_once __DIR__ . '/helpers/Response.php';
require_once __DIR__ . '/helpers/GmailMime.php';
require_once __DIR__ . '/helpers/Knowledge.php';

// Load Google client
require_once __DIR__ . '/google_client.php';

// Load environment variables
Env::load(__DIR__ . '/.env');

// Set timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

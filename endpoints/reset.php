<?php

declare(strict_types=1);

/**
 * Reset Application Endpoint
 * Deletes OAuth tokens and resets application to initial state
 */

require_once __DIR__ . '/../bootstrap.php';

use Env;

// Check admin shared key
$adminKey = Env::get('ADMIN_SHARED_KEY');
if (!$adminKey || $_GET['key'] !== $adminKey) {
    http_response_code(403);
    echo "Error: Access denied";
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Error: Method not allowed. Use POST.";
    exit;
}

try {
    $deletedFiles = [];
    $errors = [];

    // Delete OAuth token file
    $tokenPath = __DIR__ . '/../credentials/token.json';
    if (file_exists($tokenPath)) {
        if (unlink($tokenPath)) {
            $deletedFiles[] = 'OAuth token';
        } else {
            $errors[] = 'Failed to delete OAuth token';
        }
    }

    // Delete OAuth client credentials (optional - user can keep this)
    $clientPath = __DIR__ . '/../credentials/oauth-client.json';
    if (file_exists($clientPath)) {
        if (unlink($clientPath)) {
            $deletedFiles[] = 'OAuth client credentials';
        } else {
            $errors[] = 'Failed to delete OAuth client credentials';
        }
    }

    // Clear any session data
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }

    // Prepare response
    $response = [
        'success' => true,
        'message' => 'Application reset successfully',
        'deleted_files' => $deletedFiles,
        'errors' => $errors,
        'timestamp' => date('Y-m-d H:i:s')
    ];

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    $response = [
        'success' => false,
        'message' => 'Error resetting application: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ];

    header('Content-Type: application/json');
    echo json_encode($response, JSON_PRETTY_PRINT);
}

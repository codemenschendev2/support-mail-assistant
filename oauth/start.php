<?php

declare(strict_types=1);

/**
 * OAuth Start Page
 * Initiates Google OAuth2 authentication flow
 */

require_once __DIR__ . '/../bootstrap.php';

// Check if user already has valid token
$tokenPath = __DIR__ . '/../credentials/token.json';

if (file_exists($tokenPath)) {
    $tokenData = json_decode(file_get_contents($tokenPath), true);

    if ($tokenData && isset($tokenData['access_token'])) {
        // Check if token is not expired
        if (
            !isset($tokenData['expires_in']) || !isset($tokenData['created']) ||
            (time() < ($tokenData['created'] + $tokenData['expires_in']))
        ) {

            // Redirect to dashboard if already authenticated
            $adminKey = Env::get('ADMIN_SHARED_KEY');
            header("Location: ../views/dashboard.php?key=" . urlencode($adminKey));
            exit;
        }
    }
}

// No valid token found, start OAuth flow
try {
    $client = makeGoogleClient();

    // Generate authorization URL
    $authUrl = $client->createAuthUrl();

    // Redirect to Google authorization page
    header("Location: $authUrl");
    exit;
} catch (Exception $e) {
    echo "Lỗi khởi tạo OAuth: " . htmlspecialchars($e->getMessage());
}

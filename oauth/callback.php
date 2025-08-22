<?php

declare(strict_types=1);

/**
 * OAuth Callback Page
 * Handles Google OAuth2 callback and token exchange
 */

require_once __DIR__ . '/../bootstrap.php';

try {
    // Check if authorization code is received
    if (!isset($_GET['code'])) {
        echo "Lỗi: Không nhận được mã xác thực";
        exit;
    }

    $client = makeGoogleClient();

    // Exchange authorization code for access token
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

    if (isset($token['error'])) {
        echo "Lỗi OAuth: " . htmlspecialchars($token['error_description'] ?? $token['error']);
        exit;
    }

    // Prepare token data for storage
    $tokenData = [
        'access_token' => $token['access_token'],
        'refresh_token' => $token['refresh_token'] ?? null,
        'expires_in' => $token['expires_in'] ?? null,
        'created' => time()
    ];

    // Save tokens to file
    $tokenPath = __DIR__ . '/../credentials/token.json';
    $result = file_put_contents($tokenPath, json_encode($tokenData, JSON_PRETTY_PRINT));

    if ($result === false) {
        echo "Lỗi: Không thể lưu token";
        exit;
    }

    // Success message
    echo "Xác thực thành công";
} catch (Exception $e) {
    echo "Lỗi xử lý OAuth callback: " . htmlspecialchars($e->getMessage());
}

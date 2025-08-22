<?php

declare(strict_types=1);

/**
 * Send Draft Endpoint
 * Sends a draft email through Gmail API
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../google_client.php';

use Google\Service\Gmail;

// Check admin shared key
$adminKey = Env::get('ADMIN_SHARED_KEY');
if (!$adminKey || $_GET['key'] !== $adminKey) {
    http_response_code(403);
    echo "Lỗi: Không có quyền truy cập";
    exit;
}

// Check if draft ID is provided
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo "Lỗi: Thiếu ID của draft";
    exit;
}

$draftId = $_GET['id'];

try {
    // Get Google client and Gmail service
    $client = makeGoogleClient();
    $gmailService = new Gmail($client);

    // Send the draft
    $sentMessage = $gmailService->users_drafts->send('me', new \Google\Service\Gmail\Draft([
        'id' => $draftId
    ]));

    // Delete the draft after sending
    try {
        $gmailService->users_drafts->delete('me', $draftId);
    } catch (Exception $e) {
        // Log deletion error but don't fail the send operation
        error_log('Failed to delete draft after sending: ' . $e->getMessage());
    }

    // Success message
    echo "<h2>Kết quả gửi email</h2>";
    echo "<p style='color: green; font-weight: bold;'>Đã gửi thành công!</p>";
    echo "<p><strong>Message ID:</strong> " . htmlspecialchars($sentMessage->getId()) . "</p>";
    echo "<p><strong>Thread ID:</strong> " . htmlspecialchars($sentMessage->getThreadId()) . "</p>";

    // Add link back to drafts list
    echo "<br><a href='list_drafts.php?key=$adminKey' style='color: blue; text-decoration: none;'>← Quay lại danh sách Draft</a>";
} catch (Exception $e) {
    http_response_code(500);
    echo "<h2>Lỗi gửi email</h2>";
    echo "<p style='color: red;'>Lỗi: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<br><a href='list_drafts.php?key=$adminKey' style='color: blue; text-decoration: none;'>← Quay lại danh sách Draft</a>";
}

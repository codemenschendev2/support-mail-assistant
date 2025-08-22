<?php

declare(strict_types=1);

/**
 * List Drafts Endpoint
 * Retrieves and displays list of draft emails from Gmail
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

try {
    // Get Google client and Gmail service
    $client = makeGoogleClient();
    $gmailService = new Gmail($client);

    // Get drafts list (20 most recent)
    $drafts = $gmailService->users_drafts->listUsersDrafts('me', [
        'maxResults' => 20
    ]);

    // Debug: Hiển thị số lượng draft
    $draftCount = count($drafts->getDrafts());
    echo "<p>Total drafts found: $draftCount</p>";

    if (empty($drafts->getDrafts())) {
        echo "<h2>Danh sách Draft</h2>";
        echo "<p>Không có draft email nào.</p>";
        exit;
    }

    echo "<h2>Danh sách Draft (20 gần nhất)</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th style='padding: 8px;'>To</th>";
    echo "<th style='padding: 8px;'>Subject</th>";
    echo "<th style='padding: 8px;'>Body</th>";
    echo "<th style='padding: 8px;'>Action</th>";
    echo "</tr>";

    foreach ($drafts->getDrafts() as $draft) {
        $draftId = $draft->getId();

        // Debug: Hiển thị thông tin draft
        echo "<p><strong>Draft ID:</strong> " . $draftId . "</p>";

        try {
            // Lấy message chi tiết từ draft ID
            $draftDetail = $gmailService->users_drafts->get('me', $draftId);
            $message = $draftDetail->getMessage();

            echo "<p><strong>Draft Detail:</strong> " . ($draftDetail ? 'OK' : 'NULL') . "</p>";
            echo "<p><strong>Message:</strong> " . ($message ? 'OK' : 'NULL') . "</p>";

            // Kiểm tra message và payload
            if (!$message || !$message->getPayload()) {
                echo "<p style='color: red;'>Message hoặc Payload null - bỏ qua</p>";
                continue; // Bỏ qua draft không hợp lệ
            }

            $payload = $message->getPayload();
            echo "<p><strong>Payload MIME Type:</strong> " . $payload->getMimeType() . "</p>";

            $headers = $payload->getHeaders();

            // Extract headers
            $to = '';
            $subject = '';

            foreach ($headers as $header) {
                $name = strtolower($header->getName());
                $value = $header->getValue();

                switch ($name) {
                    case 'to':
                        $to = $value;
                        break;
                    case 'subject':
                        $subject = $value;
                        break;
                }
            }

            // Get message body
            $body = '';

            if ($payload->getBody() && $payload->getBody()->getData()) {
                $body = $payload->getBody()->getData();
                echo "<p><strong>Body từ getBody():</strong> " . substr($body, 0, 50) . "...</p>";
            } elseif ($payload->getParts()) {
                echo "<p><strong>Số parts:</strong> " . count($payload->getParts()) . "</p>";
                foreach ($payload->getParts() as $index => $part) {
                    echo "<p><strong>Part $index:</strong> " . $part->getMimeType() . "</p>";
                    if ($part->getMimeType() === 'text/plain' && $part->getBody() && $part->getBody()->getData()) {
                        $body = $part->getBody()->getData();
                        echo "<p><strong>Body từ part $index:</strong> " . substr($body, 0, 50) . "...</p>";
                        break;
                    }
                }
            }

            // Decode body if it's base64url encoded
            if ($body) {
                // Sử dụng base64url_decode thay vì base64_decode
                $decodedBody = base64url_decode($body);
                echo "<p><strong>Decoded body:</strong> " . substr($decodedBody, 0, 100) . "...</p>";

                // Truncate long body
                if (strlen($decodedBody) > 100) {
                    $body = substr($decodedBody, 0, 100) . '...';
                } else {
                    $body = $decodedBody;
                }
            } else {
                echo "<p style='color: red;'>Không tìm thấy body</p>";
            }

            // Escape HTML
            $to = htmlspecialchars($to);
            $subject = htmlspecialchars($subject);
            $body = htmlspecialchars($body);

            echo "<tr>";
            echo "<td style='padding: 8px;'>$to</td>";
            echo "<td style='padding: 8px;'>$subject</td>";
            echo "<td style='padding: 8px;'>$body</td>";
            echo "<td style='padding: 8px;'>";
            echo "<a href='send_draft.php?id=" . $draftId . "&key=$adminKey' style='color: blue; text-decoration: none;'>Send</a>";
            echo "</td>";
            echo "</tr>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>Lỗi lấy draft detail: " . htmlspecialchars($e->getMessage()) . "</p>";
        }

        // Chỉ xử lý 1 draft để debug
        break;
    }

    echo "</table>";
} catch (Exception $e) {
    http_response_code(500);
    echo "Lỗi: " . htmlspecialchars($e->getMessage());
}

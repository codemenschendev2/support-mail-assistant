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
        $message = $draft->getMessage();
        $headers = $message->getPayload()->getHeaders();

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
        if ($message->getPayload()->getBody()) {
            $body = $message->getPayload()->getBody()->getData();
        } elseif ($message->getPayload()->getParts()) {
            foreach ($message->getPayload()->getParts() as $part) {
                if ($part->getMimeType() === 'text/plain') {
                    $body = $part->getBody()->getData();
                    break;
                }
            }
        }

        // Decode body if it's base64
        if ($body) {
            $body = base64_decode($body);
            // Truncate long body
            if (strlen($body) > 100) {
                $body = substr($body, 0, 100) . '...';
            }
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
        echo "<a href='send_draft.php?id=" . $draft->getId() . "&key=$adminKey' style='color: blue; text-decoration: none;'>Send</a>";
        echo "</td>";
        echo "</tr>";
    }

    echo "</table>";
} catch (Exception $e) {
    http_response_code(500);
    echo "Lỗi: " . htmlspecialchars($e->getMessage());
}

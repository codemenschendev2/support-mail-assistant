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
    echo "Error: Access denied";
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
        echo "<h2>Draft List</h2>";
        echo "<p>No draft emails found.</p>";
        exit;
    }

    echo "<h2>Draft List (20 most recent)</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #f0f0f0;'>";
    echo "<th style='padding: 8px;'>To</th>";
    echo "<th style='padding: 8px;'>Subject</th>";
    echo "<th style='padding: 8px;'>Body</th>";
    echo "<th style='padding: 8px;'>Action</th>";
    echo "</tr>";

    foreach ($drafts->getDrafts() as $draft) {
        $draftId = $draft->getId();



        try {
            // Get detailed message from draft ID
            $draftDetail = $gmailService->users_drafts->get('me', $draftId);
            $message = $draftDetail->getMessage();



            // Check message and payload
            if (!$message || !$message->getPayload()) {
                continue; // Skip invalid draft
            }

            $payload = $message->getPayload();

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
            } elseif ($payload->getParts()) {
                foreach ($payload->getParts() as $index => $part) {
                    if ($part->getMimeType() === 'text/plain' && $part->getBody() && $part->getBody()->getData()) {
                        $body = $part->getBody()->getData();
                        break;
                    }
                }
            }

            // Decode body if it's base64url encoded
            if ($body) {
                // Sử dụng base64url_decode thay vì base64_decode
                $decodedBody = base64url_decode($body);

                // Truncate long body
                if (strlen($decodedBody) > 100) {
                    $body = substr($decodedBody, 0, 100) . '...';
                } else {
                    $body = $decodedBody;
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
            echo "<a href='send_draft.php?id=" . $draftId . "&key=$adminKey' style='color: blue; text-decoration: none;'>Send</a>";
            echo "</td>";
            echo "</tr>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>Error getting draft detail: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }

    echo "</table>";
} catch (Exception $e) {
    http_response_code(500);
    echo "Error: " . htmlspecialchars($e->getMessage());
}

<?php

declare(strict_types=1);

/**
 * Check and Draft Endpoint
 * Analyzes inbox emails and creates draft responses using knowledge base
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
    // Load Knowledge helper
    $knowledge = new Knowledge();

    // Get allowed senders
    $allowedSenders = $knowledge->getAllowedSenders();
    if (empty($allowedSenders)) {
        throw new RuntimeException('No allowed email list configured');
    }

    // Build Gmail query for allowed senders - same as get_unread_emails.php
    $fromQuery = implode(' OR ', array_map(function ($email) {
        return 'from:' . trim($email);
    }, $allowedSenders));

    // Use same query as get_unread_emails.php: only unread emails from today
    $today = date('Y/m/d');
    $gmailQuery = "in:inbox is:unread after:$today ($fromQuery)";

    // Get Google client and Gmail service
    $client = makeGoogleClient();
    $gmailService = new Gmail($client);

    // Search for emails matching query
    $messages = $gmailService->users_messages->listUsersMessages('me', [
        'q' => $gmailQuery,
        'maxResults' => 10
    ]);

    if (empty($messages->getMessages())) {
        echo "No new emails from allowed senders";
        exit;
    }

    $totalEmails = count($messages->getMessages());
    $createdDrafts = [];

    echo "<h3>Processing $totalEmails emails...</h3>";

    // Process all emails, not just the latest one
    foreach ($messages->getMessages() as $index => $messageItem) {
        try {
            $message = $gmailService->users_messages->get('me', $messageItem->getId());

            // Extract email details
            $headers = $message->getPayload()->getHeaders();
            $subject = '';
            $from = '';
            $to = '';

            foreach ($headers as $header) {
                $name = strtolower($header->getName());
                $value = $header->getValue();

                switch ($name) {
                    case 'subject':
                        $subject = $value;
                        break;
                    case 'from':
                        $from = $value;
                        break;
                    case 'to':
                        $to = $value;
                        break;
                }
            }

            // Get reply template and replace placeholder
            $replyTemplate = $knowledge->getReplyTemplate();
            $replyContent = str_replace('{{original_subject}}', $subject, $replyTemplate);

            // Add signature
            $signature = $knowledge->getSignature();
            $fullReply = $replyContent . "\n\n" . $signature;

            // Create MIME message
            $mimeMessage = "MIME-Version: 1.0\r\n";
            $mimeMessage .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $mimeMessage .= "Content-Transfer-Encoding: 7bit\r\n";
            $mimeMessage .= "To: $from\r\n";
            $mimeMessage .= "Subject: Re: $subject\r\n";
            $mimeMessage .= "In-Reply-To: <{$messageItem->getId()}@gmail.com>\r\n";
            $mimeMessage .= "References: <{$messageItem->getId()}@gmail.com>\r\n\r\n";
            $mimeMessage .= $fullReply;

            // Encode MIME message using base64url_encode function
            $rawMessage = base64url_encode($mimeMessage);

            // Create Gmail draft
            $draft = new \Google\Service\Gmail\Draft();
            $draft->setMessage(new \Google\Service\Gmail\Message([
                'raw' => $rawMessage
            ]));

            $createdDraft = $gmailService->users_drafts->create('me', $draft);
            $createdDrafts[] = [
                'id' => $createdDraft->getId(),
                'from' => $from,
                'subject' => $subject
            ];

            echo "<div style='color: green; margin: 5px 0;'>✅ Draft created for email from: $from</div>";
        } catch (Exception $e) {
            echo "<div style='color: red; margin: 5px 0;'>❌ Error creating draft for email: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }

    // Summary
    $successCount = count($createdDrafts);
    echo "<hr>";
    echo "<h3>📊 Results:</h3>";
    echo "<p><strong>Successfully created:</strong> $successCount/$totalEmails drafts</p>";

    if ($successCount > 0) {
        echo "<p><a href='list_drafts.php?key=$adminKey' style='color: blue; text-decoration: none;'>📋 View Draft List ($successCount)</a></p>";
    }
} catch (Exception $e) {
    http_response_code(500);
    echo "Error: " . htmlspecialchars($e->getMessage());
}

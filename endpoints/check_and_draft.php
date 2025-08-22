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
        throw new RuntimeException('Không có danh sách email được phép');
    }

    // Build Gmail query for allowed senders
    $fromQuery = implode(' OR ', array_map(function ($email) {
        return 'from:' . trim($email);
    }, $allowedSenders));

    $gmailQuery = "in:inbox newer_than:7d ($fromQuery)";

    // Get Google client and Gmail service
    $client = makeGoogleClient();
    $gmailService = new Gmail($client);

    // Search for emails matching query
    $messages = $gmailService->users_messages->listUsersMessages('me', [
        'q' => $gmailQuery,
        'maxResults' => 10
    ]);

    if (empty($messages->getMessages())) {
        echo "Không có email mới từ người gửi được phép";
        exit;
    }

    // Get the latest email (Gmail API returns newest first by default)
    $latestMessage = $messages->getMessages()[0];
    $message = $gmailService->users_messages->get('me', $latestMessage->getId());

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
    $mimeMessage .= "In-Reply-To: <{$latestMessage->getId()}@gmail.com>\r\n";
    $mimeMessage .= "References: <{$latestMessage->getId()}@gmail.com>\r\n\r\n";
    $mimeMessage .= $fullReply;

    // Encode MIME message using base64url_encode function
    $rawMessage = base64url_encode($mimeMessage);

    // Create Gmail draft
    $draft = new \Google\Service\Gmail\Draft();
    $draft->setMessage(new \Google\Service\Gmail\Message([
        'raw' => $rawMessage
    ]));

    $createdDraft = $gmailService->users_drafts->create('me', $draft);

    // Success message with link
    echo "Đã tạo Draft<br>";
    echo "<a href='list_drafts.php?key=$adminKey'>Xem danh sách Draft</a>";
} catch (Exception $e) {
    http_response_code(500);
    echo "Lỗi: " . htmlspecialchars($e->getMessage());
}

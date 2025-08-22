<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../google_client.php';

use Google\Service\Gmail;

$adminKey = Env::get('ADMIN_SHARED_KEY');
if (!$adminKey || $_GET['key'] !== $adminKey) {
    http_response_code(403);
    echo "Error: Access denied";
    exit;
}

try {
    $client = makeGoogleClient();

    // Debug: Check if client has access token
    $accessToken = $client->getAccessToken();
    if (empty($accessToken) || !isset($accessToken['access_token'])) {
        throw new RuntimeException('No valid access token found');
    }

    $gmailService = new Gmail($client);

    // Load knowledge to get allowed senders
    $knowledge = new Knowledge();
    $allowedSenders = $knowledge->getAllowedSenders();

    if (empty($allowedSenders)) {
        throw new RuntimeException('No allowed senders configured in knowledge base');
    }

    // Build query for unread emails from today, only from allowed senders
    $today = date('Y/m/d');
    $fromQuery = implode(' OR ', array_map(function ($email) {
        return 'from:' . trim($email);
    }, $allowedSenders));

    $gmailQuery = "in:inbox is:unread after:$today ($fromQuery)";

    $messages = $gmailService->users_messages->listUsersMessages('me', [
        'q' => $gmailQuery,
        'maxResults' => 20
    ]);

    $unreadEmails = [];

    if (!empty($messages->getMessages())) {
        foreach ($messages->getMessages() as $message) {
            $messageDetail = $gmailService->users_messages->get('me', $message->getId());
            $headers = $messageDetail->getPayload()->getHeaders();

            $email = [
                'id' => $message->getId(),
                'threadId' => $messageDetail->getThreadId(),
                'from' => '',
                'subject' => '',
                'date' => '',
                'snippet' => $messageDetail->getSnippet() ?? ''
            ];

            foreach ($headers as $header) {
                $name = strtolower($header->getName());
                $value = $header->getValue();

                switch ($name) {
                    case 'from':
                        $email['from'] = $value;
                        break;
                    case 'subject':
                        $email['subject'] = $value;
                        break;
                    case 'date':
                        $email['date'] = $value;
                        break;
                }
            }

            $unreadEmails[] = $email;
        }
    }

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'count' => count($unreadEmails),
        'emails' => $unreadEmails
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}

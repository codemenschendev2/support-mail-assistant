<?php

declare(strict_types=1);

/**
 * Gmail MIME Helper
 * Handles MIME message creation and manipulation for Gmail API
 */

class GmailMime
{
    /**
     * Create a simple text email MIME message
     */
    public static function createTextMessage(string $to, string $subject, string $body, string $from = null): string
    {
        $boundary = uniqid();
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
            'To: ' . $to,
            'Subject: ' . $subject
        ];

        if ($from) {
            $headers[] = 'From: ' . $from;
        }

        $message = implode("\r\n", $headers) . "\r\n\r\n";
        $message .= "--$boundary\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $message .= $body . "\r\n\r\n";
        $message .= "--$boundary--\r\n";

        return base64url_encode($message);
    }

    /**
     * Create HTML email MIME message
     */
    public static function createHtmlMessage(string $to, string $subject, string $htmlBody, string $textBody = null, string $from = null): string
    {
        $boundary = uniqid();
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
            'To: ' . $to,
            'Subject: ' . $subject
        ];

        if ($from) {
            $headers[] = 'From: ' . $from;
        }

        $message = implode("\r\n", $headers) . "\r\n\r\n";

        if ($textBody) {
            $message .= "--$boundary\r\n";
            $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $message .= $textBody . "\r\n\r\n";
        }

        $message .= "--$boundary\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $message .= $htmlBody . "\r\n\r\n";
        $message .= "--$boundary--\r\n";

        return base64url_encode($message);
    }
}

/**
 * Base64URL encode function
 */
function base64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

<?php

declare(strict_types=1);

/**
 * Google API Client Configuration
 * Handles OAuth2 authentication and Gmail API setup
 */

use Google\Client;
use Google\Service\Gmail;

/**
 * Create and configure Google Client with OAuth2
 */
function makeGoogleClient(): Google\Client
{
    $client = new Client();

    // Load environment variables
    $baseUrl = Env::get('APP_BASE_URL', 'http://localhost');
    $redirectUri = Env::get('OAUTH_REDIRECT_URI');

    // Ensure redirect URI is set correctly
    if (!$redirectUri) {
        $redirectUri = $baseUrl . '/oauth/callback.php';
    }

    // Load OAuth client credentials
    $credentialsPath = __DIR__ . '/credentials/oauth-client.json';
    if (file_exists($credentialsPath)) {
        $client->setAuthConfig($credentialsPath);
    }

    // Set redirect URI - must match exactly with Google Console
    $client->setRedirectUri($redirectUri);

    // Set scopes for Gmail API
    $client->setScopes([
        'https://www.googleapis.com/auth/gmail.readonly',
        'https://www.googleapis.com/auth/gmail.modify',
        'https://www.googleapis.com/auth/gmail.send'
    ]);

    // Load existing token if available
    $tokenPath = __DIR__ . '/credentials/token.json';
    if (file_exists($tokenPath)) {
        $tokenData = json_decode(file_get_contents($tokenPath), true);

        if ($tokenData && isset($tokenData['access_token'])) {
            $client->setAccessToken($tokenData);

            // Check if token is expired
            if (isset($tokenData['expires_in']) && isset($tokenData['created'])) {
                $expiryTime = $tokenData['created'] + $tokenData['expires_in'];

                if (time() >= $expiryTime) {
                    // Token expired, try to refresh
                    if (isset($tokenData['refresh_token'])) {
                        try {
                            $newToken = $client->fetchAccessTokenWithRefreshToken($tokenData['refresh_token']);

                            if (!isset($newToken['error'])) {
                                // Update token data
                                $tokenData = array_merge($tokenData, $newToken);
                                $tokenData['created'] = time();

                                // Save refreshed token
                                file_put_contents($tokenPath, json_encode($tokenData, JSON_PRETTY_PRINT));

                                // Set new token to client
                                $client->setAccessToken($tokenData);
                            }
                        } catch (Exception $e) {
                            // Refresh failed, token will need re-authentication
                            error_log('Failed to refresh OAuth token: ' . $e->getMessage());
                        }
                    }
                }
            }
        }
    }

    return $client;
}

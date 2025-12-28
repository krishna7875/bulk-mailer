<?php

namespace App\Services\Gmail;

use App\Models\Shooter;
use Google\Client as GoogleClient;
use Google\Service\Gmail;
use Illuminate\Support\Facades\Log;
use Exception;

class GmailClient
{
    protected GoogleClient $client;
    protected Gmail $gmail;

    public function __construct()
    {
        $this->client = new GoogleClient();
        $this->client->setClientId(config('services.gmail.client_id'));
        $this->client->setClientSecret(config('services.gmail.client_secret'));
        $this->client->setRedirectUri(config('services.gmail.redirect'));
        $this->client->setScopes(['https://www.googleapis.com/auth/gmail.send']);
        $this->client->setAccessType('offline');
    }

    /**
     * Initialize Gmail client for a specific shooter
     */
    public function forShooter(Shooter $shooter): self
    {
        if (!$shooter->gmail_access_token || !$shooter->gmail_refresh_token) {
            throw new Exception('Shooter Gmail not connected');
        }

        $this->client->setAccessToken(decrypt($shooter->gmail_access_token));

        if ($this->client->isAccessTokenExpired()) {
            $this->refreshToken($shooter);
        }

        $this->gmail = new Gmail($this->client);

        return $this;
    }

    /**
     * Refresh Gmail access token
     */
    protected function refreshToken(Shooter $shooter): void
    {
        try {
            $newToken = $this->client->fetchAccessTokenWithRefreshToken(
                decrypt($shooter->gmail_refresh_token)
            );

            if (isset($newToken['error'])) {
                throw new Exception($newToken['error_description'] ?? 'Token refresh failed');
            }

            $shooter->update([
                'gmail_access_token'     => encrypt($newToken['access_token']),
                'gmail_token_expires_at' => now()->addSeconds($newToken['expires_in']),
            ]);

            Log::info('Gmail token refreshed', [
                'shooter_id' => $shooter->id,
            ]);
        } catch (Exception $e) {
            Log::error('Gmail token refresh failed', [
                'shooter_id' => $shooter->id,
                'error'      => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Send email
     */
    public function send(string $to, string $subject, string $htmlBody): void
    {
        $rawMessage = $this->buildRawMessage($to, $subject, $htmlBody);

        $message = new \Google\Service\Gmail\Message();
        $message->setRaw($rawMessage);

        $this->gmail->users_messages->send('me', $message);
    }

    /**
     * Build RFC 2822 raw email
     */
    protected function buildRawMessage(string $to, string $subject, string $htmlBody): string
    {
        $message = "To: {$to}\r\n";
        $message .= "Subject: {$subject}\r\n";
        $message .= "MIME-Version: 1.0\r\n";
        $message .= "Content-Type: text/html; charset=utf-8\r\n\r\n";
        $message .= $htmlBody;

        return rtrim(strtr(base64_encode($message), '+/', '-_'), '=');
    }
}

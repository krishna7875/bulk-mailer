<?php

namespace App\Services;

use Google\Client as GoogleClient;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;
use App\Models\Shooter;
use Exception;
use Log;
use Carbon\Carbon;

class GmailSendService
{
    protected function clientForShooter(Shooter $shooter): GoogleClient
    {
        $client = new GoogleClient();

        $client->setClientId(config('services.gmail.client_id'));
        $client->setClientSecret(config('services.gmail.client_secret'));
        $client->setRedirectUri(config('services.gmail.redirect'));

        $client->setAccessType('offline');
        $client->setPrompt('consent'); // 🔴 VERY IMPORTANT
        $client->setScopes(['https://www.googleapis.com/auth/gmail.send']);

        // Access token handling
        $token = [
            'access_token'  => $this->safeDecrypt($shooter->gmail_access_token),
            'refresh_token' => $this->safeDecrypt($shooter->gmail_refresh_token),
            'expires_at'    => optional($shooter->gmail_token_expires_at)->timestamp,
        ];

        $client->setAccessToken($token);

        // ✅ Refresh ONLY if truly expired
        if (
            empty($token['access_token']) ||
            !$shooter->gmail_token_expires_at ||
            Carbon::parse($shooter->gmail_token_expires_at)->isPast()
        ) {

            Log::info('Refreshing Gmail access token', [
                'shooter_id' => $shooter->id,
            ]);

            $newToken = $client->fetchAccessTokenWithRefreshToken(
                $this->safeDecrypt($shooter->gmail_refresh_token)
            );

            Log::info('Refresh token response', $newToken);

            if (isset($newToken['error'])) {

                // 🔴 MARK TOKEN AS EXPIRED (UI will update)
                $shooter->update([
                    'gmail_token_expires_at' => now()->subMinute(),
                ]);

                throw new \Exception('Failed to refresh Gmail token');
            }

            // ✅ Persist refreshed token
            $shooter->update([
                'gmail_access_token'     => encrypt($newToken['access_token']),
                'gmail_token_expires_at' => now()->addSeconds($newToken['expires_in']),
            ]);

            $client->setAccessToken($newToken);
        }

        return $client;
    }

    public function send(
        Shooter $shooter,
        string $to,
        string $subject,
        string $body,
        ?array $attachment = null
    ): string {
        $client = $this->clientForShooter($shooter);
        $service = new Gmail($client);

        $rawMessage = $this->buildRawMessage(
            $shooter->email,
            $to,
            $subject,
            $body,
            $attachment
        );

        $message = new Message();
        $message->setRaw($rawMessage);

        $sent = $service->users_messages->send('me', $message);

        return $sent->getId(); // Gmail message ID
    }

    protected function buildRawMessage(
        string $from,
        string $to,
        string $subject,
        string $body,
        ?array $attachment
    ): string {
        $boundary = uniqid('boundary');

        $headers = [
            "From: {$from}",
            "To: {$to}",
            "Subject: {$subject}",
            "MIME-Version: 1.0",
        ];

        if ($attachment) {

            $headers[] = "Content-Type: multipart/mixed; boundary=\"{$boundary}\"";

            // ── BODY PART ─────────────────────────────
            $message  = "--{$boundary}\r\n";
            $message .= "Content-Type: text/html; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $message .= nl2br($body) . "\r\n";

            // ── ATTACHMENT PART ───────────────────────
            $fileData = base64_encode(file_get_contents($attachment['path']));
            $fileData = chunk_split($fileData);

            $message .= "--{$boundary}\r\n";
            $message .= "Content-Type: {$attachment['mime']}; name=\"{$attachment['name']}\"\r\n";
            $message .= "Content-Disposition: attachment; filename=\"{$attachment['name']}\"\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $message .= $fileData . "\r\n";

            // ── END ───────────────────────────────────
            $message .= "--{$boundary}--";

        } else {

            $headers[] = "Content-Type: text/html; charset=UTF-8";
            $headers[] = "Content-Transfer-Encoding: 7bit";

            $message = nl2br($body);
        }

        $raw = implode("\r\n", $headers) . "\r\n\r\n" . $message;

        // Gmail requires base64url encoding
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }


    protected function safeDecrypt(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return decrypt($value);
        } catch (\Throwable $e) {
            // Already plain text
            return $value;
        }
    }

}

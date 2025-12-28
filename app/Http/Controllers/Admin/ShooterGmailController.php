<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shooter;
use Google\Client as GoogleClient;
use Illuminate\Http\Request;
use Log;

class ShooterGmailController extends Controller
{
    protected function googleClient(): GoogleClient
    {
        $client = new GoogleClient();
        $client->setClientId(config('services.gmail.client_id'));
        $client->setClientSecret(config('services.gmail.client_secret'));
        $client->setRedirectUri(config('services.gmail.redirect'));
        $client->setScopes(['https://www.googleapis.com/auth/gmail.send']);
        $client->setAccessType('offline');
        $client->setPrompt('consent'); // ensures refresh token

        return $client;
    }

    public function redirect(Shooter $shooter)
    {
        session(['gmail_shooter_id' => $shooter->id]);

        return redirect($this->googleClient()->createAuthUrl());
    }

   public function callback(Request $request)
    {
        Log::info('Gmail OAuth callback hit', [
            'query' => $request->all(),
        ]);

        if (!$request->has('code')) {
            Log::error('Gmail OAuth callback missing code');
            abort(403, 'Authorization code missing');
        }

        $client = $this->googleClient();
        $token = $client->fetchAccessTokenWithAuthCode($request->code);

        Log::info('Gmail OAuth token response', [
            'token' => $token,
        ]);

        if (isset($token['error'])) {
            Log::error('Gmail OAuth error', $token);
            abort(403, 'Gmail authorization failed');
        }

        $shooterId = session('gmail_shooter_id');

        Log::info('Shooter ID from session', [
            'shooter_id' => $shooterId,
        ]);

        if (!$shooterId) {
            Log::error('Shooter ID missing from session');
            abort(403, 'Shooter session missing');
        }

        $shooter = Shooter::findOrFail($shooterId);

        $updated = $shooter->update([
            'gmail_access_token'     => encrypt($token['access_token']),
            'gmail_refresh_token'    => encrypt($token['refresh_token'] ?? null),
            'gmail_token_expires_at' => now()->addSeconds($token['expires_in']),
            'gmail_connected_at'     => now(),
        ]);

        Log::info('Shooter Gmail fields updated', [
            'shooter_id' => $shooter->id,
            'updated'    => $updated,
        ]);

        session()->forget('gmail_shooter_id');

        return redirect()
            ->route('shooters.index')
            ->with('success', 'Gmail connected successfully.');

    }

}


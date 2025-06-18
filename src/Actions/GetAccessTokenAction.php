<?php

namespace Kistlak\LinkedinPoster\Actions;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GetAccessTokenAction
{
    public function execute($code)
    {
        try {
            $response = Http::asForm()->post('https://www.linkedin.com/oauth/v2/accessToken', [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => config('linkedin-share.redirect'),
                'client_id' => config('linkedin-share.client_id'),
                'client_secret' => config('linkedin-share.client_secret'),
            ]);

            if (!$response->successful()) {
                Log::error('LinkedIn access token error : ' . $response->body());
                return false;
            }

            return $response;
        } catch (\Exception $e) {
            Log::error('redirectToLinkedIn error : ' . $e->getMessage());
            return false;
        }
    }
}

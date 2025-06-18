<?php

namespace Kistlak\LinkedinPoster\Actions;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GetUserProfileInfoAction
{
    public function execute($token)
    {
        try {
            $profile = Http::withToken($token)->get('https://api.linkedin.com/v2/userinfo');

            if (!$profile->successful()) {
                Log::error('LinkedIn user profile error : ' . $profile->body());
                return false;
            }

            return $profile;
        } catch (\Exception $e) {
            Log::error('GetUserProfileInfoAction error : ' . $e->getMessage());
            return false;
        }
    }
}

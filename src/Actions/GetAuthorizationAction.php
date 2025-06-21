<?php

namespace Kistlak\LinkedinPoster\Actions;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class GetAuthorizationAction
{
    public function execute($id)
    {
        try {
            $state = Str::random(16);

            Session::put('linkedin_oauth_state', $state);
            Session::put('linkedin_event_id', $id); // Keep event for redirect

            $query = http_build_query([
                'response_type' => 'code',
                'client_id' => config('linkedin-share.client_id'),
                'redirect_uri' => config('linkedin-share.redirect_uri'),
                'scope' => 'openid profile email w_member_social',
                'state' => $state,
            ]);

            return redirect('https://www.linkedin.com/oauth/v2/authorization?' . $query);
        } catch (\Exception $e) {
            Log::error('redirectToLinkedIn error : ' . $e->getMessage());
            return false;
        }
    }
}

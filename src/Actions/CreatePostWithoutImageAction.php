<?php

namespace Kistlak\LinkedinPoster\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class CreatePostWithoutImageAction
{
    public function execute(Request $request, $id, $model)
    {
        $token = Session::get('linkedin_token');
        $linkedinId = Session::get('linkedin_id');

        if (!$token || !$linkedinId) {
            return redirect()->route('linkedin.redirect')->with('error', 'Please connect LinkedIn first.');
        }

        $event = $model::findOrFail($id);
        $owner = 'urn:li:person:' . $linkedinId;

        $eventUrl = route('register.index', ['id' => $event->id, 'slug' => $event->slug]);

        // Get user-edited content and append the event link
        $userContent = trim($request->input('content'));
        $finalContent = $userContent . PHP_EOL . PHP_EOL . $eventUrl;

        try {
            $post = Http::withToken($token)
                ->withHeaders(['X-Restli-Protocol-Version' => '2.0.0'])
                ->post('https://api.linkedin.com/v2/ugcPosts', [
                    'author' => $owner,
                    'lifecycleState' => 'PUBLISHED',
                    'specificContent' => [
                        'com.linkedin.ugc.ShareContent' => [
                            'shareCommentary' => [
                                'text' => $finalContent,
                            ],
                            'shareMediaCategory' => 'NONE',
                        ],
                    ],
                    'visibility' => [
                        'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
                    ],
                ]);

            if (!$post->successful()) {
                Log::error('LinkedIn post failed: ' . $post->body());
                return back()->with('error', 'Failed to post on LinkedIn.');
            }

            return redirect(route('linkedin.share.success.index'));
        } catch (\Exception $e) {
            Log::error('LinkedIn post exception: ' . $e->getMessage());
            return back()->with('error', 'LinkedIn post failed.');
        }
    }
}

<?php

namespace Kistlak\LinkedinPoster\Actions;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CreatePostAction
{
    public function execute($token, $owner, $finalContent, $asset, $event)
    {
        try {
            $post = Http::withToken($token)
                ->withHeaders(['X-Restli-Protocol-Version' => '2.0.0'])
                ->post('https://api.linkedin.com/v2/ugcPosts', [
                    'author' => $owner,
                    'lifecycleState' => 'PUBLISHED',
                    'specificContent' => [
                        'com.linkedin.ugc.ShareContent' => [
                            'shareCommentary' => ['text' => $finalContent],
                            'shareMediaCategory' => 'IMAGE',
                            'media' => [[
                                'status' => 'READY',
                                'media' => $asset,
                                'description' => ['text' => $finalContent],
                                'title' => ['text' => $event->title_en],
                            ]],
                        ],
                    ],
                    'visibility' => [
                        'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
                    ],
                ]);

            if (!$post->successful()) {
                Log::error('LinkedIn post failed: ' . $post->body());
                return false;
            }

            return $post;
        } catch (\Exception $e) {
            Log::error('GetImageUploadUrlAction error : ' . $e->getMessage());
            return false;
        }
    }
}

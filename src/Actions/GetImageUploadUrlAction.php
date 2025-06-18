<?php

namespace Kistlak\LinkedinPoster\Actions;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GetImageUploadUrlAction
{
    public function execute($token, $owner)
    {
        try {
            $register = Http::withToken($token)->post('https://api.linkedin.com/v2/assets?action=registerUpload', [
                'registerUploadRequest' => [
                    'recipes' => ['urn:li:digitalmediaRecipe:feedshare-image'],
                    'owner' => $owner,
                    'serviceRelationships' => [[
                        'relationshipType' => 'OWNER',
                        'identifier' => 'urn:li:userGeneratedContent',
                    ]],
                ],
            ]);

            if (!$register->successful()) {
                Log::error('LinkedIn image registration failed: ' . $register->body());
                return false;
            }

            return $register->json();
        } catch (\Exception $e) {
            Log::error('GetImageUploadUrlAction error : ' . $e->getMessage());
            return false;
        }
    }
}

<?php

namespace Kistlak\LinkedinPoster\Actions;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UploadImageAction
{
    public function execute($mergedImagePath, $token, $uploadUrl)
    {
        try {
            $imageContent = file_get_contents($mergedImagePath);
            $mimeType = 'image/jpeg';

            $uploadResponse = Http::withHeaders([
                'Authorization' => "Bearer $token",
                'Content-Type' => $mimeType,
            ])->withBody($imageContent, $mimeType)->put($uploadUrl);

            if (file_exists($mergedImagePath)) {
                unlink($mergedImagePath);
                Log::info('Temp image deleted after upload', ['path' => $mergedImagePath]);
            } else {
                Log::warning('Temp image not found for deletion', ['path' => $mergedImagePath]);
            }

            if (!$uploadResponse->successful()) {
                Log::error('Image upload failed: ' . $uploadResponse->status());
                return false;
            }

            return $uploadResponse;

        } catch (\Exception $e) {
            Log::error('UploadImageAction error: ' . $e->getMessage());
            return false;
        }
    }
}

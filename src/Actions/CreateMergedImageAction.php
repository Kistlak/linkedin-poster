<?php

namespace Kistlak\LinkedinPoster\Actions;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Facades\Image;

class CreateMergedImageAction
{
    public function execute($eventImageUrl, $profileImageUrl): ?string
    {
        try {
            if (!filter_var($eventImageUrl, FILTER_VALIDATE_URL) || !filter_var($profileImageUrl, FILTER_VALIDATE_URL)) {
                throw new \Exception('Invalid image URL(s)');
            }

            // Load separated configs
            $eventConfig = config('linkedin-share.image.event', []);
            $profileConfig = config('linkedin-share.image.profile', []);

            $eventWidth = $eventConfig['width'] ?? 1200;
            $eventHeight = $eventConfig['height'] ?? 700;

            $profileSize = $profileConfig['size'] ?? 200;
            $position = $profileConfig['position'] ?? 'bottom-right';
            $offsetX = $profileConfig['offset_x'] ?? 50;
            $offsetY = $profileConfig['offset_y'] ?? 50;
            $circle = $profileConfig['circle'] ?? true;

            // Prepare images
            $eventImage = Image::make($eventImageUrl)->resize($eventWidth, $eventHeight);
            $profileImage = Image::make($profileImageUrl)->fit($profileSize, $profileSize);

            if ($circle) {
                $mask = Image::canvas($profileSize, $profileSize);
                $mask->circle($profileSize, $profileSize / 2, $profileSize / 2, function ($draw) {
                    $draw->background('#fff');
                });
                $profileImage->mask($mask, false);
            }

            // Position calculation
            $x = $offsetX;
            $y = $offsetY;

            switch ($position) {
                case 'bottom-right':
                    $x = $eventImage->width() - $profileImage->width() - $offsetX;
                    $y = $eventImage->height() - $profileImage->height() - $offsetY;
                    break;
                case 'top-right':
                    $x = $eventImage->width() - $profileImage->width() - $offsetX;
                    break;
                case 'bottom-left':
                    $y = $eventImage->height() - $profileImage->height() - $offsetY;
                    break;
                case 'center':
                    $x = ($eventImage->width() - $profileImage->width()) / 2;
                    $y = ($eventImage->height() - $profileImage->height()) / 2;
                    break;
                // 'top-left' is default
            }

            $eventImage->insert($profileImage, 'top-left', (int) $x, (int) $y);

            // Save to file
            $fileName = 'linkedin_merged_' . now()->format('YmdHis') . '_' . uniqid() . '.jpg';
            $path = storage_path('app/public/tmp/' . $fileName);

            if (!file_exists(dirname($path))) {
                mkdir(dirname($path), 0775, true);
            }

            $eventImage->save($path, 90);
            return $path;

        } catch (\Exception $e) {
            Log::error('Image merge failed: ' . $e->getMessage());
            return null;
        }
    }
}

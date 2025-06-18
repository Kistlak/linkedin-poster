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

            // Load images
            $eventImage = Image::make($eventImageUrl)->resize(1200, 700);
            $profileImage = Image::make($profileImageUrl)->fit(200, 200);

            // Make profile image circular
            $mask = Image::canvas(200, 200);
            $mask->circle(200, 100, 100, function ($draw) {
                $draw->background('#fff');
            });
            $profileImage->mask($mask, false);

            // Calculate middle-right position
            $x = $eventImage->width() - $profileImage->width() - 100; // right padding
            $y = intval(($eventImage->height() - $profileImage->height()) / 2) + 41; // vertical center

            // Insert profile image
            $eventImage->insert($profileImage, 'top-left', $x, $y);

            // Save to temp
            $dateTimeNow = Carbon::now()->format('YmdHis');
            $fileName = 'linkedin_merged_' . $dateTimeNow . '_' . uniqid() . '.jpg';
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

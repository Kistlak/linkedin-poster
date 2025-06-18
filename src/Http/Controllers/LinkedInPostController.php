<?php

namespace Kistlak\LinkedinPoster\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Session;
use Kistlak\LinkedinPoster\Actions\CreatePostAction;
use Kistlak\LinkedinPoster\Actions\GetAccessTokenAction;
use Kistlak\LinkedinPoster\Actions\GetAuthorizationAction;
use Kistlak\LinkedinPoster\Actions\GetImageUploadUrlAction;
use Kistlak\LinkedinPoster\Actions\GetUserProfileInfoAction;
use Kistlak\LinkedinPoster\Actions\UploadImageAction;
use Kistlak\LinkedinPoster\Actions\CreateMergedImageAction;

class LinkedInPostController
{
    public function linkedinShareIndex(string $model, int $id, CreateMergedImageAction $createMergedImageAction)
    {
		$allowedModels = config('linkedin-share.models');

        if (!array_key_exists($model, $allowedModels)) {
            abort(404, 'Invalid model type.');
        }
		
		$modelClass = $allowedModels[$model];
        $event = $modelClass::findOrFail($id);

        if(!Session::has('linkedin_token')) {
            return redirect(route('linkedin.redirect', $event->id));
        }

        $profileImageUrl = Session::get('linkedin_profile_picture', asset('images/default-avatar.jpg'));
        $eventImage = $event->linkedin_post_image ?? asset('images/event-img-1.jpg');
        $mergedImagePath = $createMergedImageAction->execute($eventImage, $profileImageUrl);

        $relativePath = str_replace(storage_path('app/public/'), '', $mergedImagePath); // tmp/filename.jpg
        $mergedImageUrl = asset('storage/' . $relativePath); // or Storage::url($relativePath) if disk is configured

        return view('frontend.linkedin-share', compact('event', 'mergedImageUrl'));
    }

    public function redirectToLinkedIn($id, GetAuthorizationAction $getAuthorizationAction)
    {
        try {
            $getAuthorizationAction = $getAuthorizationAction->execute($id);

            if(!$getAuthorizationAction) return back()->with('error', 'LinkedIn post failed.');

            return $getAuthorizationAction;
        } catch (\Exception $e) {
            return back()->with('error', 'LinkedIn post failed.');
        }
    }

    public function handleLinkedInCallback
    (
        Request $request,
        GetAccessTokenAction $getAccessTokenAction,
        GetUserProfileInfoAction $getUserProfileInfoAction
    )
    {
        try {
            if (!$request->has('code') || Session::get('linkedin_oauth_state') !== $request->get('state')) {
                return redirect('/')->with('error', 'Invalid LinkedIn callback.');
            }

            $response = $getAccessTokenAction->execute($request->code);

            if (!$response) {
                return redirect('/')->with('error', 'LinkedIn token exchange failed.');
            }

            $token = $response['access_token'];

            $profile = $getUserProfileInfoAction->execute($token);

            if (!$profile) {
                return redirect('/')->with('error', 'Failed to fetch LinkedIn profile.');
            }

            Session::put('linkedin_token', $token);
            Session::put('linkedin_id', $profile['sub']);

            if (isset($profile['picture']) && !empty($profile['picture'])) {
                Session::put('linkedin_profile_picture', $profile['picture']);
            } else {
                Session::put('linkedin_profile_picture', asset('images/default-avatar.jpg'));
            }

            $eventId = Session::get('linkedin_event_id');

            return redirect()->route('linkedin.share.index.view', ['id' => $eventId]);
        } catch (\Exception $e) {
            Log::error('handleLinkedInCallback error : ' . $e->getMessage());
            return back()->with('error', 'LinkedIn post failed.');
        }
    }

    public function shareToLinkedIn(
        Request                 $request,
        string                        $model,
        int                        $id,
        GetImageUploadUrlAction $linkedInGetImageUploadUrlAction,
        UploadImageAction       $uploadImageAction,
        CreateMergedImageAction $createMergedImageAction,
        CreatePostAction        $linkedInCreatePostAction
    )
    {
        try {
            $token = Session::get('linkedin_token');
            $linkedinId = Session::get('linkedin_id');

            if (!$token || !$linkedinId) {
                // Save event_id and redirect for auth
                Session::put('linkedin_event_id', $id);
                return redirect()->route('linkedin.redirect', ['id' => $id])->with('error', 'Please reconnect LinkedIn.');
            }
			
			$allowedModels = config('linkedin-share.models');

            if (!array_key_exists($model, $allowedModels)) {
				abort(404, 'Invalid model type.');
            }
		
			$modelClass = $allowedModels[$model];

            $event = $modelClass::findOrFail($id);
            $owner = 'urn:li:person:' . $linkedinId;

            $register = $linkedInGetImageUploadUrlAction->execute($token, $owner);

            if (!$register) {
                return back()->with('error', 'Image upload registration failed.');
            }

            $uploadUrl = $register['value']['uploadMechanism']['com.linkedin.digitalmedia.uploading.MediaUploadHttpRequest']['uploadUrl'];
            $asset = $register['value']['asset'];

            // Merge image
            $eventImageUrl = $event->linkedin_post_image ?? asset('images/event-img-1.jpg');
            $profileImageUrl = Session::get('linkedin_profile_picture', asset('images/default-avatar.jpg'));
            $mergedImagePath = $createMergedImageAction->execute($eventImageUrl, $profileImageUrl);

            if (!$mergedImagePath || !file_exists($mergedImagePath)) {
                return back()->with('error', 'Failed to generate merged image.');
            }

            $uploadResponse = $uploadImageAction->execute($mergedImagePath, $token, $uploadUrl);

            if (!$uploadResponse) {
                return back()->with('error', 'Image upload to LinkedIn failed.');
            }

            $eventUrl = route('register.index', ['id' => $event->id, 'slug' => $event->slug]);
            $userContent = trim($request->input('content'));
            $finalContent = $userContent . PHP_EOL . PHP_EOL . $eventUrl;

            $post = $linkedInCreatePostAction->execute($token, $owner, $finalContent, $asset, $event);

            if (!$post) {
                return back()->with('error', 'Failed to post event on LinkedIn.');
            }

            return redirect()->route('linkedin.share.success.index');
        } catch (\Exception $e) {
            Log::error('shareToLinkedIn error : ' . $e->getMessage());
            return back()->with('error', 'LinkedIn post failed.');
        }
    }

    public function linkedinShareSuccessIndex(): View
    {
        return view('frontend.linkedin-success');
    }
}

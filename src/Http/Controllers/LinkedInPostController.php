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
        $modelsConfig = config('linkedin-share.models');

        if (!array_key_exists($model, $modelsConfig)) {
            abort(404, 'Invalid model type.');
        }

        $modelConfig = $modelsConfig[$model];
        $modelClass = $modelConfig['class'];
        $imageAccessor = $modelConfig['image_accessor'] ?? null;

        $event = $modelClass::findOrFail($id);

        if (!Session::has('linkedin_token')) {
            return redirect(route('linkedin.redirect', $event->id));
        }

        $profileImageUrl = Session::get('linkedin_profile_picture', asset(config('linkedin-share.default_avatar')));

        // Dynamically resolve image accessor
        $eventImage = asset('images/event-img-1.jpg'); // fallback

        if ($imageAccessor) {
            if (method_exists($event, $imageAccessor)) {
                $eventImage = $event->{$imageAccessor}();
            } elseif (isset($event->{$imageAccessor})) {
                $eventImage = $event->{$imageAccessor};
            }
        }

        $linkedInPostImgPath = (config('linkedin-share.add_profile_picture_on_the_banner')) ?
            $createMergedImageAction->execute($eventImage, $profileImageUrl) :
            $eventImage;

        $relativePath = str_replace(storage_path('app/public/'), '', $linkedInPostImgPath);
        $linkedInPostImgUrl = asset('storage/' . $relativePath);

        Session::put('linkedin_post_img_path', $linkedInPostImgPath);

        return view('frontend.linkedin-share', compact('event', 'linkedInPostImgUrl'));
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
        string                  $model,
        int                     $id,
        GetImageUploadUrlAction $linkedInGetImageUploadUrlAction,
        UploadImageAction       $uploadImageAction,
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

            $modelsConfig = config('linkedin-share.models');

            if (!array_key_exists($model, $modelsConfig)) {
                abort(404, 'Invalid model type.');
            }

            $modelConfig = $modelsConfig[$model];
            $modelClass = $modelConfig['class'];
            $event = $modelClass::findOrFail($id);

            $owner = 'urn:li:person:' . $linkedinId;

            $register = $linkedInGetImageUploadUrlAction->execute($token, $owner);

            if (!$register) {
                return back()->with('error', 'Image upload registration failed.');
            }

            $uploadUrl = $register['value']['uploadMechanism']['com.linkedin.digitalmedia.uploading.MediaUploadHttpRequest']['uploadUrl'];
            $asset = $register['value']['asset'];

            $linkedInPostImgPath = Session::get('linkedin_post_img_path');

            if (!$linkedInPostImgPath || !file_exists($linkedInPostImgPath)) {
                return back()->with('error', 'Failed to generate merged image.');
            }

            $uploadResponse = $uploadImageAction->execute($linkedInPostImgPath, $token, $uploadUrl);

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

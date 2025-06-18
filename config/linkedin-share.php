<?php

return [
    'client_id' => env('LINKEDIN_CLIENT_ID'),
    'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
    'redirect_uri' => env('LINKEDIN_REDIRECT_URI'),

    // Optional extras
    'default_avatar' => '/images/default-avatar.jpg',
    'image_temp_path' => 'tmp', // relative to storage/app/public/
];

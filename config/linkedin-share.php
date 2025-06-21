<?php

return [
    'client_id' => env('LINKEDIN_CLIENT_ID'),
    'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
    'redirect_uri' => env('LINKEDIN_REDIRECT_URI'),
    'default_avatar' => '/images/default-avatar.jpg',
    'image_temp_path' => 'tmp', // relative to storage/app/public/
    'add_profile_picture_on_the_banner' => env('ADD_PROFILE_PICTURE_ON_THE_BANNER', false),
	
	// Allowed Eloquent models that can be shared
    'models' => [
        'event' => [
            'class' => \App\Models\Event::class,
            'image_accessor' => 'linkedin_post_image',
        ],
        // 'post' => [
        //     'class' => \App\Models\Post::class,
        //     'image_accessor' => 'linkedin_post_image',
        // ],
    ],

    'image' => [

        'event' => [
            'width' => 1200,
            'height' => 700,
        ],

        'profile' => [
            'size' => 200,
            'position' => 'bottom-right', // 'top-left', 'top-right', 'bottom-right', 'center'
            'offset_x' => 50,
            'offset_y' => 50,
            'circle' => true,
        ],

    ],
];

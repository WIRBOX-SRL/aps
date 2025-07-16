<?php

return [
    'avatar_column' => 'avatar_url',
    'disk' => env('FILAMENT_EDIT_PROFILE_DISK', 'public'), // Will be dynamically set to cloudinary in AppServiceProvider
    'visibility' => 'public', // or replace by filesystem disk visibility with fallback value

'show_custom_fields' => true,
    'custom_fields' => [
        // Email settings (for all users)
        'smtp_host' => [
            'type' => 'text',
            'label' => 'SMTP Host',
            'placeholder' => 'SMTP Host',
            'required' => false,
            'column_span' => 'full',
        ],
        'smtp_username' => [
            'type' => 'text',
            'label' => 'SMTP Username',
            'placeholder' => 'SMTP Username',
            'required' => false,
            'column_span' => 'full',
        ],
        'smtp_password' => [
            'type' => 'text',
            'label' => 'SMTP Password',
            'placeholder' => 'SMTP Password',
            'required' => false,
            'column_span' => 'full',
        ],
        'smtp_encryption' => [
            'type' => 'text',
            'label' => 'Encryption',
            'placeholder' => 'SSL/TLS',
            'required' => false,
            'column_span' => 'full',
        ],
        'smtp_port' => [
            'type' => 'text',
            'label' => 'SMTP Port',
            'placeholder' => '554',
            'required' => false,
            'column_span' => 'full',
        ],
        // Cloud & domain settings (doar pentru admini)
        'cloudinary_cloud_name' => [
            'type' => 'text',
            'label' => 'Cloudinary Cloud Name',
            'placeholder' => 'Cloudinary Cloud Name',
            'required' => false,
            'column_span' => 'full',
        ],
        'cloudinary_api_key' => [
            'type' => 'text',
            'label' => 'Cloudinary API Key',
            'placeholder' => 'Cloudinary API Key',
            'required' => false,
            'column_span' => 'full',
        ],
        'cloudinary_api_secret' => [
            'type' => 'text',
            'label' => 'Cloudinary API Secret',
            'placeholder' => 'Cloudinary API Secret',
            'required' => false,
            'column_span' => 'full',
        ],
        'cloudflare_api_key' => [
            'type' => 'text',
            'label' => 'Cloudflare API Key',
            'placeholder' => 'Cloudflare API Key',
            'required' => false,
            'column_span' => 'full',
        ],
        'cloudflare_zone_id' => [
            'type' => 'text',
            'label' => 'Cloudflare Zone ID',
            'placeholder' => 'Cloudflare Zone ID',
            'required' => false,
            'column_span' => 'full',
        ],
        'custom_domain' => [
            'type' => 'text',
            'label' => 'Custom Domain',
            'placeholder' => 'Custom Domain',
            'required' => false,
            'column_span' => 'full',
        ],
    ]
];

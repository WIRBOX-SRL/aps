<?php

return [
    'avatar_column' => 'avatar_url',
    'disk' => env('FILESYSTEM_DISK', 'public'),
    'visibility' => 'public', // or replace by filesystem disk visibility with fallback value

'show_custom_fields' => true,
    'custom_fields' => [
        'custom_field_1' => [
            'type' => 'text', // required
            'label' => 'SMTP Host', // required
            'placeholder' => 'SMTP Host', // optional
            'id' => 'custom-field-1', // optional
            'required' => true, // optional
            'rules' => [], // optional
            'hint_icon' => '', // optional
            'hint' => '', // optional
            'suffix_icon' => '', // optional
            'prefix_icon' => '', // optional
            'default' => '', // optional
            'column_span' => 'full', // optional
            'autocomplete' => false, // optional
        ],
        'custom_field_2' => [
            'type' => 'text', // required
            'label' => ' SMTP Username', // required
            'placeholder' => ' Email', // optional
            'id' => 'custom-field-2', // optional
            'required' => true, // optional
            'rules' => [], // optional
            'hint_icon' => '', // optional
            'hint' => '', // optional
            'default' => '', // optional
            'column_span' => 'full', // optional
        ],
        'custom_field_4' => [
            'type' => 'text', // required
            'label' => 'SMTP Password', // required
            'placeholder' => 'Password', // optional
            'id' => 'custom-field-4', // optional
            'required' => true, // optional
            'hint_icon' => '', // optional
            'hint' => '', // optional
            'default' => '', // optional
            'rules' => [], // optional
            'column_span' => 'full', // optional
        ],
         'custom_field_3' => [
            'type' => 'text', // required
            'label' => 'Encryption', // required
            'placeholder' => 'SSL/TLS', // optional
            'id' => 'custom-field-3', // optional
            'required' => true, // optional
            'hint_icon' => '', // optional
            'hint' => '', // optional
            'default' => '', // optional
            'rules' => [], // optional
            'column_span' => 'full', // optional
        ],
        'custom_field_5' => [
            'type' => 'text', // required
            'label' => 'SMTP Port', // required
            'placeholder' => '554', // optional
            'id' => 'custom-field-5', // optional
            'required' => true, // optional
            'hint_icon' => '', // optional
            'hint' => '', // optional
            'default' => '', // optional
            'rules' => [], // optional
            'column_span' => 'full', // optional
        ],



    ]
];

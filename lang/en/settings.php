<?php

declare(strict_types=1);

return [
    'title' => 'Settings',
    'description' => 'Manage your profile and account settings',
    'appearance' => [
        'title' => 'Dashboard appearance settings',
        'description' => "Update your account's dashboard appearance settings",
    ],
    'password' => [
        'title' => 'Password settings',
        'description' => 'Ensure your account is using a long, random password to stay secure',
        'current_password' => 'Current password',
        'current_password_placeholder' => 'Current password',
        'new_password' => 'New password',
        'new_password_placeholder' => 'New password',
        'confirm_password' => 'Confirm password',
        'confirm_password_placeholder' => 'Confirm password',
        'save_button' => 'Save password',
        'saved' => 'Saved',
    ],
    'profile' => [
        'title' => 'Profile settings',
        'description' => 'Update your name and email address',
        'name' => 'Name',
        'name_placeholder' => 'Full name',
        'email' => 'Email address',
        'email_placeholder' => 'Email address',
        'email_unverified' => 'Your email address is unverified.',
        'resend_verification' => 'Click here to resend the verification email.',
        'verification_link_sent' => 'A new verification link has been sent to your email address.',
        'save_button' => 'Save',
        'saved' => 'Saved',
        'delete_account' => [
            'title' => 'Delete account',
            'description' => 'Delete your account and all of its resources',
            'warning' => 'Warning',
            'warning_description' => 'Please proceed with caution, this cannot be undone.',
            'button' => 'Delete account',
            'confirm_title' => 'Are you sure you want to delete your account?',
            'confirm_description' => 'Once your account is deleted, all of its resources and data will also be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.',
            'password' => 'Password',
            'password_placeholder' => 'Password',
            'cancel' => 'Cancel',
            'confirm_button' => 'Delete account',
        ],
    ],
];

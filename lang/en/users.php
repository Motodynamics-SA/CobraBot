<?php

declare(strict_types=1);

return [
    'title' => 'Backoffice Users',
    'index_page_title' => 'All Backoffice Users',
    'actions' => [
        'new' => 'New Backoffice User',
        'new_big_button' => 'Create Backoffice User',
        'create' => 'Create Backoffice User',
        'edit' => 'Edit Backoffice User',
        'edit_big_button' => 'Edit Backoffice User',
        'update' => 'Update Backoffice User',
        'delete' => 'Delete Backoffice User',
        'show' => 'Show Backoffice User',
        'restore' => 'Restore Backoffice User',
    ],
    'labels' => [
        'name' => 'Name',
        'email' => 'Email',
        'password' => 'Password',
        'password_confirmation' => 'Confirm Password',
        'actions' => 'Actions',
        'role' => 'Role',
        'status' => 'Status',
        'created_at' => 'Created At',
    ],
    'placeholders' => [
        'select_role' => 'Select Role',
        'search' => 'Search by name or email...',
    ],
    'messages' => [
        'created' => 'User created successfully',
        'updated' => 'User updated successfully',
        'deleted' => 'User deleted successfully',
        'restored' => 'User restored successfully',
    ],
    'delete' => [
        'title' => 'Delete User',
        'description' => 'Are you sure you want to delete this user?',
    ],
    'status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],
    'restore' => [
        'title' => 'Restore User',
        'description' => 'Are you sure you want to restore this user?',
    ],
];

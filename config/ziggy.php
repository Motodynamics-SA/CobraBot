<?php

declare(strict_types=1);

// This is a list of routes that will be available in the Ziggy router.
return [
    'only' => ['dashboard', 'users.index', 'users.create', 'users.store', 'users.show',
        'users.edit', 'users.update', 'users.destroy', 'users.restore',
        'logout', 'login', 'home', 'profile.edit', 'profile.update', 'profile.destroy',
        'password.edit', 'password.update', 'appearance', 'client-applications.index',
        'altcha-challenge', 'data-entry.index', 'data-entry.store',
        'login.microsoft', 'login.microsoft.callback',
    ],
];

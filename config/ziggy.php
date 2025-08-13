<?php

declare(strict_types=1);

// This is a list of routes that will be available in the Ziggy router.
return [
    'only' => ['dashboard', 'users.index', 'users.create', 'users.store', 'users.show',
        'users.edit', 'users.update', 'users.destroy', 'users.restore',
        'logout', 'login', 'home', 'profile.edit', 'profile.update', 'profile.destroy',
        'password.edit', 'password.update', 'appearance', 'client-applications.index',
        'altcha-challenge', 'register', 'password.request', 'password.email',
        'password.reset', 'password.store', 'verification.notice', 'verification.verify',
        'verification.send', 'password.confirm', 'password.confirmation', 'data-entry.index', 'data-entry.store',
        'login.microsoft', 'login.microsoft.callback',
    ],
];

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Models\User;
use App\Services\User\UserService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller {
    use AuthorizesRequests;

    public function __construct(
        private UserService $userService
    ) {}

    /**
     * Display a listing of users.
     */
    public function index(Request $request): Response {
        $this->authorize('viewAny', User::class);

        $search = $request->query('search');

        $users = $this->userService->getUsers(
            search: $search
        );

        return Inertia::render('users/index', [
            'users' => $users,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): Response {
        $this->authorize('create', User::class);

        return Inertia::render('users/create', [
            'roles' => $this->userService->getRolesForForm(),
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(UserStoreRequest $userStoreRequest): RedirectResponse {
        $this->authorize('create', User::class);

        $this->userService->create($userStoreRequest->validated());

        return redirect()
            ->route('users.index')
            ->with('success', __('users.messages.created'));
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): Response {
        $this->authorize('view', $user);

        return Inertia::render('users/show', [
            'user' => $user,
        ]);
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): Response {
        $this->authorize('update', $user);

        return Inertia::render('users/edit', [
            'user' => $user,
            'roles' => $this->userService->getRolesForForm(),
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(UserUpdateRequest $userUpdateRequest, User $user): RedirectResponse {
        $this->authorize('update', $user);

        $this->userService->update($user, $userUpdateRequest->validated());

        return redirect()
            ->route('users.index')
            ->with('success', __('users.messages.updated'));
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user): RedirectResponse {
        $this->authorize('delete', $user);

        $this->userService->delete($user);

        return redirect()->route('users.index')
            ->with('success', __('users.messages.deleted'));
    }
}

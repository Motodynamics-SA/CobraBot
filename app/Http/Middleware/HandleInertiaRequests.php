<?php

namespace App\Http\Middleware;

use App\Enums\PermissionsEnum;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware {
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return [
            ...parent::share($request),
            'appName' => config('app.name'),
            'locale' => app()->getLocale(),
            'quote' => ['message' => trim($message ?? ''), 'author' => trim($author ?? '')],
            'translations' => fn () => [
                'auth' => trans('auth'),
                'pagination' => trans('pagination'),
                'passwords' => trans('passwords'),
                'users' => trans('users'),
                'roles' => trans('roles'),
                'validation' => trans('validation'),
                'common' => trans('common'),
                'dashboard' => trans('dashboard'),
                'client-applications' => trans('client-applications'),
                'navbar' => trans('navbar'),
                'settings' => trans('settings'),
                // add other translation files as needed
            ],
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'created_at' => $request->user()->created_at,
                    'updated_at' => $request->user()->updated_at,
                    'deleted_at' => $request->user()->deleted_at,
                    'can' => collect(PermissionsEnum::cases())
                        ->mapWithKeys(fn (PermissionsEnum $permission) => [
                            str_replace(' ', '_', $permission->value) => $request->user()->can($permission->value),
                            str_replace('-', '_', $permission->value) => $request->user()->can($permission->value),
                        ])
                        ->all(),
                ] : null,
            ],
            'ziggy' => fn (): array => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'flash' => [
                'success' => session('success'),
                'error' => session('error'),
                'warning' => session('warning'),
                'info' => session('info'),
            ],
            'csrf_token' => csrf_token(),
        ];
    }
}

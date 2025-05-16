<?php

namespace App\Http\Middleware;

use App\Models\Conversion;
use App\Services\GitHubVersionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
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
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'user' => fn () => Auth::check()
                ? Auth::user()->only('id', 'name', 'email')
                : null,
            'session' => [
                'id' => $request->session()->getId(),
            ],
            'conversions' => fn () => Conversion::where('session_id', $request->session()->getId())->select('id')->get(),
            'github_version' => app(GitHubVersionService::class)->getVersion(),
        ]);
    }
}

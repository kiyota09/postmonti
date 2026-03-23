<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                // Employees/Staff (Default Web Guard)
                'user' => $request->user(),

                // B2B Clients - Safely retrieved if guard exists
                'client' => $this->getGuardUser('client'),

                // New: Supplier Guard - Safely retrieved if guard exists
                'supplier' => $this->getGuardUser('supplier'),
            ],
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            // Flash messages for login/registration alerts
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
            ],
        ];
    }

    /**
     * Safely get the user for a specific guard to prevent
     * InvalidArgumentException if the guard is not yet defined.
     */
    protected function getGuardUser(string $guard)
    {
        try {
            return Auth::guard($guard)->user();
        } catch (\InvalidArgumentException $e) {
            return null;
        }
    }
}

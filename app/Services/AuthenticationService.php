<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\SessionGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use LogicException;

class AuthenticationService
{
    private const ATTEMPT_WINDOW_SECONDS = 60;

    private const PAIR_ATTEMPT_LIMIT = 5;

    private const ACCOUNT_ATTEMPT_LIMIT = 10;

    private const REMEMBER_DURATION_MINUTES = 7 * 24 * 60;

    public function attempt(string $email, string $password, bool $remember, ?string $ip): bool
    {
        $normalizedEmail = Str::lower($email);
        $accountKey = 'admin-login:account:'.hash('sha256', $normalizedEmail);
        $pairKey = 'admin-login:pair:'.hash('sha256', $normalizedEmail.'|'.$ip);

        if (
            RateLimiter::tooManyAttempts($pairKey, self::PAIR_ATTEMPT_LIMIT)
            || RateLimiter::tooManyAttempts($accountKey, self::ACCOUNT_ATTEMPT_LIMIT)
        ) {
            return false;
        }

        $guard = Auth::guard();

        if (! ($guard instanceof SessionGuard)) {
            throw new LogicException('Admin login requires a session guard.');
        }

        $guard->setRememberDuration(self::REMEMBER_DURATION_MINUTES);

        $authenticated = $guard->attemptWhen(
            ['email' => $email, 'password' => $password],
            fn (User $user): bool => Gate::forUser($user)->allows('admin.dashboard.view'),
            $remember,
        );

        if (! $authenticated) {
            RateLimiter::hit($pairKey, self::ATTEMPT_WINDOW_SECONDS);
            RateLimiter::hit($accountKey, self::ATTEMPT_WINDOW_SECONDS);

            return false;
        }

        RateLimiter::clear($pairKey);
        RateLimiter::clear($accountKey);

        return true;
    }
}

<?php

declare(strict_types=1);

namespace App\Auth;

use App\Models\Scopes\FirmScope;
use App\Models\User;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;

class FirmAwareUserProvider extends EloquentUserProvider
{
    protected function newModelQuery($model = null): Builder
    {
        $query = parent::newModelQuery($model);

        return $query->withoutGlobalScope(FirmScope::class);
    }

    /**
     * Emails are unique per firm, not globally. When the same email exists in
     * multiple firms, pick the account whose password matches.
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        $password = $credentials['password'] ?? null;

        $queryCredentials = array_filter(
            $credentials,
            fn ($key) => ! str_contains((string) $key, 'password'),
            ARRAY_FILTER_USE_KEY
        );

        if ($queryCredentials === []) {
            return null;
        }

        $query = $this->newModelQuery();
        foreach ($queryCredentials as $key => $value) {
            if (is_array($value) || $value instanceof \Arrayable) {
                $query->whereIn($key, $value);
            } else {
                $query->where($key, $value);
            }
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, User> $users */
        $users = $query->get();
        if ($users->isEmpty()) {
            return null;
        }

        if ($users->count() === 1) {
            return $users->first();
        }

        if (! is_string($password) || $password === '') {
            return null;
        }

        $matches = $users->filter(
            fn (User $user) => $this->hasher->check($password, $user->getAuthPassword())
        );

        if ($matches->isEmpty()) {
            return null;
        }

        if ($matches->count() === 1) {
            return $matches->first();
        }

        // Same email + same password across firms: prefer an active firm member.
        return $matches->first(fn (User $user) => $user->isActive() && $user->firm_id !== null)
            ?? $matches->first(fn (User $user) => $user->isActive())
            ?? $matches->first();
    }
}

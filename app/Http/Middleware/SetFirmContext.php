<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Firm\FirmContext;
use App\Models\Firm;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetFirmContext
{
    public function __construct(private readonly FirmContext $firms) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->firms->id() === null) {
            $user = $request->user();
            $header = $request->header('X-Firm-Id');

            if ($user instanceof User) {
                $this->firms->set($user->firm_id);
            } elseif (is_numeric($header)) {
                $this->firms->set((int) $header);
            } elseif (Firm::query()->count() === 1) {
                $this->firms->set((int) Firm::query()->orderBy('id')->value('id'));
            }
        }

        try {
            return $next($request);
        } finally {
            $this->firms->forget();
        }
    }
}

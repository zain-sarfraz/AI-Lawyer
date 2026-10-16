<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Firm\FirmContext;
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

            // Superadmin stays firm-less so FirmScope blocks all tenant CRM data.
            if ($user instanceof User && ! $user->isSuperAdmin() && $user->firm_id !== null) {
                $this->firms->set((int) $user->firm_id);
            }
        }

        try {
            return $next($request);
        } finally {
            $this->firms->forget();
        }
    }
}

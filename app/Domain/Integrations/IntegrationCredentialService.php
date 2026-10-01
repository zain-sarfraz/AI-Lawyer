<?php

namespace App\Domain\Integrations;

use App\Domain\Audit\AuditLogService;
use App\Models\Integration;

class IntegrationCredentialService
{
    public function __construct(private readonly AuditLogService $audit) {}

    public function upsert(string $provider, array $credentials, array $settings = [], string $status = 'active'): Integration
    {
        $integration = Integration::query()->firstOrNew(['provider' => $provider]);
        $wasConfigured = filled($integration->encrypted_credentials);
        $integration->encrypted_credentials = $credentials;
        $integration->settings_json = $settings;
        $integration->status = $status;
        $integration->save();

        $this->audit->log(
            $wasConfigured ? 'integration.updated' : 'integration.created',
            $integration,
            null,
            ['provider' => $provider, 'status' => $status, 'credentials' => '***'],
        );

        return $integration;
    }
}

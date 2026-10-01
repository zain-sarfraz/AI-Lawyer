<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Integration extends Model
{
    use \App\Models\Concerns\BelongsToFirm;

    protected $fillable = [
        'firm_id', 'provider', 'status', 'encrypted_credentials', 'settings_json',
    ];

    protected function casts(): array
    {
        return [
            'encrypted_credentials' => 'encrypted:array',
            'settings_json' => 'array',
        ];
    }

    public function maskedCredentials(): array
    {
        $creds = $this->encrypted_credentials ?? [];
        $masked = [];
        foreach ($creds as $key => $value) {
            if (! is_string($value) || $value === '') {
                $masked[$key] = null;
                continue;
            }
            $masked[$key] = str_repeat('*', max(strlen($value) - 4, 0)).substr($value, -4);
        }

        return $masked;
    }
}

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Legal-data providers for the Case Research module
    |--------------------------------------------------------------------------
    |
    | Each provider covers one or more countries (ISO-3166-1 alpha-2). The
    | Cases module asks the registry for the provider that can serve a country
    | instead of talking to a provider directly, so new jurisdictions are added
    | here without touching controllers or views.
    |
    | CourtListener publishes United States federal and state court records
    | only — no other country is covered yet.
    |
    */

    'providers' => [
        'PK' => App\Domain\Legal\PakistanCaseLawService::class,
        'GB' => App\Domain\Legal\UkCaseLawService::class,
    ],

    /*
    | Fallback provider used when the user has no saved signup country or when
    | the signup country has no compatible data source yet.
    */

    'default' => App\Domain\Legal\PakistanCaseLawService::class,

];

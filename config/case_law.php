<?php

return [

    'fetch' => [
        'enabled' => (bool) env('CASE_LAW_FETCH_SCHEDULE_ENABLED', false),
        'delay_ms' => (int) env('CASE_LAW_FETCH_DELAY_MS', 2500),
        'user_agent' => env('CASE_LAW_FETCH_USER_AGENT', 'AI-Lawyer/1.0 (+https://example.local; research)'),
        'max_requests_per_run' => (int) env('CASE_LAW_FETCH_MAX_REQUESTS', 25),
        'respect_robots' => (bool) env('CASE_LAW_FETCH_RESPECT_ROBOTS', true),
        'retry_times' => (int) env('CASE_LAW_FETCH_RETRY_TIMES', 3),
        'retry_sleep_ms' => (int) env('CASE_LAW_FETCH_RETRY_SLEEP_MS', 1500),
    ],

    'findcaselaw' => [
        'enabled' => (bool) env('FINDCASELAW_ENABLED', false),
        'base_url' => env('FINDCASELAW_BASE_URL', 'https://caselaw.nationalarchives.gov.uk'),
    ],

    'sindh' => [
        'base_url' => env('SINDH_CASELAW_BASE_URL', 'https://caselaw.shc.gov.pk'),
        'home_path' => env('SINDH_CASELAW_HOME_PATH', '/caselaw/public/home.php'),
        'selectors' => [
            'result_row' => env('SINDH_SELECTOR_ROW', 'textarea.reference'),
            'title' => env('SINDH_SELECTOR_TITLE', ''),
            'citation' => env('SINDH_SELECTOR_CITATION', ''),
        ],
    ],

    'pakistan_case_law' => [
        'mcp_url' => env('PAKISTAN_CASE_LAW_MCP_URL', 'https://pakistancaselaw.com/mcp'),
    ],

    'canlii' => [
        'api_key' => env('CANLII_API_KEY'),
        'base_url' => env('CANLII_BASE_URL', 'https://api.canlii.org/v1/caseBrowse'),
        'language' => env('CANLII_LANGUAGE', 'en'),
        'default_database' => env('CANLII_DEFAULT_DATABASE', 'csc-scc'),
        // Which CanLII databases to pull for each app jurisdiction code.
        'databases_by_jurisdiction' => [
            'CA-FED' => array_values(array_filter(array_map(
                'trim',
                explode(',', (string) env('CANLII_CA_FED_DATABASES', 'csc-scc,fca,fc'))
            ))),
            'CA-ON' => array_values(array_filter(array_map(
                'trim',
                explode(',', (string) env('CANLII_CA_ON_DATABASES', 'onca,onsc'))
            ))),
        ],
    ],

    'huggingface_scp' => [
        // Map dataset columns → case_law_records fields. Adjust after inspecting the file.
        'column_map' => [
            'external_id' => env('HF_SCP_COL_ID', 'id'),
            'title' => env('HF_SCP_COL_TITLE', 'title'),
            'citation' => env('HF_SCP_COL_CITATION', 'citation'),
            'decision_date' => env('HF_SCP_COL_DATE', 'date'),
            'full_text' => env('HF_SCP_COL_TEXT', 'text'),
            'source_url' => env('HF_SCP_COL_URL', 'url'),
        ],
    ],

    'adapters' => [
        'GB-EAW' => App\Domain\CaseLaw\Adapters\UkFindCaseLawAdapter::class,
        'PK-SD' => App\Domain\CaseLaw\Adapters\PakistanCaseLawAdapter::class,
        'PK-FED' => App\Domain\CaseLaw\Adapters\PakistanCaseLawAdapter::class,
        'PK-PB' => App\Domain\CaseLaw\Adapters\PakistanCaseLawAdapter::class,
        'PK-IS' => App\Domain\CaseLaw\Adapters\PakistanCaseLawAdapter::class,
        'CA-FED' => App\Domain\CaseLaw\Adapters\CanliiAdapter::class,
        'CA-ON' => App\Domain\CaseLaw\Adapters\CanliiAdapter::class,
    ],

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-3-5-haiku-latest'),
        'max_chunk_chars' => (int) env('ANTHROPIC_MAX_CHUNK_CHARS', 12000),
    ],

];

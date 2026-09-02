<?php
// Copy to config.php and set your MySQL credentials.
return [
    'app_name' => 'WebStripe AI CRM',
    'db' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3306',
        'name' => getenv('DB_NAME') ?: 'ai_crm',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '',
        'charset' => 'utf8mb4',
    ],
    'ai' => [
        'enabled' => (bool) getenv('OPENAI_API_KEY'),
        'api_key' => getenv('OPENAI_API_KEY') ?: '',
        'model' => getenv('OPENAI_MODEL') ?: 'gpt-4o-mini',
    ],
];

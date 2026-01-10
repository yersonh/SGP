<?php
header('Content-Type: application/json');
header('X-Voting-Health-Check: true');

$health = [
    'status' => 'healthy',
    'service' => 'voting-system',
    'timestamp' => date('c'),
    'environment' => getenv('APP_ENV') ?: 'production',
    'checks' => []
];

try {
    // Check PostgreSQL
    if (extension_loaded('pdo_pgsql')) {
        $dsn = sprintf(
            "pgsql:host=%s;port=%s;dbname=%s;sslmode=require",
            getenv('PGHOST'),
            getenv('PGPORT'),
            getenv('PGDATABASE')
        );
        
        $pdo = new PDO($dsn, getenv('PGUSER'), getenv('PGPASSWORD'), [
            PDO::ATTR_TIMEOUT => 5,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        $stmt = $pdo->query("SELECT 1 as connected, NOW() as db_time");
        $result = $stmt->fetch();
        
        $health['checks']['database'] = [
            'status' => 'connected',
            'response_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
            'server_time' => $result['db_time']
        ];
        
        $pdo = null;
    }
} catch (Exception $e) {
    $health['checks']['database'] = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
    $health['status'] = 'unhealthy';
}

// Check filesystem
$health['checks']['filesystem'] = [
    'storage_writable' => is_writable('/app/storage'),
    'session_writable' => is_writable('/tmp/php_sessions_votaciones'),
    'upload_writable' => is_writable('/tmp/php_uploads_votaciones')
];

// Check PHP
$health['checks']['php'] = [
    'version' => PHP_VERSION,
    'extensions' => get_loaded_extensions(),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time')
];

// Check services
$health['checks']['services'] = [
    'php_fpm' => function_exists('fastcgi_finish_request'),
    'opcache' => extension_loaded('Zend OPcache'),
    'pgsql' => extension_loaded('pdo_pgsql')
];

// Determinar estado final
foreach ($health['checks'] as $check) {
    if (is_array($check) && isset($check['status']) && $check['status'] === 'error') {
        $health['status'] = 'unhealthy';
        http_response_code(503);
        break;
    }
}

// Auditoría de health check
file_put_contents(
    '/var/log/votaciones-health.log',
    json_encode([
        'timestamp' => date('c'),
        'status' => $health['status'],
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
    ]) . PHP_EOL,
    FILE_APPEND
);

echo json_encode($health, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
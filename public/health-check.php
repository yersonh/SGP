<?php
// Health Check TEMPORAL - Sin PostgreSQL hasta que lo configures
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');

// Siempre devolver 200 OK para que Railway pueda iniciar
http_response_code(200);

echo json_encode([
    'status' => 'healthy',
    'service' => 'voting-system',
    'timestamp' => date('c'),
    'message' => 'Sistema funcionando - PostgreSQL pendiente de configurar',
    'checks' => [
        'php' => [
            'version' => PHP_VERSION,
            'status' => 'ok'
        ],
        'webserver' => [
            'type' => 'nginx/php-fpm',
            'status' => 'ok'
        ],
        'postgresql' => [
            'status' => 'not_configured',
            'message' => 'Configurar en Railway Dashboard → New → Database → PostgreSQL'
        ]
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
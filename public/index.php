<?php
// Mostrar TODOS los errores (solo para desarrollo)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';

// Verificar si hay errores de conexión
try {
    $pdo = Database::getConnection();
    
    // Probar consulta simple
    $stmt = $pdo->query("SELECT 1 as conexion_ok");
    $result = $stmt->fetch();
    
    echo "✅ Conexión exitosa a PostgreSQL<br>";
    echo "Railway DB: " . getenv('PGDATABASE') . "<br>";
    
} catch (PDOException $e) {
    echo "❌ ERROR DE CONEXIÓN:<br>";
    echo "Mensaje: " . $e->getMessage() . "<br>";
    echo "Host: " . (getenv('PGHOST') ?: 'No configurado') . "<br>";
    echo "DB: " . (getenv('PGDATABASE') ?: 'No configurado') . "<br>";
    echo "Usuario: " . (getenv('PGUSER') ?: 'No configurado') . "<br>";
    
    // Mostrar todas las variables de entorno relacionadas
    echo "<hr><h3>Variables de entorno:</h3>";
    $env_vars = ['PGHOST', 'PGPORT', 'PGDATABASE', 'PGUSER', 'PGPASSWORD', 'DATABASE_URL'];
    foreach ($env_vars as $var) {
        echo $var . " = " . (getenv($var) ?: 'NO DEFINIDA') . "<br>";
    }
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>SGP - Sistema de Votaciones</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            height: 100vh;
            background: 
                linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)),
                url('/imagenes/fondo.jpg') no-repeat center center fixed;
            background-size: cover;
            font-family: Arial, sans-serif;
        }
        
        .debug-info {
            position: fixed;
            top: 10px;
            left: 10px;
            background: rgba(255,255,255,0.9);
            padding: 10px;
            border-radius: 5px;
            font-size: 12px;
            color: green;
            border: 1px solid green;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <div class="debug-info">
        ✅ PostgreSQL Conectado
    </div>
    <!-- Tu contenido aquí -->
</body>
</html>
<?php
// config/database.php - VERSIÓN CON CREDENCIALES DIRECTAS
class Database {
    private static $instance = null;
    
    public static function getConnection() {
        if (self::$instance === null) {
            try {
                // 🔥 USAR DATABASE_PUBLIC_URL que YA TIENES
                $host = 'turntable.proxy.rlwy.net';
                $port = '39196';
                $dbname = 'railway';
                $user = 'postgres';
                $pass = 'sqmBeKEZFMTSIqwaJKCbwkplRfzdpHwB';
                
                $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
                
                self::$instance = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 5,
                    PDO::ATTR_PERSISTENT => false
                ]);
                
                // Configurar UTF-8
                self::$instance->exec("SET NAMES 'UTF8'");
                self::$instance->exec("SET timezone = 'America/Bogota'");
                
                error_log("✅ Conectado a PostgreSQL: {$host}:{$port}/{$dbname}");
                
            } catch (PDOException $e) {
                error_log("❌ Error PostgreSQL: " . $e->getMessage());
                throw new Exception("No se pudo conectar a la base de datos. Contacte al administrador.");
            }
        }
        return self::$instance;
    }
}
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
    
    // 🔥 NUEVO: Configuración de uploads para Railway
    public static function getUploadsPath() {
        // Railway crea automáticamente la variable RAILWAY_VOLUME_MOUNT_PATH
        // cuando configuras un mount en railway.json
        if (getenv('RAILWAY_VOLUME_MOUNT_PATH')) {
            // En producción con Railway
            return getenv('RAILWAY_VOLUME_MOUNT_PATH') . '/uploads/';
        } elseif (getenv('UPLOADS_PATH')) {
            // Variable personalizada
            return getenv('UPLOADS_PATH') . '/';
        } else {
            // Entorno local o desarrollo
            return dirname(__DIR__) . '/uploads/';
        }
    }
    
    public static function getUploadsUrl() {
        // Para Railway, necesitamos construir la URL correctamente
        if (getenv('RAILWAY_STATIC_URL')) {
            // Railway proporciona esta variable para static assets
            return getenv('RAILWAY_STATIC_URL') . '/uploads/';
        } else {
            // Entorno local o fallback
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $basePath = dirname($_SERVER['PHP_SELF'] ?? '');
            
            // Remover "index.php" si está en la ruta
            $basePath = str_replace('/index.php', '', $basePath);
            
            return $protocol . $host . $basePath . '/uploads/';
        }
    }
    
    // Validar que el directorio de uploads existe
    public static function ensureUploadsDirectory() {
        $uploadsPath = self::getUploadsPath();
        
        if (!is_dir($uploadsPath)) {
            mkdir($uploadsPath, 0755, true);
            mkdir($uploadsPath . 'profiles/', 0755, true);
            mkdir($uploadsPath . 'temp/', 0755, true);
            
            // Crear archivos .gitkeep para mantener las carpetas
            file_put_contents($uploadsPath . 'profiles/.gitkeep', '');
            file_put_contents($uploadsPath . 'temp/.gitkeep', '');
            
            error_log("✅ Directorio de uploads creado: {$uploadsPath}");
        }
        
        // Verificar permisos
        if (!is_writable($uploadsPath)) {
            error_log("⚠️  Advertencia: Directorio de uploads no tiene permisos de escritura: {$uploadsPath}");
        }
        
        return $uploadsPath;
    }
}
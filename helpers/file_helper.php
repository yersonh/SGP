<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/uploads.php';

class FileHelper {
    
    /**
     * Subir foto de perfil
     */
    public static function uploadProfilePhoto($file, $userId) {
        $config = require __DIR__ . '/../config/uploads.php';
        $uploadsPath = Database::getUploadsPath();
        
        // Validar archivo
        self::validateFile($file, $config);
        
        // Generar nombre único
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = 'profile_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $destination = $uploadsPath . $config['profiles_path'] . $filename;
        
        // Crear directorio si no existe
        Database::ensureUploadsDirectory();
        
        // Mover archivo
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception('Error al mover el archivo subido');
        }
        
        // Optimizar imagen si es necesario
        self::optimizeImage($destination, $config);
        
        return [
            'filename' => $filename,
            'path' => $destination,
            'url' => Database::getUploadsUrl() . $config['profiles_path'] . $filename,
            'size' => filesize($destination),
            'type' => mime_content_type($destination)
        ];
    }
    
    /**
     * Validar archivo
     */
    private static function validateFile($file, $config) {
        // Verificar errores de upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors = [
                UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por el servidor',
                UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo permitido por el formulario',
                UPLOAD_ERR_PARTIAL => 'El archivo fue subido parcialmente',
                UPLOAD_ERR_NO_FILE => 'No se seleccionó ningún archivo',
                UPLOAD_ERR_NO_TMP_DIR => 'No existe directorio temporal',
                UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir en el disco',
                UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida'
            ];
            
            throw new Exception($errors[$file['error']] ?? 'Error desconocido al subir archivo');
        }
        
        // Verificar tipo MIME
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $config['allowed_types'])) {
            throw new Exception('Tipo de archivo no permitido. Solo se permiten imágenes JPG, PNG, GIF y WebP');
        }
        
        // Verificar tamaño
        if ($file['size'] > $config['max_size']) {
            $maxSizeMB = $config['max_size'] / (1024 * 1024);
            throw new Exception("El archivo es demasiado grande. Tamaño máximo: {$maxSizeMB}MB");
        }
        
        // Verificar extensión
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $config['allowed_extensions'])) {
            throw new Exception('Extensión de archivo no permitida');
        }
    }
    
    /**
     * Optimizar imagen
     */
    private static function optimizeImage($imagePath, $config) {
        $mimeType = mime_content_type($imagePath);
        
        if ($mimeType === 'image/jpeg' || $mimeType === 'image/jpg') {
            $image = imagecreatefromjpeg($imagePath);
            imagejpeg($image, $imagePath, $config['jpeg_quality']);
            imagedestroy($image);
        }
        
        // Redimensionar si es muy grande
        list($width, $height) = getimagesize($imagePath);
        
        if ($width > $config['max_width'] || $height > $config['max_height']) {
            self::resizeImage($imagePath, $config['max_width'], $config['max_height']);
        }
    }
    
    /**
     * Redimensionar imagen manteniendo proporción
     */
    private static function resizeImage($imagePath, $maxWidth, $maxHeight) {
        list($width, $height, $type) = getimagesize($imagePath);
        
        // Calcular nuevas dimensiones manteniendo proporción
        $ratio = $width / $height;
        
        if ($width > $height) {
            $newWidth = $maxWidth;
            $newHeight = $maxWidth / $ratio;
        } else {
            $newHeight = $maxHeight;
            $newWidth = $maxHeight * $ratio;
        }
        
        // Crear imagen redimensionada
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Cargar imagen según tipo
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($imagePath);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($imagePath);
                // Mantener transparencia
                imagecolortransparent($newImage, imagecolorallocatealpha($newImage, 0, 0, 0, 127));
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
                break;
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($imagePath);
                break;
            case IMAGETYPE_WEBP:
                $source = imagecreatefromwebp($imagePath);
                break;
            default:
                return; // No redimensionar tipos desconocidos
        }
        
        // Redimensionar
        imagecopyresampled($newImage, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        // Guardar según tipo
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($newImage, $imagePath, 85);
                break;
            case IMAGETYPE_PNG:
                imagepng($newImage, $imagePath, 9);
                break;
            case IMAGETYPE_GIF:
                imagegif($newImage, $imagePath);
                break;
            case IMAGETYPE_WEBP:
                imagewebp($newImage, $imagePath, 85);
                break;
        }
        
        // Liberar memoria
        imagedestroy($source);
        imagedestroy($newImage);
    }
    
    /**
     * Eliminar archivo
     */
    public static function deleteFile($filename, $folder = 'profiles') {
        $uploadsPath = Database::getUploadsPath();
        $filePath = $uploadsPath . $folder . '/' . $filename;
        
        if (file_exists($filePath) && is_file($filePath)) {
            return unlink($filePath);
        }
        
        return false;
    }
    
    /**
     * Obtener URL de foto
     */
    public static function getPhotoUrl($filename, $folder = 'profiles') {
        if (empty($filename)) {
            return Database::getUploadsUrl() . 'profiles/default.png';
        }
        
        return Database::getUploadsUrl() . $folder . '/' . $filename;
    }
}
<?php
// navigation_helper.php
class NavigationHelper {
    
    /**
     * Guardar la URL actual en el historial
     */
    public static function pushUrl($url = null) {
        if (!isset($_SESSION['nav_history'])) {
            $_SESSION['nav_history'] = [];
        }
        
        $current_url = $url ?: self::getCurrentUrl();
        
        // No guardar URLs duplicadas consecutivas
        $last_url = end($_SESSION['nav_history']);
        if ($last_url !== $current_url) {
            $_SESSION['nav_history'][] = $current_url;
            
            // Limitar a 10 URLs máximo
            if (count($_SESSION['nav_history']) > 10) {
                array_shift($_SESSION['nav_history']);
            }
        }
    }
    
    /**
     * Obtener la URL anterior
     */
    public static function getPreviousUrl() {
        if (!isset($_SESSION['nav_history']) || count($_SESSION['nav_history']) < 2) {
            return 'data_referidos.php';
        }
        
        $history = $_SESSION['nav_history'];
        $current_url = self::getCurrentUrl();
        $previous_url = $history[count($history) - 2];
        
        // Si por alguna razón es la misma, intentar con la anterior
        if ($previous_url === $current_url && count($history) >= 3) {
            $previous_url = $history[count($history) - 3];
        }
        
        return $previous_url;
    }
    
    /**
     * Obtener la URL actual completa
     */
    public static function getCurrentUrl() {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }
    
    /**
     * Limpiar el historial
     */
    public static function clearHistory() {
        unset($_SESSION['nav_history']);
    }
    
    /**
     * Imprimir historial para debugging
     */
    public static function debugHistory() {
        if (isset($_SESSION['nav_history'])) {
            echo '<pre>';
            print_r($_SESSION['nav_history']);
            echo '</pre>';
        }
    }
}
?>
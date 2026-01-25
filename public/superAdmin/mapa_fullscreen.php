<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/UsuarioModel.php';

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");

// Verificar si el usuario está logueado y es SuperAdmin
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] !== 'SuperAdmin') {
    echo '<script>window.close();</script>';
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapa Full Screen - SGP</title>
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
          crossorigin=""/>
    
    <!-- Leaflet JavaScript -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
            crossorigin=""></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #1a1a2e;
            height: 100vh;
            overflow: hidden;
        }
        
        /* El Mapa */
        #map {
            width: 100%;
            height: 100vh;
        }
        
        /* Botón cerrar */
        #btn-cerrar {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(244, 67, 54, 0.9);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
        }
        
        #btn-cerrar:hover {
            background: rgba(244, 67, 54, 1);
            transform: translateY(-2px);
        }
        
        /* Leaflet Customization (MISMO que tu mapa) */
        .leaflet-control-zoom {
            border: 2px solid #4a5568 !important;
            border-radius: 6px !important;
            overflow: hidden;
        }
        
        .leaflet-control-zoom a {
            background: #2d3748 !important;
            color: #e2e8f0 !important;
            border-bottom: 1px solid #4a5568 !important;
        }
        
        .leaflet-control-zoom a:hover {
            background: #4a5568 !important;
        }
        
        .leaflet-control-attribution {
            background: rgba(45, 55, 72, 0.9) !important;
            color: #cbd5e0 !important;
            padding: 5px 10px !important;
            border-radius: 4px !important;
            font-size: 0.8rem !important;
        }
        
        .leaflet-control-attribution a {
            color: #4fc3f7 !important;
        }
    </style>
</head>
<body>

    <!-- El Mapa (EXACTAMENTE igual que el tuyo) -->
    <div id="map"></div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // ============ CÓDIGO EXACTO DE TU MAPA ============
        // Coordenadas de Puerto Gaitán, Meta
        const PUERTO_GAITAN = [4.314, -72.082];
        const ZOOM_INICIAL = 12;
        
        // Inicializar mapa centrado en Puerto Gaitán
        const map = L.map('map').setView(PUERTO_GAITAN, ZOOM_INICIAL);
        const capaCalles = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        });
        
        const capaTopografico = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
            attribution: 'Map data: © OpenStreetMap contributors, SRTM | Map style: © OpenTopoMap (CC-BY-SA)',
            maxZoom: 17
        });
        
        // Agregar capa inicial
        capaTopografico.addTo(map);
        // Control de capas
        const capasBase = {
            "Mapa de Calles": capaCalles,
            "Mapa Topográfico": capaTopografico
        };
        
        L.control.layers(capasBase).addTo(map);
        
        // Coordenadas al hacer clic
        const popupCoordenadas = L.popup();
        
        map.on('click', function(e) {
            popupCoordenadas
                .setLatLng(e.latlng)
                .setContent(`
                    <div style="padding: 5px;">
                        <strong>Coordenadas:</strong><br>
                        ${e.latlng.lat.toFixed(6)}° N, ${e.latlng.lng.toFixed(6)}° W
                    </div>
                `)
                .openOn(map);
        });
        
        // Escala
        L.control.scale({imperial: false}).addTo(map);
        
        // Añadir algunos puntos de referencia en Puerto Gaitán (ejemplo)
        const puntosReferencia = [
            {
                nombre: "Alcaldía Municipal",
                coords: [4.3135, -72.080],
                tipo: "gobierno"
            },
            {
                nombre: "Hospital Local",
                coords: [4.315, -72.085],
                tipo: "salud"
            },
            {
                nombre: "Plaza Principal",
                coords: [4.3145, -72.0825],
                tipo: "publico"
            }
        ];
        
        // Iconos personalizados
        const iconos = {
            gobierno: L.divIcon({
                html: '<i class="fas fa-landmark" style="color: #4CAF50; font-size: 20px;"></i>',
                iconSize: [20, 20],
                className: 'custom-icon'
            }),
            salud: L.divIcon({
                html: '<i class="fas fa-hospital" style="color: #f44336; font-size: 20px;"></i>',
                iconSize: [20, 20],
                className: 'custom-icon'
            }),
            publico: L.divIcon({
                html: '<i class="fas fa-map-marker-alt" style="color: #FF9800; font-size: 20px;"></i>',
                iconSize: [20, 20],
                className: 'custom-icon'
            })
        };
        
        // Agregar puntos de referencia
        puntosReferencia.forEach(punto => {
            L.marker(punto.coords, { icon: iconos[punto.tipo] })
                .addTo(map)
                .bindPopup(`<strong>${punto.nombre}</strong><br>Puerto Gaitán, Meta`);
        });
        
        // Ajustar tamaño del mapa
        map.invalidateSize();
        // ============ FIN CÓDIGO EXACTO DE TU MAPA ============
        
        // Botón cerrar
        document.getElementById('btn-cerrar').addEventListener('click', function() {
            window.close();
        });
        
        // Cerrar con tecla ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                window.close();
            }
        });
        
        // Ajustar mapa al redimensionar
        window.addEventListener('resize', function() {
            map.invalidateSize();
        });
        
        // Intentar pantalla completa automática
        function toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(err => {
                    console.log('Error al intentar pantalla completa:', err);
                });
            }
        }
        
        // Intentar pantalla completa después de cargar
        setTimeout(toggleFullscreen, 100);
        
        console.log('Mapa Full Screen cargado (mismo código)');
    });
    </script>
</body>
</html>
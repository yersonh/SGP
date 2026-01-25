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
    header('Location: ../index.php');
    exit();
}

$pdo = Database::getConnection();
$usuarioModel = new UsuarioModel($pdo);
$usuario_logueado = $usuarioModel->getUsuarioById($_SESSION['id_usuario']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapa - Puerto Gaitán | SGP</title>
    
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
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: #e2e8f0;
            height: 100vh;
            overflow: hidden;
        }
        
        /* Header */
        .main-header {
            background: linear-gradient(135deg, #2d3748 0%, #4a5568 100%);
            padding: 15px 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-title h1 {
            font-size: 1.8rem;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .header-title h1 i {
            color: #4fc3f7;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
            color: #cbd5e0;
        }
        
        .header-actions {
            display: flex;
            gap: 15px;
        }
        
        .header-btn {
            background: linear-gradient(135deg, #4fc3f7 0%, #2196f3 100%);
            color: white;
            padding: 8px 15px;
            border-radius: 6px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .header-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(79, 195, 247, 0.3);
        }
        
        .back-btn {
            background: linear-gradient(135deg, #718096 0%, #4a5568 100%);
        }
        
        .back-btn:hover {
            box-shadow: 0 4px 12px rgba(113, 128, 150, 0.3);
        }
        
        /* Main Content */
        .main-container {
            margin-top: 80px;
            height: calc(100vh - 80px);
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        /* Mapa Container */
        .map-container {
            flex: 1;
            background: #2d3748;
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
            display: flex;
            flex-direction: column;
        }
        
        .map-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid #4a5568;
        }
        
        .map-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .map-title h2 {
            font-size: 1.4rem;
            color: #fff;
        }
        
        .map-title h2 i {
            color: #4fc3f7;
        }
        
        .location-badge {
            background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .map-controls {
            display: flex;
            gap: 10px;
        }
        
        .map-btn {
            background: #4a5568;
            color: #e2e8f0;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .map-btn:hover {
            background: #5a6578;
            transform: translateY(-2px);
        }
        
        .map-btn.active {
            background: #4fc3f7;
            color: white;
        }
        
        /* El Mapa */
        #map {
            flex: 1;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid #4a5568;
        }
        
        /* Info Panel */
        .info-panel {
            background: #2d3748;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
            margin-top: 20px;
        }
        
        .info-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            color: #4fc3f7;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .stat-card {
            background: #4a5568;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #4fc3f7;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #fff;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #cbd5e0;
        }
        
        /* Leaflet Customization */
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
        
        /* Responsive */
        @media (max-width: 768px) {
            .main-container {
                padding: 10px;
            }
            
            .map-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .map-controls {
                width: 100%;
                justify-content: space-between;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
        /* Estilos para marcadores de referenciados */
.referenciado-marker {
    transition: all 0.3s ease;
}

.referenciado-marker:hover {
    transform: scale(1.15);
    z-index: 1000 !important;
}

/* Tooltip personalizado */
.referenciado-tooltip {
    background-color: rgba(45, 55, 72, 0.9) !important;
    border: 1px solid #4a5568 !important;
    border-radius: 4px !important;
    color: white !important;
    font-size: 12px !important;
    padding: 4px 8px !important;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
}

.referenciado-tooltip:before {
    border-top-color: #4a5568 !important;
}

/* Estilos para popups */
.leaflet-popup-content-wrapper {
    border-radius: 8px !important;
    border: 2px solid #4a5568 !important;
    background-color: #f7fafc !important;
}

.leaflet-popup-content {
    margin: 15px !important;
    line-height: 1.5 !important;
}

.leaflet-popup-tip {
    background-color: #f7fafc !important;
    border: 2px solid #4a5568 !important;
}

/* Indicador de carga */
.loading-indicator {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: rgba(45, 55, 72, 0.9);
    color: white;
    padding: 15px 25px;
    border-radius: 8px;
    z-index: 1000;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
}
    </style>
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="header-container">
            <div class="header-top">
                <div class="header-title">
                    <h1><i class="fas fa-map-marked-alt"></i> Mapa de Referenciados</h1>
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span>Super Admin: <?php echo htmlspecialchars($usuario_logueado['nombres'] . ' ' . $usuario_logueado['apellidos']); ?></span>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="../superadmin_dashboard.php" class="header-btn back-btn">
                        <i class="fas fa-arrow-left"></i> Volver a Referenciados
                    </a>
                    <a href="../logout.php" class="header-btn">
                        <i class="fas fa-sign-out-alt"></i> Salir
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="main-container">
        <!-- Mapa -->
        <div class="map-container">
            <div class="map-header">
                <div class="map-title">
                    <h2><i class="fas fa-map"></i> Mapa Interactivo</h2>
                    <div class="location-badge">
                        <i class="fas fa-location-dot"></i> Puerto Gaitán, Meta
                    </div>
                </div>
            </div>
            
            <!-- Aquí va el mapa -->
            <div id="map"></div>
        </div>
    </div>

    <script>
   document.addEventListener('DOMContentLoaded', function() {
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
    capaCalles.addTo(map);
    
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
    
    // Agregar puntos de referencia
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
    
    // Iconos personalizados para puntos de referencia
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
    
    // ============ CARGAR REFERENCIADOS EN EL MAPA ============
    // Crear grupo para referenciados
    const grupoReferenciados = L.layerGroup().addTo(map);
    
    // Función para geocodificar dirección
    async function geocodificarDireccion(direccion) {
        try {
            // URL encode la dirección
            const direccionCodificada = encodeURIComponent(direccion);
            
            // Hacer solicitud a Nominatim (OpenStreetMap)
            const response = await fetch(
                `https://nominatim.openstreetmap.org/search?format=json&q=${direccionCodificada}&limit=1&countrycodes=co`,
                {
                    headers: {
                        'User-Agent': 'SistemaGestionPuertoGaitan/1.0',
                        'Accept-Language': 'es'
                    }
                }
            );
            
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data && data.length > 0) {
                return {
                    lat: parseFloat(data[0].lat),
                    lng: parseFloat(data[0].lon),
                    display_name: data[0].display_name
                };
            }
            
            // Si no encuentra exacto, buscar Puerto Gaitán general
            return {
                lat: PUERTO_GAITAN[0],
                lng: PUERTO_GAITAN[1],
                display_name: 'Puerto Gaitán, Meta, Colombia'
            };
            
        } catch (error) {
            console.error('Error en geocodificación:', error);
            // En caso de error, usar coordenadas de Puerto Gaitán
            return {
                lat: PUERTO_GAITAN[0],
                lng: PUERTO_GAITAN[1],
                display_name: 'Puerto Gaitán, Meta, Colombia'
            };
        }
    }
    
    // Función para calcular distancia entre coordenadas (en km)
    function calcularDistancia(lat1, lon1, lat2, lon2) {
        const R = 6371; // Radio de la Tierra en km
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = 
            Math.sin(dLat/2) * Math.sin(dLat/2) +
            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * 
            Math.sin(dLon/2) * Math.sin(dLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c;
    }
    
    // Función para crear marcador de referenciado
    function crearMarcadorReferenciado(ref, lat, lng) {
        // Icono personalizado para referenciado
        const iconoReferenciado = L.divIcon({
            html: `<div style="
                background-color: #4fc3f7;
                border: 2px solid white;
                border-radius: 50%;
                width: 30px;
                height: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-size: 14px;
                box-shadow: 0 2px 5px rgba(0,0,0,0.3);
                cursor: pointer;
            ">
                <i class="fas fa-user"></i>
            </div>`,
            iconSize: [30, 30],
            iconAnchor: [15, 15],
            popupAnchor: [0, -15],
            className: 'referenciado-marker'
        });
        
        // Contenido del popup
        const popupContent = `
            <div style="min-width: 220px; max-width: 300px;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                    <div style="
                        background-color: #4fc3f7;
                        width: 30px;
                        height: 30px;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        color: white;
                    ">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <h4 style="margin: 0; color: #2d3748; font-size: 16px;">${ref.nombre}</h4>
                    </div>
                </div>
                
                <div style="margin-top: 12px;">
                    <p style="margin: 8px 0; line-height: 1.4;">
                        <i class="fas fa-home" style="color: #718096; width: 18px;"></i>
                        <strong style="color: #4a5568;">Dirección:</strong><br>
                        <span style="color: #2d3748;">${ref.direccion || 'No especificada'}</span>
                    </p>
                    
                    ${ref.barrio ? `
                    <p style="margin: 8px 0; line-height: 1.4;">
                        <i class="fas fa-map-marker-alt" style="color: #718096; width: 18px;"></i>
                        <strong style="color: #4a5568;">Barrio:</strong><br>
                        <span style="color: #2d3748;">${ref.barrio}</span>
                    </p>
                    ` : ''}
                </div>
                
                <div style="margin-top: 15px; padding-top: 10px; border-top: 1px solid #e2e8f0;">
                    <small style="color: #a0aec0; font-size: 11px;">
                        <i class="fas fa-info-circle"></i>
                        Ubicación aproximada basada en dirección
                    </small>
                </div>
            </div>
        `;
        
        // Crear marcador
        const marcador = L.marker([lat, lng], { 
            icon: iconoReferenciado,
            title: ref.nombre
        })
        .addTo(grupoReferenciados)
        .bindPopup(popupContent);
        
        // Agregar tooltip
        marcador.bindTooltip(ref.nombre, {
            permanent: false,
            direction: 'top',
            className: 'referenciado-tooltip'
        });
        
        return marcador;
    }
    
    // Función para cargar referenciados del servidor
    async function cargarReferenciados() {
        console.log('Cargando referenciados...');
        
        try {
            const response = await fetch('get_referenciados_mapa.php');
            const data = await response.json();
            
            if (data.success) {
                console.log(`Total referenciados a procesar: ${data.total}`);
                
                // Contadores
                let procesados = 0;
                let cargados = 0;
                let errores = 0;
                
                // Procesar cada referenciado con delay para respetar límites de API
                for (const ref of data.referenciados) {
                    try {
                        // Esperar 1 segundo entre solicitudes (límite de Nominatim)
                        await new Promise(resolve => setTimeout(resolve, 1000));
                        
                        // Geocodificar dirección
                        const geocodificacion = await geocodificarDireccion(ref.direccion_completa);
                        
                        // Verificar si está cerca de Puerto Gaitán (50km máximo)
                        const distancia = calcularDistancia(
                            geocodificacion.lat,
                            geocodificacion.lng,
                            PUERTO_GAITAN[0],
                            PUERTO_GAITAN[1]
                        );
                        
                        if (distancia <= 50) {
                            crearMarcadorReferenciado(ref, geocodificacion.lat, geocodificacion.lng);
                            cargados++;
                            console.log(`✓ ${ref.nombre} - ${ref.direccion.substring(0, 30)}...`);
                        } else {
                            console.warn(`✗ ${ref.nombre} - Fuera de Puerto Gaitán (${distancia.toFixed(1)} km)`);
                            errores++;
                        }
                        
                    } catch (error) {
                        console.error(`Error procesando ${ref.nombre}:`, error);
                        errores++;
                    }
                    
                    procesados++;
                    
                    // Mostrar progreso cada 5 registros
                    if (procesados % 5 === 0) {
                        console.log(`Progreso: ${procesados}/${data.total} procesados`);
                    }
                }
                
                console.log(`Proceso completado: ${cargados} cargados, ${errores} errores`);
                
                // Agregar capa de referenciados al control de capas
                const capasControl = L.control.layers(capasBase, {
                    "Personas Referenciadas": grupoReferenciados
                }).addTo(map);
                
            } else {
                console.error('Error en respuesta del servidor:', data.message);
            }
            
        } catch (error) {
            console.error('Error cargando referenciados:', error);
        }
    }
    
    // Llamar a la función para cargar referenciados
    cargarReferenciados();
    
    // ============ BOTÓN DE PANTALLA COMPLETA ============
    // Crear control personalizado para pantalla completa
    const FullscreenControl = L.Control.extend({
        options: {
            position: 'bottomleft'
        },
        
        onAdd: function(map) {
            const container = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
            container.style.backgroundColor = '#2d3748';
            container.style.border = '2px solid #4a5568';
            container.style.borderRadius = '6px';
            container.style.overflow = 'hidden';
            container.style.marginLeft = '10px';
            container.style.marginBottom = '20px';
            
            const button = L.DomUtil.create('a', '', container);
            button.href = '#';
            button.title = 'Pantalla completa';
            button.style.width = '36px';
            button.style.height = '36px';
            button.style.display = 'flex';
            button.style.alignItems = 'center';
            button.style.justifyContent = 'center';
            button.style.color = '#4a5568'; 
            button.style.textDecoration = 'none';
            button.innerHTML = '<i class="fas fa-expand"></i>';
            
            L.DomEvent.on(button, 'click', L.DomEvent.stopPropagation)
                      .on(button, 'click', L.DomEvent.preventDefault)
                      .on(button, 'click', function() {
                          abrirPantallaCompleta();
                      });
            
            L.DomEvent.on(button, 'mouseover', function() {
                button.style.backgroundColor = '#4a5568';
                button.style.color = 'white';
            });
            
            L.DomEvent.on(button, 'mouseout', function() {
                button.style.backgroundColor = '#2d3748';
                button.style.color = '#e2e8f0';
            });
            
            return container;
        }
    });

    // Agregar el control al mapa
    map.addControl(new FullscreenControl());

    // Función para abrir pantalla completa
    function abrirPantallaCompleta() {
        const ancho = window.screen.width;
        const alto = window.screen.height;
        
        const features = `
            width=${ancho},
            height=${alto},
            left=0,
            top=0,
            scrollbars=no,
            resizable=yes,
            fullscreen=yes,
            location=no,
            menubar=no,
            toolbar=no,
            status=no
        `;
        
        // Abrir el archivo de pantalla completa
        window.open('mapa_fullscreen.php', 'MapaFullScreen', features);
    }
    // ============ FIN BOTÓN PANTALLA COMPLETA ============
    
    // ============ AGREGAR ESTILOS DINÁMICOS ============
    // Agregar estilos para referenciados
    const estilo = document.createElement('style');
    estilo.textContent = `
        .referenciado-marker {
            transition: all 0.3s ease;
        }
        
        .referenciado-marker:hover {
            transform: scale(1.15);
            z-index: 1000 !important;
        }
        
        .referenciado-tooltip {
            background-color: rgba(45, 55, 72, 0.9) !important;
            border: 1px solid #4a5568 !important;
            border-radius: 4px !important;
            color: white !important;
            font-size: 12px !important;
            padding: 4px 8px !important;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
        }
        
        .referenciado-tooltip:before {
            border-top-color: #4a5568 !important;
        }
    `;
    document.head.appendChild(estilo);
    
    // Log para depuración
    console.log('Mapa de Puerto Gaitán cargado correctamente');
    console.log('Coordenadas:', PUERTO_GAITAN);
    console.log('Zoom inicial:', ZOOM_INICIAL);
    console.log('Botón de pantalla completa añadido');
    console.log('Sistema de referenciados inicializado');
});
    </script>
</body>
</html>
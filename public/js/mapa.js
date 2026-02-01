// mapa.js - Todo el JavaScript del mapa

// Variables globales del mapa
let map;
let capaCalles, capaTopografico;
let puntosLayerGroup = L.layerGroup();
let referenciadosLayerGroup = L.layerGroup(); // NUEVO: Capa para referenciados
let marcadorSeleccion = null;
let modalAbierto = false;
let editandoPuntoId = null;
let userLocationMarker = null;
let isTrackingUser = false;
let watchId = null;

// Constantes del mapa
const PUERTO_GAITAN = [4.314, -72.082];
const ZOOM_INICIAL = 12;

// Variables desde PHP (se asignan después de cargar el DOM)
let currentLatLng = PUERTO_GAITAN;
let currentZoom = ZOOM_INICIAL;
let puntosGuardados = [];
let tiposPuntos = {};
let coloresMarcadores = {};
let isFullscreen = false;
let idUsuario = null;

// NUEVO: Variable para referenciados
let referenciadoresActivos = [];

// Cache para geocodificación (para no repetir solicitudes)
const geocodingCache = new Map();

// Estadísticas de geocodificación
let estadisticasGeocodificacion = {
    total: 0,
    exitosas: 0,
    aproximadas: 0,
    fallidas: 0,
    cacheHits: 0
};

// Inicializar cuando el DOM esté cargado
document.addEventListener('DOMContentLoaded', function() {
    // Asignar variables desde PHP
    if (window.PHP_VARS) {
        isFullscreen = PHP_VARS.isFullscreen;
        puntosGuardados = PHP_VARS.puntosUsuario || [];
        tiposPuntos = PHP_VARS.tiposPuntos || {};
        coloresMarcadores = PHP_VARS.coloresMarcadores || {};
        idUsuario = PHP_VARS.idUsuario;
        referenciadoresActivos = PHP_VARS.referenciadoresActivos || [];
    }
    
    // Intentar recuperar la posición del mapa desde sessionStorage
    if (!isFullscreen) {
        const savedPosition = sessionStorage.getItem('mapPosition');
        const savedZoom = sessionStorage.getItem('mapZoom');
        
        if (savedPosition) {
            try {
                currentLatLng = JSON.parse(savedPosition);
                currentZoom = savedZoom ? parseInt(savedZoom) : ZOOM_INICIAL;
            } catch(e) {
                console.log('No se pudo recuperar la posición guardada');
            }
        }
    }
    
    // Inicializar mapa
    map = L.map('map').setView(currentLatLng, currentZoom);
    
    // Configurar capas
    capaCalles = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
    });
    
    capaTopografico = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
        attribution: 'Map data: © OpenStreetMap contributors, SRTM | Map style: © OpenTopoMap (CC-BY-SA)',
        maxZoom: 17
    });
    
    // Agregar capa inicial
    capaCalles.addTo(map);
    puntosLayerGroup.addTo(map);
    referenciadosLayerGroup.addTo(map); // NUEVO: Agregar capa de referenciados
    
    // Control de capas
    const capasBase = {
        "Mapa de Calles": capaCalles,
        "Mapa Topográfico": capaTopografico
    };
    
    const capasOverlay = {
        "Mis Puntos": puntosLayerGroup,
        "Referenciados": referenciadosLayerGroup // NUEVO: Capa de referenciados
    };
    
    L.control.layers(capasBase, capasOverlay).addTo(map);
    
    // Coordenadas al hacer clic
    const popupCoordenadas = L.popup();
    
    // Evento de clic en el mapa
    map.on('click', function(e) {
        console.log('Click en mapa:', e.latlng.lat, e.latlng.lng);
        
        // Mostrar coordenadas en popup
        popupCoordenadas
            .setLatLng(e.latlng)
            .setContent(`
                <div style="padding: 10px; min-width: 200px;">
                    <strong style="color: #4fc3f7;">Coordenadas seleccionadas</strong><br>
                    <div style="margin-top: 5px; font-family: monospace;">
                        Lat: ${e.latlng.lat.toFixed(6)}° N<br>
                        Lng: ${e.latlng.lng.toFixed(6)}° W
                    </div>
                </div>
            `)
            .openOn(map);
        
        // Si el modal está abierto, establecer las coordenadas
        if (modalAbierto) {
            establecerCoordenadasDesdeMapa(e.latlng.lat, e.latlng.lng);
            
            // Crear o mover marcador de selección
            if (marcadorSeleccion) {
                marcadorSeleccion.setLatLng(e.latlng);
            } else {
                marcadorSeleccion = L.marker(e.latlng, {
                    icon: crearIconoMarcador('#4fc3f7'),
                    draggable: true,
                    zIndexOffset: 1000
                }).addTo(map);
                
                // Evento de arrastre del marcador
                marcadorSeleccion.on('dragend', function() {
                    const pos = marcadorSeleccion.getLatLng();
                    establecerCoordenadasDesdeMapa(pos.lat, pos.lng);
                    console.log('Marcador arrastrado a:', pos.lat, pos.lng);
                });
            }
        }
    });
    
    // Guardar posición del mapa cuando se mueva
    map.on('moveend', function() {
        if (!isFullscreen) {
            const center = map.getCenter();
            const zoom = map.getZoom();
            
            // Guardar en sessionStorage
            sessionStorage.setItem('mapPosition', JSON.stringify([center.lat, center.lng]));
            sessionStorage.setItem('mapZoom', zoom.toString());
        }
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
    
    // Cargar puntos iniciales
    cargarPuntosEnMapa();
    
    // Inicializar funcionalidades según el modo
    if (isFullscreen) {
        initFullscreenMode();
    } else {
        initNormalMode();
    }
    
    // Inicializar geolocalización
    setTimeout(() => {
        initGeolocation();
    }, 1000);
    
    // Log para depuración
    console.log('Mapa de Puerto Gaitán cargado correctamente');
    console.log('Modo fullscreen:', isFullscreen);
    console.log('Puntos cargados:', puntosGuardados.length);
    console.log('Referenciadores activos:', referenciadoresActivos.length);
    console.log('Posición inicial:', currentLatLng);
    console.log('Zoom inicial:', currentZoom);
});

// ============ FUNCIONES COMUNES ============

// Función para crear icono personalizado
function crearIconoMarcador(color) {
    return L.divIcon({
        html: `<div style="
            background-color: ${color};
            border: 3px solid white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
            cursor: pointer;
        ">
            <i class="fas fa-map-pin"></i>
        </div>`,
        iconSize: [24, 24],
        iconAnchor: [12, 12],
        popupAnchor: [0, -12],
        className: 'custom-marker'
    });
}

// Función para crear icono de referenciado basado en afinidad
function crearIconoReferenciado(afinidad) {
    // Determinar color basado en afinidad (1-5)
    let color;
    let tamaño = 24; // Tamaño base
    
    switch(parseInt(afinidad)) {
        case 5:
            color = '#4CAF50'; // Verde - Alta afinidad
            tamaño = 30;
            break;
        case 4:
            color = '#8BC34A'; // Verde claro
            tamaño = 26;
            break;
        case 3:
            color = '#FFC107'; // Amarillo - Afinidad media
            break;
        case 2:
            color = '#FF9800'; // Naranja - Baja afinidad
            break;
        case 1:
            color = '#F44336'; // Rojo - Muy baja afinidad
            break;
        default:
            color = '#9E9E9E'; // Gris - Sin afinidad
    }
    
    return L.divIcon({
        html: `
            <div style="
                background-color: ${color};
                border: 2px solid white;
                border-radius: 50%;
                width: ${tamaño}px;
                height: ${tamaño}px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-size: ${tamaño * 0.4}px;
                font-weight: bold;
                box-shadow: 0 2px 5px rgba(0,0,0,0.3);
                cursor: pointer;
            ">
                <i class="fas fa-user"></i>
            </div>
        `,
        iconSize: [tamaño, tamaño],
        iconAnchor: [tamaño / 2, tamaño / 2],
        popupAnchor: [0, -tamaño / 2],
        className: 'referenciado-marker'
    });
}

// Función para crear icono de referenciado aproximado
function crearIconoReferenciadoAproximado(afinidad) {
    // Mismo color que antes
    let color;
    let tamaño = 24;
    
    switch(parseInt(afinidad)) {
        case 5: color = '#4CAF50'; tamaño = 30; break;
        case 4: color = '#8BC34A'; tamaño = 26; break;
        case 3: color = '#FFC107'; break;
        case 2: color = '#FF9800'; break;
        case 1: color = '#F44336'; break;
        default: color = '#9E9E9E';
    }
    
    return L.divIcon({
        html: `
            <div style="
                background-color: ${color};
                border: 2px dashed white;
                border-radius: 50%;
                width: ${tamaño}px;
                height: ${tamaño}px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-size: ${tamaño * 0.4}px;
                font-weight: bold;
                box-shadow: 0 2px 5px rgba(0,0,0,0.3);
                cursor: pointer;
                opacity: 0.8;
            ">
                <i class="fas fa-question"></i>
            </div>
        `,
        iconSize: [tamaño, tamaño],
        iconAnchor: [tamaño / 2, tamaño / 2],
        popupAnchor: [0, -tamaño / 2],
        className: 'referenciado-marker-aproximado'
    });
}

// ============ FUNCIONES DE GEOLOCALIZACIÓN MEJORADAS ============

// Función para analizar la dirección y determinar estrategia
function analizarDireccion(direccion) {
    const direccionLower = direccion.toLowerCase();
    
    // Detectar municipios
    let municipio = null;
    const municipios = [
        'puerto gaitán', 'villavicencio', 'cabuyaro', 'san martín', 'san martin', 
        'granada', 'fuente de oro', 'puerto lópez', 'puerto lopez', 'puerto rico',
        'acacías', 'cumaral', 'barranca de upía', 'castilla la nueva', 'cubarral',
        'cumaral', 'el castillo', 'el dorado', 'fuente de oro', 'granada',
        'guamal', 'mapiripán', 'mesetas', 'la macarena', 'la uribe', 'lejanías',
        'puerto concordia', 'puerto gaitán', 'puerto lleras', 'puerto lópez',
        'puerto rico', 'restrepo', 'san carlos de guaroa', 'san juan de arama',
        'san juanito', 'san martín', 'vistahermosa'
    ];
    
    for (const muni of municipios) {
        if (direccionLower.includes(muni)) {
            municipio = muni;
            break;
        }
    }
    
    // Detectar veredas/corregimientos
    let vereda = null;
    const veredas = [
        'santa bárbara', 'santa barbara', 'wacoyo', 'getsemaní', 'getsemani',
        'pueblo nuevo', 'brisas del guayuriba', 'flor amarillo', 'villa flor',
        'la serranía', 'la serrania', 'el centro', 'centro', 'barrio centro',
        'villa flor', 'flor amarillo', 'brisas del guayuriba', 'guayuriba',
        'las brisas', 'san isidro', 'san jose', 'san jose', 'san pedro',
        'san antonio', 'san miguel', 'san luis', 'san francisco', 'santa ana',
        'santa rosa', 'santa teresa', 'santa cruz', 'santa lucia', 'santa maria',
        'santa fe', 'santa elena', 'santa isabel', 'santa monica', 'santa rita',
        'santa sofia', 'santa catalina', 'santa ines', 'santa adelaida'
    ];
    
    for (const ver of veredas) {
        if (direccionLower.includes(ver)) {
            vereda = ver;
            break;
        }
    }
    
    // Extraer dirección base (sin municipio/vereda repetido)
    let direccionBase = direccion;
    if (municipio) {
        direccionBase = direccionBase.replace(new RegExp(municipio, 'gi'), '').trim();
    }
    if (vereda) {
        direccionBase = direccionBase.replace(new RegExp(vereda, 'gi'), '').trim();
    }
    
    // Limpiar caracteres extra
    direccionBase = direccionBase
        .replace(/\s+/g, ' ')
        .replace(/,\s*,/g, ',')
        .trim()
        .replace(/,$/, '');
    
    // Si quedó vacío, usar la dirección original
    if (!direccionBase) {
        direccionBase = direccion;
    }
    
    return {
        municipio,
        vereda,
        direccionBase: direccionBase || direccion,
        tieneCoordenadasEspecificas: /cra|calle|cll|kr|kra|av|avenida|diag|trans|manzana|mz|casa|#|numero|no\.|\d+\s*[-\–]\s*\d+/i.test(direccion)
    };
}

// Función para buscar en Nominatim con parámetros específicos
async function buscarEnNominatim(direccion, countryCode = null, estrategia = {}) {
    try {
        // Construir query parameters
        const params = new URLSearchParams({
            q: direccion,
            format: 'json',
            limit: '1',
            addressdetails: '1',
            'accept-language': 'es'
        });
        
        // Agregar countrycode si se especifica
        if (countryCode) {
            params.append('countrycodes', countryCode);
        }
        
        // Si tiene coordenadas específicas, priorizar importancia
        if (estrategia.tieneCoordenadasEspecificas) {
            params.append('dedupe', '0'); // No deduplicar
        }
        
        const url = `https://nominatim.openstreetmap.org/search?${params.toString()}`;
        
        const response = await fetch(url, {
            headers: {
                'User-Agent': 'SistemaGestionPuertoGaitan/1.0 (contacto@ejemplo.com)',
                'Referer': window.location.origin
            }
        });
        
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data && data.length > 0) {
            const item = data[0];
            
            // Verificar que sea en Colombia
            if (item.address && item.address.country_code === 'co') {
                // Priorizar resultados en Meta si estamos buscando en Meta
                if (countryCode === 'co' && item.address.state === 'Meta') {
                    return {
                        lat: parseFloat(item.lat),
                        lng: parseFloat(item.lon),
                        display_name: item.display_name,
                        importancia: item.importance || 0,
                        tipo: item.type || 'node',
                        address: item.address,
                        esAproximado: false
                    };
                }
                
                // Si no está en Meta pero es buena coincidencia, aceptarla
                if (item.importance > 0.3) {
                    return {
                        lat: parseFloat(item.lat),
                        lng: parseFloat(item.lon),
                        display_name: item.display_name,
                        importancia: item.importance || 0,
                        tipo: item.type || 'node',
                        address: item.address,
                        esAproximado: false
                    };
                }
            }
        }
        
        return null;
    } catch (error) {
        console.error('Error en búsqueda Nominatim:', error);
        return null;
    }
}

// Función para coordenadas aproximadas de lugares conocidos
function obtenerCoordenadasAproximadas(estrategia) {
    const coordenadasConocidas = {
        // Puerto Gaitán (centro)
        'puerto gaitán': { lat: 4.314, lng: -72.082 },
        
        // Veredas de Puerto Gaitán
        'santa bárbara': { lat: 4.35, lng: -72.05 },
        'santa barbara': { lat: 4.35, lng: -72.05 },
        'wacoyo': { lat: 4.28, lng: -72.12 },
        'getsemaní': { lat: 4.40, lng: -72.15 },
        'getsemani': { lat: 4.40, lng: -72.15 },
        'pueblo nuevo': { lat: 4.25, lng: -72.20 },
        'flor amarillo': { lat: 4.30, lng: -72.07 },
        'villa flor': { lat: 4.31, lng: -72.08 },
        'brisas del guayuriba': { lat: 4.32, lng: -72.09 },
        'guayuriba': { lat: 4.33, lng: -72.10 },
        
        // Villavicencio
        'villavicencio': { lat: 4.142, lng: -73.626 },
        'el centro': { lat: 4.142, lng: -73.626 },
        'centro': { lat: 4.142, lng: -73.626 },
        'barrio centro': { lat: 4.142, lng: -73.626 },
        'la serranía': { lat: 4.135, lng: -73.615 },
        'la serrania': { lat: 4.135, lng: -73.615 },
        
        // Otros municipios del Meta
        'cabuyaro': { lat: 4.285, lng: -72.793 },
        'san martín': { lat: 3.696, lng: -73.698 },
        'san martin': { lat: 3.696, lng: -73.698 },
        'granada': { lat: 3.546, lng: -73.707 },
        'acacías': { lat: 3.987, lng: -73.772 },
        'cumaral': { lat: 4.269, lng: -73.487 },
        'castilla la nueva': { lat: 3.826, lng: -73.689 },
        'cubarral': { lat: 3.794, lng: -73.836 },
        'el castillo': { lat: 3.564, lng: -73.794 },
        'el dorado': { lat: 2.779, lng: -72.871 },
        'fuente de oro': { lat: 3.462, lng: -73.619 },
        'guamal': { lat: 3.880, lng: -73.766 },
        'mapiripán': { lat: 2.891, lng: -72.133 },
        'mesetas': { lat: 3.384, lng: -74.046 },
        'la macarena': { lat: 2.179, lng: -74.785 },
        'la uribe': { lat: 3.236, lng: -74.357 },
        'lejanías': { lat: 3.527, lng: -74.024 },
        'puerto concordia': { lat: 2.622, lng: -72.758 },
        'puerto lleras': { lat: 3.273, lng: -73.376 },
        'puerto lópez': { lat: 4.089, lng: -72.955 },
        'puerto rico': { lat: 2.938, lng: -73.208 },
        'restrepo': { lat: 4.261, lng: -73.561 },
        'san carlos de guaroa': { lat: 3.711, lng: -73.242 },
        'san juan de arama': { lat: 3.373, lng: -73.889 },
        'san juanito': { lat: 4.458, lng: -73.675 },
        'vistahermosa': { lat: 3.124, lng: -73.751 },
        
        // Barrios comunes
        'san isidro': { lat: 4.15, lng: -73.63 },
        'san jose': { lat: 4.145, lng: -73.62 },
        'san pedro': { lat: 4.155, lng: -73.635 },
        'san antonio': { lat: 4.14, lng: -73.625 },
        'san miguel': { lat: 4.138, lng: -73.628 },
        'san luis': { lat: 4.132, lng: -73.622 },
        'san francisco': { lat: 4.136, lng: -73.63 },
        'santa ana': { lat: 4.142, lng: -73.618 },
        'santa rosa': { lat: 4.148, lng: -73.615 },
        'santa teresa': { lat: 4.134, lng: -73.612 },
        'santa cruz': { lat: 4.128, lng: -73.608 },
        'santa lucia': { lat: 4.122, lng: -73.605 },
        'santa maria': { lat: 4.118, lng: -73.602 },
        'santa fe': { lat: 4.125, lng: -73.598 },
        'santa elena': { lat: 4.13, lng: -73.592 },
        'santa isabel': { lat: 4.135, lng: -73.588 },
        'santa monica': { lat: 4.14, lng: -73.585 },
        'santa rita': { lat: 4.145, lng: -73.582 },
        'santa sofia': { lat: 4.15, lng: -73.578 },
        'santa catalina': { lat: 4.155, lng: -73.575 },
        'santa ines': { lat: 4.16, lng: -73.572 },
        'santa adelaida': { lat: 4.165, lng: -73.568 }
    };
    
    // Buscar coincidencia exacta primero
    for (const [key, coords] of Object.entries(coordenadasConocidas)) {
        if (estrategia.municipio === key) {
            return { ...coords, esAproximado: true, lugar: key };
        }
        if (estrategia.vereda === key) {
            return { ...coords, esAproximado: true, lugar: key };
        }
    }
    
    // Buscar coincidencia parcial
    for (const [key, coords] of Object.entries(coordenadasConocidas)) {
        if (estrategia.municipio && estrategia.municipio.includes(key)) {
            return { ...coords, esAproximado: true, lugar: key };
        }
        if (estrategia.vereda && estrategia.vereda.includes(key)) {
            return { ...coords, esAproximado: true, lugar: key };
        }
    }
    
    return null;
}

// Función mejorada para geocodificar una dirección
async function geocodificarDireccion(direccion) {
    try {
        // Limpiar y formatear la dirección
        const direccionLimpia = direccion.trim();
        if (!direccionLimpia) {
            console.log('Dirección vacía');
            return null;
        }

        // Verificar si ya tenemos esta dirección en caché
        const cacheKey = direccionLimpia.toLowerCase();
        if (geocodingCache.has(cacheKey)) {
            console.log('Usando dirección de caché:', direccionLimpia);
            estadisticasGeocodificacion.cacheHits++;
            return geocodingCache.get(cacheKey);
        }

        console.log('Geocodificando:', direccionLimpia);

        // Analizar la dirección para determinar la mejor estrategia de búsqueda
        const estrategia = analizarDireccion(direccionLimpia);
        
        // Intentar diferentes estrategias en orden
        let resultado = null;
        
        // ESTRATEGIA 1: Si tiene municipio específico, buscar ahí primero
        if (estrategia.municipio) {
            const busqueda = `${estrategia.direccionBase}, ${estrategia.municipio}, Meta, Colombia`;
            resultado = await buscarEnNominatim(busqueda, 'CO', estrategia);
        }
        
        // ESTRATEGIA 2: Búsqueda general en Colombia
        if (!resultado) {
            resultado = await buscarEnNominatim(`${estrategia.direccionBase}, Colombia`, 'CO', estrategia);
        }
        
        // ESTRATEGIA 3: Si no funciona, buscar solo en Meta
        if (!resultado) {
            resultado = await buscarEnNominatim(`${estrategia.direccionBase}, Meta, Colombia`, 'CO', estrategia);
        }
        
        // ESTRATEGIA 4: Si no funciona, buscar sin restricciones de país
        if (!resultado) {
            resultado = await buscarEnNominatim(estrategia.direccionBase, null, estrategia);
        }
        
        // ESTRATEGIA 5: Búsqueda aproximada por vereda/zona
        if (!resultado) {
            resultado = obtenerCoordenadasAproximadas(estrategia);
        }

        // Actualizar estadísticas
        estadisticasGeocodificacion.total++;
        if (resultado) {
            if (resultado.esAproximado) {
                estadisticasGeocodificacion.aproximadas++;
            } else {
                estadisticasGeocodificacion.exitosas++;
            }
            
            // Guardar en caché
            geocodingCache.set(cacheKey, resultado);
            console.log('Geocodificación exitosa:', resultado);
            return resultado;
        } else {
            estadisticasGeocodificacion.fallidas++;
            console.log('No se encontraron coordenadas para:', direccionLimpia);
            // Guardar null en caché para no intentar de nuevo
            geocodingCache.set(cacheKey, null);
            return null;
        }
    } catch (error) {
        console.error('Error en geocodificación:', error);
        estadisticasGeocodificacion.fallidas++;
        return null;
    }
}

// Función para geocodificar con reintentos
async function geocodificarConReintentos(direccion, maxReintentos = 2) {
    for (let intento = 1; intento <= maxReintentos; intento++) {
        try {
            const resultado = await geocodificarDireccion(direccion);
            if (resultado) return resultado;
            
            if (intento < maxReintentos) {
                console.log(`Reintentando (${intento}/${maxReintentos})...`);
                await new Promise(resolve => setTimeout(resolve, 2000)); // 2 segundos entre reintentos
            }
        } catch (error) {
            console.error(`Intento ${intento} fallado:`, error);
            if (intento === maxReintentos) throw error;
        }
    }
    return null;
}

// Función para establecer coordenadas desde el mapa
function establecerCoordenadasDesdeMapa(lat, lng) {
    console.log('Estableciendo coordenadas desde mapa:', lat, lng);
    
    document.getElementById('latitud').value = lat;
    document.getElementById('longitud').value = lng;
    document.getElementById('coordsDisplay').innerHTML = `
        <div style="color: #4fc3f7; font-weight: bold;">Coordenadas seleccionadas</div>
        <div style="font-size: 0.9rem; margin-top: 2px;">
            Lat: ${lat.toFixed(6)}° N<br>
            Lng: ${lng.toFixed(6)}° W
        </div>
    `;
}

// Función para cargar puntos en el mapa
function cargarPuntosEnMapa() {
    puntosLayerGroup.clearLayers();
    
    puntosGuardados.forEach(punto => {
        const icono = crearIconoMarcador(punto.color_marcador || '#4fc3f7');
        const marcador = L.marker([punto.latitud, punto.longitud], { 
            icon: icono,
            title: punto.nombre
        }).addTo(puntosLayerGroup);
        
        // Contenido del popup
        const popupContent = `
            <div style="min-width: 250px; max-width: 300px;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                    <div style="
                        background-color: ${punto.color_marcador || '#4fc3f7'};
                        width: 30px;
                        height: 30px;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        color: white;
                    ">
                        <i class="fas fa-map-pin"></i>
                    </div>
                    <div>
                        <h4 style="margin: 0; color: #2d3748; font-size: 16px;">${punto.nombre}</h4>
                        <small style="color: #718096;">${tiposPuntos[punto.tipo_punto] || punto.tipo_punto}</small>
                    </div>
                </div>
                
                ${punto.descripcion ? `
                <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #e2e8f0;">
                    <p style="margin: 0; color: #4a5568; line-height: 1.4; font-size: 14px;">
                        ${punto.descripcion}
                    </p>
                </div>
                ` : ''}
                
                <div style="margin-top: 12px; padding-top: 10px; border-top: 1px solid #e2e8f0;">
                    <p style="margin: 5px 0; font-size: 12px; color: #a0aec0;">
                        <i class="fas fa-calendar"></i>
                        ${new Date(punto.fecha_creacion).toLocaleDateString('es-ES', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit'
                        })}
                    </p>
                    <p style="margin: 5px 0; font-size: 12px; color: #a0aec0;">
                        <i class="fas fa-user"></i>
                        ${punto.nombres} ${punto.apellidos}
                    </p>
                </div>
                
                <div style="margin-top: 15px;">
                    <button onclick="centrarEnPunto(${punto.latitud}, ${punto.longitud})" 
                            style="background: #4fc3f7; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; width: 100%;">
                        <i class="fas fa-search-location"></i> Centrar en este punto
                    </button>
                </div>
            </div>
        `;
        
        marcador.bindPopup(popupContent);
        
        // Agregar tooltip
        marcador.bindTooltip(punto.nombre, {
            permanent: false,
            direction: 'top',
            className: 'referenciado-tooltip'
        });
    });
}

// ============ FUNCIONES PARA REFERENCIADOS ============

// Función para crear contenido del popup para referenciados
function crearPopupReferenciado(referenciado, coordenadas) {
    // Determinar color de afinidad
    let colorAfinidad;
    let textoAfinidad;
    
    switch(parseInt(referenciado.afinidad)) {
        case 5:
            colorAfinidad = '#4CAF50';
            textoAfinidad = 'Muy Alta';
            break;
        case 4:
            colorAfinidad = '#8BC34A';
            textoAfinidad = 'Alta';
            break;
        case 3:
            colorAfinidad = '#FFC107';
            textoAfinidad = 'Media';
            break;
        case 2:
            colorAfinidad = '#FF9800';
            textoAfinidad = 'Baja';
            break;
        case 1:
            colorAfinidad = '#F44336';
            textoAfinidad = 'Muy Baja';
            break;
        default:
            colorAfinidad = '#9E9E9E';
            textoAfinidad = 'Sin definir';
    }
    
    // Formatear fecha
    let fechaFormateada = 'No disponible';
    if (referenciado.fecha_registro) {
        const fecha = new Date(referenciado.fecha_registro);
        fechaFormateada = fecha.toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    // Determinar ícono de compromiso
    let iconoCompromiso = referenciado.compromiso === 'Si' 
        ? '<i class="fas fa-check-circle" style="color: #4CAF50;"></i>'
        : '<i class="fas fa-times-circle" style="color: #F44336;"></i>';
    
    // Determinar ícono de vota fuera
    let iconoVotaFuera = referenciado.vota_fuera === 'Si'
        ? '<i class="fas fa-external-link-alt" style="color: #FF9800;"></i>'
        : '<i class="fas fa-home" style="color: #4CAF50;"></i>';
    
    // Determinar si son coordenadas aproximadas
    const esAproximado = coordenadas.esAproximado || referenciado.coordenadasAproximadas;
    const lugarAproximado = referenciado.lugarAproximado || coordenadas.lugar;
    
    // Sección de coordenadas
    const seccionCoordenadas = esAproximado 
        ? `
            <div style="background-color: #FFF3CD; padding: 8px; border-radius: 4px; margin-bottom: 15px; border-left: 4px solid #FFC107;">
                <div style="font-size: 12px; color: #856404; margin-bottom: 5px;">
                    <i class="fas fa-exclamation-triangle"></i> <strong>Ubicación Aproximada</strong>
                </div>
                <div style="font-family: monospace; font-size: 11px; color: #856404;">
                    Lat: ${coordenadas.lat.toFixed(6)}<br>
                    Lng: ${coordenadas.lng.toFixed(6)}<br>
                    <small style="color: #856404; font-style: italic;">
                        ${lugarAproximado ? `Aproximado a ${lugarAproximado}` : 'Ubicación estimada'}
                    </small>
                </div>
            </div>
        `
        : `
            <div style="background-color: #edf2f7; padding: 8px; border-radius: 4px; margin-bottom: 15px;">
                <div style="font-size: 12px; color: #2d3748; margin-bottom: 5px;">
                    <i class="fas fa-crosshairs"></i> <strong>Coordenadas:</strong>
                </div>
                <div style="font-family: monospace; font-size: 11px; color: #4a5568;">
                    Lat: ${coordenadas.lat.toFixed(6)}<br>
                    Lng: ${coordenadas.lng.toFixed(6)}
                </div>
            </div>
        `;
    
    return `
        <div style="min-width: 280px; max-width: 320px; font-family: Arial, sans-serif;">
            <!-- Encabezado -->
            <div style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); 
                        color: white; padding: 15px; border-radius: 8px 8px 0 0; margin: -10px -10px 10px -10px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="
                        background-color: ${colorAfinidad};
                        width: 40px;
                        height: 40px;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        color: white;
                        font-size: 18px;
                        font-weight: bold;
                        border: 2px solid white;
                    ">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <h3 style="margin: 0; font-size: 16px;">${referenciado.nombre} ${referenciado.apellido}</h3>
                        <p style="margin: 3px 0 0 0; font-size: 12px; opacity: 0.9;">
                            <i class="fas fa-id-card"></i> ${referenciado.cedula}
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Información básica -->
            <div style="margin-bottom: 15px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span style="font-weight: bold; color: #2d3748;">
                        <i class="fas fa-phone"></i> Teléfono:
                    </span>
                    <span style="color: #4a5568;">${referenciado.telefono || 'No registrado'}</span>
                </div>
                
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span style="font-weight: bold; color: #2d3748;">
                        <i class="fas fa-envelope"></i> Email:
                    </span>
                    <span style="color: #4a5568;">${referenciado.email || 'No registrado'}</span>
                </div>
                
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span style="font-weight: bold; color: #2d3748;">
                        <i class="fas fa-calendar-alt"></i> Registro:
                    </span>
                    <span style="color: #4a5568; font-size: 12px;">${fechaFormateada}</span>
                </div>
                
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                    <span style="font-weight: bold; color: #2d3748;">
                        <i class="fas fa-map-marker-alt"></i> Dirección:
                    </span>
                    <span style="color: #4a5568; font-size: 12px; text-align: right;">${referenciado.direccion || 'No registrada'}</span>
                </div>
            </div>
            
            <!-- Sección de afinidad -->
            <div style="background-color: #f7fafc; padding: 10px; border-radius: 6px; margin-bottom: 15px; border-left: 4px solid ${colorAfinidad};">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-weight: bold; color: #2d3748;">Afinidad</div>
                        <div style="display: flex; align-items: center; gap: 5px; margin-top: 3px;">
                            <div style="display: flex;">
                                ${[1,2,3,4,5].map(i => `
                                    <div style="
                                        width: 12px;
                                        height: 12px;
                                        border-radius: 50%;
                                        background-color: ${i <= referenciado.afinidad ? colorAfinidad : '#e2e8f0'};
                                        margin-right: 2px;
                                        border: 1px solid ${i <= referenciado.afinidad ? colorAfinidad : '#cbd5e0'};
                                    "></div>
                                `).join('')}
                            </div>
                            <span style="font-size: 12px; color: ${colorAfinidad}; font-weight: bold;">
                                ${textoAfinidad} (${referenciado.afinidad}/5)
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sección de ubicación y votación -->
            <div style="margin-bottom: 15px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        ${iconoVotaFuera}
                        <span style="font-weight: bold; color: #2d3748;">Vota fuera:</span>
                        <span style="color: #4a5568;">${referenciado.vota_fuera || 'No'}</span>
                    </div>
                    
                    <div style="display: flex; align-items: center; gap: 8px;">
                        ${iconoCompromiso}
                        <span style="font-weight: bold; color: #2d3748;">Compromiso:</span>
                        <span style="color: #4a5568;">${referenciado.compromiso || 'No'}</span>
                    </div>
                </div>
                
                ${referenciado.zona_nombre ? `
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 5px;">
                    <i class="fas fa-map" style="color: #4a5568;"></i>
                    <span style="font-size: 13px; color: #4a5568;">
                        ${referenciado.zona_nombre} ${referenciado.sector_nombre ? ' - ' + referenciado.sector_nombre : ''}
                    </span>
                </div>
                ` : ''}
                
                ${referenciado.puesto_votacion_display ? `
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-vote-yea" style="color: #4a5568;"></i>
                    <span style="font-size: 13px; color: #4a5568;">
                        ${referenciado.puesto_votacion_display} ${referenciado.mesa_display ? ' - Mesa ' + referenciado.mesa_display : ''}
                    </span>
                </div>
                ` : ''}
            </div>
            
            <!-- Coordenadas -->
            ${seccionCoordenadas}
            
            <!-- Estadísticas de geocodificación -->
            ${esAproximado ? `
            <div style="font-size: 10px; color: #999; text-align: center; margin-top: 10px; padding-top: 10px; border-top: 1px solid #eee;">
                <i class="fas fa-info-circle"></i> Estadísticas: ${estadisticasGeocodificacion.exitosas} exactas, ${estadisticasGeocodificacion.aproximadas} aproximadas
            </div>
            ` : ''}
            
            <!-- Acciones -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                <button onclick="centrarEnUbicacion(${coordenadas.lat}, ${coordenadas.lng})" 
                        style="background: #4fc3f7; color: white; border: none; padding: 8px; 
                               border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: bold;
                               display: flex; align-items: center; justify-content: center; gap: 5px;">
                    <i class="fas fa-search-location"></i> Centrar
                </button>
                
                <button onclick="mostrarDetalleReferenciado(${referenciado.id_referenciado})" 
                        style="background: #2d3748; color: white; border: none; padding: 8px; 
                               border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: bold;
                               display: flex; align-items: center; justify-content: center; gap: 5px;">
                    <i class="fas fa-info-circle"></i> Detalles
                </button>
            </div>
        </div>
    `;
}

// Función para cargar referenciados en el mapa
async function cargarReferenciadosEnMapa(referenciadorId = null) {
    console.log('Cargando referenciados para referenciador:', referenciadorId);
    
    // Limpiar capa anterior
    referenciadosLayerGroup.clearLayers();
    
    // Si no hay ID de referenciador, no cargar nada
    if (!referenciadorId) {
        console.log('No se especificó referenciador, limpiando marcadores');
        return;
    }
    
    try {
        // Mostrar indicador de carga
        const loadingIndicator = L.divIcon({
            html: '<div style="color: #FF9800;"><i class="fas fa-spinner fa-spin fa-2x"></i></div>',
            iconSize: [40, 40],
            iconAnchor: [20, 20]
        });
        
        const loadingMarker = L.marker(map.getCenter(), { icon: loadingIndicator })
            .addTo(referenciadosLayerGroup)
            .bindPopup('<div style="padding: 10px;">Cargando referenciados...</div>')
            .openPopup();
        
        // Obtener referenciados del servidor
        const response = await fetch(`../ajax/obtener_referenciados.php?id_referenciador=${referenciadorId}`);
        
        if (!response.ok) {
            throw new Error(`Error HTTP: ${response.status}`);
        }
        
        const result = await response.json();
        
        // Eliminar indicador de carga
        referenciadosLayerGroup.removeLayer(loadingMarker);
        
        if (!result.success) {
            throw new Error(result.message || 'Error al obtener referenciados');
        }
        
        const referenciadosData = result.referenciados || [];
        console.log('Referenciados obtenidos:', referenciadosData.length);
        
        if (referenciadosData.length === 0) {
            // Mostrar mensaje de que no hay referenciados
            const noDataMarker = L.marker(map.getCenter(), {
                icon: L.divIcon({
                    html: '<div style="color: #9E9E9E;"><i class="fas fa-info-circle fa-2x"></i></div>',
                    iconSize: [40, 40],
                    iconAnchor: [20, 20]
                })
            }).addTo(referenciadosLayerGroup)
            .bindPopup('<div style="padding: 10px; text-align: center;">Este referenciador no tiene referenciados activos</div>')
            .openPopup();
            
            return;
        }
        
        // Contador para referenciados con dirección válida
        let referenciadosConCoordenadas = 0;
        const markers = [];
        
        // Mostrar progreso
        const progressInfo = L.divIcon({
            html: `<div style="color: #4fc3f7; text-align: center;">
                      <i class="fas fa-sync fa-spin"></i><br>
                      <small>Geocodificando: 0/${referenciadosData.length}</small>
                   </div>`,
            iconSize: [60, 40],
            iconAnchor: [30, 20]
        });
        
        const progressMarker = L.marker(map.getCenter(), { icon: progressInfo })
            .addTo(referenciadosLayerGroup)
            .bindPopup('<div style="padding: 10px;">Procesando direcciones...</div>')
            .openPopup();
        
        // Procesar cada referenciado
        for (let i = 0; i < referenciadosData.length; i++) {
            const referenciado = referenciadosData[i];
            
            // Actualizar progreso
            progressMarker.getPopup().setContent(`<div style="padding: 10px;">Procesando ${i+1}/${referenciadosData.length} direcciones...</div>`);
            
            // Solo procesar si tiene dirección
            if (referenciado.direccion && referenciado.direccion.trim() !== '') {
                try {
                    // Analizar la dirección primero
                    const estrategia = analizarDireccion(referenciado.direccion);
                    
                    // Intentar geocodificar la dirección
                    let coordenadas = await geocodificarDireccion(referenciado.direccion);
                    
                    // Si no se encontraron coordenadas exactas, usar aproximadas
                    if (!coordenadas) {
                        coordenadas = obtenerCoordenadasAproximadas(estrategia);
                        
                        if (coordenadas) {
                            console.log('Usando coordenadas aproximadas para:', referenciado.direccion);
                            // Marcar como aproximado en el popup
                            referenciado.coordenadasAproximadas = true;
                            referenciado.lugarAproximado = coordenadas.lugar;
                        }
                    }
                    
                    if (coordenadas) {
                        referenciadosConCoordenadas++;
                        
                        // Crear icono basado en afinidad
                        const icono = crearIconoReferenciado(referenciado.afinidad);
                        
                        // Si son coordenadas aproximadas, usar icono diferente
                        const marcadorIcono = coordenadas.esAproximado 
                            ? crearIconoReferenciadoAproximado(referenciado.afinidad)
                            : icono;
                        
                        // Crear marcador
                        const marcador = L.marker([coordenadas.lat, coordenadas.lng], {
                            icon: marcadorIcono,
                            title: `${referenciado.nombre} ${referenciado.apellido}`
                        });
                        
                        // Crear contenido del popup
                        const popupContent = crearPopupReferenciado(referenciado, coordenadas);
                        
                        // Vincular popup
                        marcador.bindPopup(popupContent, {
                            maxWidth: 300,
                            minWidth: 250
                        });
                        
                        // Agregar tooltip
                        marcador.bindTooltip(
                            `${referenciado.nombre} ${referenciado.apellido}`,
                            {
                                permanent: false,
                                direction: 'top',
                                className: 'referenciado-tooltip'
                            }
                        );
                        
                        markers.push(marcador);
                        
                        // Pausa para no sobrecargar el servicio (1.5 segundos entre solicitudes)
                        await new Promise(resolve => setTimeout(resolve, 1500));
                    } else {
                        console.log('No se pudieron obtener coordenadas para:', referenciado.direccion);
                    }
                } catch (error) {
                    console.error('Error procesando referenciado:', referenciado.id_referenciado, error);
                }
            }
        }
        
        // Eliminar marcador de progreso
        referenciadosLayerGroup.removeLayer(progressMarker);
        
        // Agregar todos los marcadores a la capa
        markers.forEach(marker => {
            marker.addTo(referenciadosLayerGroup);
        });
        
        console.log(`Referenciados cargados en mapa: ${referenciadosConCoordenadas}/${referenciadosData.length}`);
        console.log('Estadísticas geocodificación:', estadisticasGeocodificacion);
        
        // Si hay referenciados con coordenadas, ajustar vista
        if (referenciadosConCoordenadas > 0) {
            // Crear bounds con todos los marcadores
            const group = L.featureGroup(markers);
            const bounds = group.getBounds();
            
            if (bounds.isValid()) {
                // Ajustar zoom para mostrar todos los marcadores con un poco de margen
                map.fitBounds(bounds.pad(0.2));
                
                // Mostrar notificación
                showLocationNotification(
                    `Cargados ${referenciadosConCoordenadas} referenciados (${estadisticasGeocodificacion.exitosas} exactos, ${estadisticasGeocodificacion.aproximadas} aproximados)`, 
                    'success'
                );
            } else {
                // Si no se pudo crear bounds, centrar en Meta
                map.setView([3.5, -73], 8);
                showLocationNotification(
                    `Cargados ${referenciadosConCoordenadas} referenciados en diferentes ubicaciones`, 
                    'info'
                );
            }
        } else {
            // Mostrar mensaje si no se pudieron geocodificar direcciones
            const noCoordsMarker = L.marker(map.getCenter(), {
                icon: L.divIcon({
                    html: '<div style="color: #FF9800;"><i class="fas fa-exclamation-triangle fa-2x"></i></div>',
                    iconSize: [40, 40],
                    iconAnchor: [20, 20]
                })
            }).addTo(referenciadosLayerGroup)
            .bindPopup('<div style="padding: 10px; text-align: center;">No se pudieron geocodificar las direcciones de los referenciados</div>')
            .openPopup();
            
            showLocationNotification(
                'No se pudieron geocodificar las direcciones. Intente con direcciones más específicas.', 
                'warning'
            );
        }
        
    } catch (error) {
        console.error('Error al cargar referenciados:', error);
        
        // Mostrar error
        const errorMarker = L.marker(map.getCenter(), {
            icon: L.divIcon({
                html: '<div style="color: #F44336;"><i class="fas fa-exclamation-triangle fa-2x"></i></div>',
                iconSize: [40, 40],
                iconAnchor: [20, 20]
            })
        }).addTo(referenciadosLayerGroup)
        .bindPopup(`<div style="padding: 10px; color: #F44336;">Error al cargar referenciados: ${error.message}</div>`)
        .openPopup();
        
        showLocationNotification('Error al cargar referenciados', 'error');
    }
}

// Función para centrar en una ubicación específica
window.centrarEnUbicacion = function(lat, lng) {
    map.setView([lat, lng], 16);
};

// Función para mostrar detalles del referenciado
window.mostrarDetalleReferenciado = function(id_referenciado) {
    // Abrir en una nueva pestaña o modal
    console.log('Mostrar detalles del referenciado:', id_referenciado);
    // Por ahora, solo mostrar un alert
    alert(`Detalles del referenciado ID: ${id_referenciado}\n\nEsta funcionalidad puede redirigir a una página de detalles o abrir un modal.`);
};

// Función para centrar mapa en un punto
window.centrarEnPunto = function(lat, lng) {
    map.setView([lat, lng], 15);
};

// ============ MODO NORMAL ============

function initNormalMode() {
    console.log('Inicializando modo normal');
    
    // Obtener elementos del DOM
    const pointModal = document.getElementById('pointModal');
    const openAddPointModalBtn = document.getElementById('openAddPointModal');
    const closeModalBtn = document.getElementById('closeModal');
    const cancelBtn = document.getElementById('cancelBtn');
    const pointForm = document.getElementById('pointForm');
    const modalTitle = document.getElementById('modalTitle');
    const pointsList = document.getElementById('pointsList');
    const latitudInput = document.getElementById('latitud');
    const longitudInput = document.getElementById('longitud');
    const filterReferenciador = document.getElementById('filterReferenciador');
    
    // Abrir modal desde botón "Nuevo"
    openAddPointModalBtn.addEventListener('click', function() {
        abrirModal('nuevo');
    });
    
    // Cerrar modal
    closeModalBtn.addEventListener('click', cerrarModal);
    cancelBtn.addEventListener('click', cerrarModal);
    
    // Cerrar modal al hacer clic fuera
    pointModal.addEventListener('click', function(e) {
        if (e.target === pointModal) {
            cerrarModal();
        }
    });
    
    // Eventos para actualizar coordenadas cuando se escriben manualmente
    latitudInput.addEventListener('input', actualizarCoordenadasDesdeInputs);
    longitudInput.addEventListener('input', actualizarCoordenadasDesdeInputs);
    
    // Agregar evento al combo box de referenciadores
    if (filterReferenciador) {
        filterReferenciador.addEventListener('change', function() {
            const referenciadorId = this.value;
            console.log('Referenciador seleccionado:', referenciadorId);
            
            // Cargar referenciados en el mapa
            if (referenciadorId) {
                cargarReferenciadosEnMapa(referenciadorId);
            } else {
                // Si no hay referenciador seleccionado, limpiar marcadores
                referenciadosLayerGroup.clearLayers();
                console.log('Limpiando marcadores de referenciados');
            }
        });
    }
    
    // Función para actualizar coordenadas desde los campos de entrada
    function actualizarCoordenadasDesdeInputs() {
        const lat = document.getElementById('latitud').value;
        const lng = document.getElementById('longitud').value;
        
        if (lat && lng) {
            document.getElementById('coordsDisplay').innerHTML = `
                <div style="color: #4fc3f7; font-weight: bold;">Coordenadas ingresadas</div>
                <div style="font-size: 0.9rem; margin-top: 2px;">
                    Lat: ${parseFloat(lat).toFixed(6)}° N<br>
                    Lng: ${parseFloat(lng).toFixed(6)}° W
                </div>
            `;
            
            // Mover el marcador si existe
            if (marcadorSeleccion) {
                marcadorSeleccion.setLatLng([lat, lng]);
                map.setView([lat, lng], 15);
            }
        }
    }
    
    // Función para abrir modal
    function abrirModal(modo, puntoId = null) {
        console.log('Abriendo modal, modo:', modo);
        modalAbierto = true;
        
        // Limpiar formulario
        pointForm.reset();
        document.getElementById('puntoId').value = '';
        latitudInput.value = '';
        longitudInput.value = '';
        document.getElementById('coordsDisplay').innerHTML = 'Haz clic en el mapa para seleccionar ubicación';
        
        // Establecer valores por defecto
        document.getElementById('tipo_punto').value = 'general';
        document.getElementById('color_marcador').value = '#4fc3f7';
        
        if (modo === 'editar' && puntoId) {
            // Buscar punto a editar
            const punto = puntosGuardados.find(p => p.id_punto == puntoId);
            if (punto) {
                editandoPuntoId = puntoId;
                
                // Llenar formulario
                document.getElementById('puntoId').value = punto.id_punto;
                document.getElementById('nombre').value = punto.nombre;
                document.getElementById('descripcion').value = punto.descripcion || '';
                document.getElementById('tipo_punto').value = punto.tipo_punto;
                document.getElementById('color_marcador').value = punto.color_marcador || '#4fc3f7';
                
                // Establecer coordenadas
                latitudInput.value = punto.latitud;
                longitudInput.value = punto.longitud;
                actualizarCoordenadasDesdeInputs();
                
                // Cambiar título del modal
                modalTitle.innerHTML = '<i class="fas fa-edit"></i><span>Editar Punto</span>';
                
                // Centrar mapa en el punto
                map.setView([punto.latitud, punto.longitud], 15);
                
                // Crear marcador de selección
                if (marcadorSeleccion) {
                    marcadorSeleccion.setLatLng([punto.latitud, punto.longitud]);
                } else {
                    marcadorSeleccion = L.marker([punto.latitud, punto.longitud], {
                        icon: crearIconoMarcador('#4fc3f7'),
                        draggable: true,
                        zIndexOffset: 1000
                    }).addTo(map);
                    
                    marcadorSeleccion.on('dragend', function() {
                        const pos = marcadorSeleccion.getLatLng();
                        establecerCoordenadasDesdeMapa(pos.lat, pos.lng);
                    });
                }
            }
        } else {
            editandoPuntoId = null;
            modalTitle.innerHTML = '<i class="fas fa-map-marker-alt"></i><span>Agregar Nuevo Punto</span>';
            
            // Si hay una posición guardada, usarla
            const savedPosition = sessionStorage.getItem('mapPosition');
            if (savedPosition) {
                try {
                    const pos = JSON.parse(savedPosition);
                    establecerCoordenadasDesdeMapa(pos[0], pos[1]);
                } catch(e) {
                    // Ignorar error
                }
            }
        }
        
        // Mostrar modal
        pointModal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    // Función para cerrar modal
    function cerrarModal() {
        console.log('Cerrando modal');
        pointModal.classList.remove('active');
        document.body.style.overflow = '';
        modalAbierto = false;
        editandoPuntoId = null;
        
        // Eliminar marcador de selección
        if (marcadorSeleccion) {
            map.removeLayer(marcadorSeleccion);
            marcadorSeleccion = null;
        }
    }
    
    // Manejar envío del formulario
    pointForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        console.log('Enviando formulario...');
        
        // Validar coordenadas
        const latitud = latitudInput.value;
        const longitud = longitudInput.value;
        
        if (!latitud || !longitud) {
            alert('Por favor, selecciona una ubicación en el mapa o escribe las coordenadas manualmente.');
            return;
        }
        
        // Validar nombre
        const nombre = document.getElementById('nombre').value.trim();
        if (!nombre) {
            alert('Por favor, ingresa un nombre para el punto.');
            return;
        }
        
        // Obtener datos del formulario
        const formData = new FormData(pointForm);
        const data = Object.fromEntries(formData.entries());
        
        // Convertir coordenadas a números
        data.latitud = parseFloat(data.latitud);
        data.longitud = parseFloat(data.longitud);
        
        // Agregar ID de usuario
        data.id_usuario = idUsuario;
        
        // Guardar referencia al botón y su texto original
        const saveBtn = document.getElementById('saveBtn');
        const originalBtnText = saveBtn.innerHTML;
        
        try {
            // Mostrar carga
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            saveBtn.disabled = true;
            
            console.log('Enviando datos:', data);
            
            // Enviar datos al servidor
            const response = await fetch('guardar_punto.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });
            
            console.log('Estado de respuesta:', response.status);
            
            // Verificar si la respuesta es JSON válido
            const responseText = await response.text();
            console.log('Respuesta cruda:', responseText);
            
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (jsonError) {
                console.error('Error parseando JSON:', jsonError);
                throw new Error('La respuesta del servidor no es válida. Por favor, intenta nuevamente.');
            }
            
            console.log('Resultado parseado:', result);
            
            if (result.success) {
                // Actualizar lista de puntos
                await actualizarListaPuntos();
                
                // Cerrar modal
                cerrarModal();
                
                // Mostrar mensaje de éxito
                alert(result.message || '✓ Punto guardado correctamente');
                
                // Centrar en el nuevo punto si se guardó
                if (result.puntoId) {
                    // Buscar el punto recién guardado
                    const nuevoPunto = puntosGuardados.find(p => p.id_punto == result.puntoId);
                    if (nuevoPunto) {
                        centrarEnPunto(nuevoPunto.latitud, nuevoPunto.longitud);
                    }
                }
                
            } else {
                throw new Error(result.message || 'Error al guardar el punto');
            }
            
        } catch (error) {
            console.error('Error completo:', error);
            alert('❌ Error al guardar el punto: ' + error.message);
        } finally {
            // Siempre restaurar el botón, incluso si hay errores
            saveBtn.innerHTML = originalBtnText;
            saveBtn.disabled = false;
        }
    });
    
    // Función para actualizar lista de puntos
    async function actualizarListaPuntos() {
        console.log('Actualizando lista de puntos...');
        try {
            const response = await fetch('obtener_puntos.php');
            const result = await response.json();
            
            if (result.success) {
                puntosGuardados = result.puntos;
                console.log('Puntos actualizados:', puntosGuardados.length);
                cargarPuntosEnMapa();
                actualizarInterfazPuntos();
            }
        } catch (error) {
            console.error('Error al actualizar puntos:', error);
        }
    }
    
    // Función para actualizar interfaz de puntos
    function actualizarInterfazPuntos() {
        console.log('Actualizando interfaz de puntos...');
        const pointsList = document.getElementById('pointsList');
        if (puntosGuardados.length === 0) {
            pointsList.innerHTML = `
                <div style="text-align: center; color: #a0aec0; padding: 40px 20px;">
                    <i class="fas fa-map-marker-alt" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.3;"></i>
                    <p>No hay puntos guardados aún</p>
                    <p style="font-size: 0.9rem; margin-top: 10px;">Haz clic en "Agregar Punto" en el mapa para empezar</p>
                </div>
            `;
        } else {
            let html = '';
            puntosGuardados.forEach(punto => {
                html += `
                    <div class="point-item" data-id="${punto.id_punto}" 
                         data-lat="${punto.latitud}" 
                         data-lng="${punto.longitud}">
                        <div class="point-item-header">
                            <div class="point-name">${punto.nombre}</div>
                            <div class="point-type">${tiposPuntos[punto.tipo_punto] || punto.tipo_punto}</div>
                        </div>
                        ${punto.descripcion ? `
                        <div class="point-description">${punto.descripcion}</div>
                        ` : ''}
                        <div class="point-coords">
                            ${parseFloat(punto.latitud).toFixed(6)}, ${parseFloat(punto.longitud).toFixed(6)}
                        </div>
                        <div class="point-actions">
                            <button class="action-btn edit-btn" onclick="editarPunto(${punto.id_punto})">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <button class="action-btn delete-btn" onclick="eliminarPunto(${punto.id_punto})">
                                <i class="fas fa-trash"></i> Eliminar
                            </button>
                        </div>
                    </div>
                `;
            });
            pointsList.innerHTML = html;
            
            // Actualizar contador
            document.querySelector('.counter-value').textContent = puntosGuardados.length;
            
            // Agregar eventos a los items
            document.querySelectorAll('.point-item').forEach(item => {
                item.addEventListener('click', function(e) {
                    if (!e.target.closest('.point-actions')) {
                        const lat = parseFloat(this.dataset.lat);
                        const lng = parseFloat(this.dataset.lng);
                        centrarEnPunto(lat, lng);
                    }
                });
            });
        }
    }
    
    // Inicializar interfaz de puntos
    actualizarInterfazPuntos();
    
    // Función para editar punto (global)
    window.editarPunto = function(puntoId) {
        console.log('Editando punto:', puntoId);
        abrirModal('editar', puntoId);
    };
    
    // Función para eliminar punto (global)
    window.eliminarPunto = async function(puntoId) {
        if (!confirm('¿Estás seguro de que deseas eliminar este punto?')) {
            return;
        }
        
        console.log('Eliminando punto:', puntoId);
        
        try {
            const response = await fetch('eliminar_punto.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id_punto: puntoId })
            });
            
            const result = await response.json();
            
            if (result.success) {
                await actualizarListaPuntos();
                alert(result.message || '✓ Punto eliminado correctamente');
            } else {
                throw new Error(result.message || 'Error al eliminar el punto');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('❌ Error al eliminar el punto: ' + error.message);
        }
    };
    
    // Agregar botón de pantalla completa
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
    
    map.addControl(new FullscreenControl());
    
    // Función para abrir pantalla completa
    function abrirPantallaCompleta() {
        // Obtener la posición y zoom actual del mapa
        const center = map.getCenter();
        const zoom = map.getZoom();
        
        // Guardar en sessionStorage para que el mapa fullscreen lo use
        sessionStorage.setItem('mapPosition', JSON.stringify([center.lat, center.lng]));
        sessionStorage.setItem('mapZoom', zoom.toString());
        
        // Guardar también la ubicación del usuario si está activa
        if (userLocationMarker) {
            const userLatLng = userLocationMarker.getLatLng();
            const userLocationData = {
                lat: userLatLng.lat,
                lng: userLatLng.lng,
                accuracy: userLocationMarker.accuracyCircle ? userLocationMarker.accuracyCircle.getRadius() : 50,
                timestamp: new Date().getTime()
            };
            sessionStorage.setItem('userLocation', JSON.stringify(userLocationData));
        }
        
        // Abrir este mismo archivo en modo fullscreen
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
        
        // Abrir este mismo archivo con parámetro fullscreen
        window.open('?fullscreen=1', 'MapaFullScreen', features);
    }
}

// ============ MODO FULLSCREEN ============

function initFullscreenMode() {
    console.log('Inicializando modo fullscreen');
    
    // Función para salir de pantalla completa
    function salirPantallaCompleta() {
        window.close();
    }
    
    // Agregar evento al botón de salir
    document.getElementById('exitFullscreenBtn').addEventListener('click', salirPantallaCompleta);
    
    // Agregar evento para tecla ESC
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' || event.key === 'Esc' || event.keyCode === 27) {
            salirPantallaCompleta();
        }
    });
    
    // Ocultar/mostrar indicador ESC al pasar el mouse
    const escHint = document.getElementById('escHint');
    let hideTimeout;
    
    function hideEscHint() {
        escHint.style.opacity = '0';
        escHint.style.transform = 'translateY(10px)';
    }
    
    function showEscHint() {
        escHint.style.opacity = '1';
        escHint.style.transform = 'translateY(0)';
        
        // Configurar para que se oculte después de 3 segundos
        clearTimeout(hideTimeout);
        hideTimeout = setTimeout(hideEscHint, 3000);
    }
    
    // Inicialmente mostrar el hint y luego ocultarlo después de 5 segundos
    setTimeout(hideEscHint, 5000);
    
    // Mostrar cuando el usuario mueve el mouse
    document.addEventListener('mousemove', function() {
        showEscHint();
    });
    
    // Mostrar cuando el usuario toca la pantalla (móviles)
    document.addEventListener('touchstart', function() {
        showEscHint();
    });
    
    // En modo fullscreen, ajustar el mapa al redimensionar la ventana
    window.addEventListener('resize', function() {
        map.invalidateSize();
    });
    
    // Forzar redimensionamiento inicial
    setTimeout(() => map.invalidateSize(), 100);
    
    // Verificar si hay ubicación guardada desde el modo normal
    const savedLocation = sessionStorage.getItem('userLocation');
    if (savedLocation) {
        try {
            const locationData = JSON.parse(savedLocation);
            // Verificar si la ubicación es reciente (menos de 5 minutos)
            const fiveMinutesAgo = Date.now() - (5 * 60 * 1000);
            if (locationData.timestamp > fiveMinutesAgo) {
                // Crear un objeto position simulado
                const mockPosition = {
                    coords: {
                        latitude: locationData.lat,
                        longitude: locationData.lng,
                        accuracy: locationData.accuracy || 50
                    }
                };
                
                // Mostrar la ubicación guardada después de un breve retraso
                setTimeout(() => {
                    showUserLocationFullscreen(mockPosition);
                }, 1000);
            }
        } catch(e) {
            console.log('No se pudo cargar ubicación guardada:', e);
        }
    }
    
    // Agregar control de filtro de referenciadores en modo fullscreen
    if (referenciadoresActivos.length > 0) {
        const ReferenciadorFilterControl = L.Control.extend({
            options: {
                position: 'topleft'
            },
            
            onAdd: function(map) {
                const container = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
                container.style.backgroundColor = 'rgba(42, 67, 101, 0.9)';
                container.style.border = '2px solid #4a5568';
                container.style.borderRadius = '6px';
                container.style.overflow = 'hidden';
                container.style.padding = '8px';
                container.style.marginTop = '10px';
                container.style.marginLeft = '10px';
                container.style.minWidth = '250px';
                container.style.backdropFilter = 'blur(5px)';
                container.style.zIndex = '1000';
                
                // Crear select
                const select = L.DomUtil.create('select', '', container);
                select.style.width = '100%';
                select.style.padding = '8px 12px';
                select.style.background = 'rgba(26, 26, 46, 0.9)';
                select.style.color = '#e2e8f0';
                select.style.border = '1px solid #4fc3f7';
                select.style.borderRadius = '4px';
                select.style.fontSize = '14px';
                select.style.fontWeight = '500';
                select.style.cursor = 'pointer';
                select.style.appearance = 'none';
                select.style.backgroundImage = "url(\"data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%234fc3f7' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e\")";
                select.style.backgroundRepeat = 'no-repeat';
                select.style.backgroundPosition = 'right 10px center';
                select.style.backgroundSize = '15px';
                
                // Agregar opciones
                select.innerHTML = `
                    <option value="">Todos los referenciadores</option>
                    ${referenciadoresActivos.map(ref => `
                        <option value="${ref.id_usuario}">
                            ${ref.nombres} ${ref.apellidos}
                        </option>
                    `).join('')}
                `;
                
                // Agregar evento
                L.DomEvent.on(select, 'change', function() {
                    const referenciadorId = this.value;
                    if (referenciadorId) {
                        cargarReferenciadosEnMapa(referenciadorId);
                    } else {
                        referenciadosLayerGroup.clearLayers();
                    }
                });
                
                return container;
            }
        });
        
        map.addControl(new ReferenciadorFilterControl());
    }
    
    // Agregar control de ubicación en modo fullscreen que use la misma lógica
    const FullscreenLocateControl = L.Control.extend({
        options: {
            position: 'bottomright'
        },
        
        onAdd: function(map) {
            const container = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
            container.style.backgroundColor = '#2d3748';
            container.style.border = '2px solid #4a5568';
            container.style.borderRadius = '6px';
            container.style.overflow = 'hidden';
            container.style.marginRight = '10px';
            container.style.marginBottom = '20px';
            
            const button = L.DomUtil.create('a', '', container);
            button.href = '#';
            button.title = 'Mi Ubicación';
            button.style.width = '36px';
            button.style.height = '36px';
            button.style.display = 'flex';
            button.style.alignItems = 'center';
            button.style.justifyContent = 'center';
            button.style.color = '#4a5568';
            button.style.textDecoration = 'none';
            button.innerHTML = '<i class="fas fa-location-crosshairs"></i>';
            
            L.DomEvent.on(button, 'click', L.DomEvent.stopPropagation)
                      .on(button, 'click', L.DomEvent.preventDefault)
                      .on(button, 'click', function() {
                          // Usar la misma lógica del modo normal
                          if (isTrackingUser) {
                              stopTrackingUserFullscreen();
                          } else {
                              startTrackingUserFullscreen();
                          }
                      });
            
            L.DomEvent.on(button, 'mouseover', function() {
                button.style.backgroundColor = '#4a5568';
                button.style.color = 'white';
            });
            
            L.DomEvent.on(button, 'mouseout', function() {
                button.style.backgroundColor = '#2d3748';
                button.style.color = '#e2e8f0';
            });
            
            // Guardar referencia al botón para actualizar su estado
            container.locateButton = button;
            
            return container;
        }
    });
    
    map.addControl(new FullscreenLocateControl());
    
    // Función específica para fullscreen
    function startTrackingUserFullscreen() {
        if (!navigator.geolocation) {
            alert('La geolocalización no es soportada por tu navegador.');
            return;
        }
        
        // Mostrar estado en el botón
        updateLocateButtonStateFullscreen('loading');
        
        // Opciones para geolocalización
        const options = {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        };
        
        // Obtener ubicación actual
        navigator.geolocation.getCurrentPosition(
            function(position) {
                showUserLocationFullscreen(position);
                updateLocateButtonStateFullscreen('active');
                isTrackingUser = true;
                
                // Iniciar seguimiento continuo
                watchId = navigator.geolocation.watchPosition(
                    updateUserLocationFullscreen,
                    handleLocationErrorFullscreen,
                    options
                );
            },
            handleLocationErrorFullscreen,
            options
        );
    }
    
    function stopTrackingUserFullscreen() {
        if (watchId !== null) {
            navigator.geolocation.clearWatch(watchId);
            watchId = null;
        }
        
        isTrackingUser = false;
        updateLocateButtonStateFullscreen('inactive');
        
        // Limpiar marcador si existe
        if (userLocationMarker) {
            map.removeLayer(userLocationMarker);
            if (userLocationMarker.accuracyCircle) {
                map.removeLayer(userLocationMarker.accuracyCircle);
            }
            userLocationMarker = null;
        }
    }
    
    function showUserLocationFullscreen(position) {
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;
        const accuracy = position.coords.accuracy;
        
        console.log('Ubicación del usuario (fullscreen):', lat, lng, 'Precisión:', accuracy + 'm');
        
        // Guardar ubicación en sessionStorage
        sessionStorage.setItem('userLocation', JSON.stringify({
            lat: lat,
            lng: lng,
            accuracy: accuracy,
            timestamp: new Date().getTime()
        }));
        
        // Crear o actualizar marcador
        if (!userLocationMarker) {
            // Crear icono personalizado para ubicación del usuario
            const userIcon = L.divIcon({
                html: `
                    <div style="
                        background-color: #2196F3;
                        border: 3px solid white;
                        border-radius: 50%;
                        width: 24px;
                        height: 24px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        box-shadow: 0 0 10px rgba(33, 150, 243, 0.7);
                        animation: pulse 2s infinite;
                    ">
                        <i class="fas fa-user" style="color: white; font-size: 12px;"></i>
                    </div>
                `,
                iconSize: [24, 24],
                iconAnchor: [12, 12],
                className: 'user-location-marker'
            });
            
            userLocationMarker = L.marker([lat, lng], {
                icon: userIcon,
                zIndexOffset: 2000
            }).addTo(map);
            
            // Crear círculo de precisión
            const accuracyCircle = L.circle([lat, lng], {
                color: '#2196F3',
                fillColor: '#2196F3',
                fillOpacity: 0.2,
                radius: accuracy
            }).addTo(map);
            
            // Vincular el círculo al marcador
            userLocationMarker.accuracyCircle = accuracyCircle;
        } else {
            userLocationMarker.setLatLng([lat, lng]);
            if (userLocationMarker.accuracyCircle) {
                userLocationMarker.accuracyCircle.setLatLng([lat, lng]);
                userLocationMarker.accuracyCircle.setRadius(accuracy);
            }
        }
        
        // Actualizar popup
        const popupContent = `
            <div style="padding: 10px; min-width: 200px;">
                <strong style="color: #2d3748; font-size: 14px;">
                    <i class="fas fa-user"></i> Tu Ubicación
                </strong>
                <div style="margin-top: 8px;">
                    <div style="font-size: 12px; margin-bottom: 4px;">
                        <strong>Coordenadas:</strong>
                    </div>
                    <div style="font-family: monospace; font-size: 11px; background: rgba(33, 150, 243, 0.1); padding: 5px; border-radius: 4px;">
                        Lat: ${lat.toFixed(6)}° N<br>
                        Lng: ${lng.toFixed(6)}° W
                    </div>
                    <div style="margin-top: 8px; font-size: 11px; color: #666;">
                        <i class="fas fa-crosshairs"></i> Precisión: ${Math.round(accuracy)} metros
                    </div>
                    <div style="margin-top: 8px; font-size: 11px; color: #666;">
                        <i class="fas fa-clock"></i> Actualizado: ${new Date().toLocaleTimeString()}
                    </div>
                </div>
                <div style="margin-top: 10px;">
                    <button onclick="centerOnUserLocationFullscreen()" 
                            style="background: #2196F3; color: white; border: none; padding: 6px 12px; 
                                   border-radius: 4px; cursor: pointer; font-size: 12px; width: 100%;
                                   font-weight: bold;">
                        <i class="fas fa-search-location"></i> Centrar aquí
                    </button>
                </div>
            </div>
        `;
        
        userLocationMarker.bindPopup(popupContent, {
            className: 'user-location-popup',
            closeButton: true,
            autoClose: false
        }).openPopup();
        
        // Centrar el mapa en la ubicación del usuario con zoom apropiado
        const zoomLevel = accuracy < 100 ? 16 : accuracy < 500 ? 15 : 14;
        map.setView([lat, lng], zoomLevel);
        
        // Mostrar notificación
        showLocationNotification('Ubicación encontrada', 'success');
    }
    
    function updateUserLocationFullscreen(position) {
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;
        const accuracy = position.coords.accuracy;
        
        // Actualizar sessionStorage
        sessionStorage.setItem('userLocation', JSON.stringify({
            lat: lat,
            lng: lng,
            accuracy: accuracy,
            timestamp: new Date().getTime()
        }));
        
        if (userLocationMarker) {
            userLocationMarker.setLatLng([lat, lng]);
            
            if (userLocationMarker.accuracyCircle) {
                userLocationMarker.accuracyCircle.setLatLng([lat, lng]);
                userLocationMarker.accuracyCircle.setRadius(accuracy);
            }
            
            // Actualizar popup
            const popupContent = `
                <div style="padding: 10px; min-width: 200px;">
                    <strong style="color: #2d3748; font-size: 14px;">
                        <i class="fas fa-user"></i> Tu Ubicación
                    </strong>
                    <div style="margin-top: 8px;">
                        <div style="font-size: 12px; margin-bottom: 4px;">
                            <strong>Coordenadas:</strong>
                        </div>
                        <div style="font-family: monospace; font-size: 11px; background: rgba(33, 150, 243, 0.1); padding: 5px; border-radius: 4px;">
                            Lat: ${lat.toFixed(6)}° N<br>
                            Lng: ${lng.toFixed(6)}° W
                        </div>
                        <div style="margin-top: 8px; font-size: 11px; color: #666;">
                            <i class="fas fa-crosshairs"></i> Precisión: ${Math.round(accuracy)} metros
                        </div>
                        <div style="margin-top: 8px; font-size: 11px; color: #666;">
                            <i class="fas fa-clock"></i> Actualizado: ${new Date().toLocaleTimeString()}
                        </div>
                    </div>
                    <div style="margin-top: 10px;">
                        <button onclick="centerOnUserLocationFullscreen()" 
                                style="background: #2196F3; color: white; border: none; padding: 6px 12px; 
                                       border-radius: 4px; cursor: pointer; font-size: 12px; width: 100%;
                                       font-weight: bold;">
                            <i class="fas fa-search-location"></i> Centrar aquí
                        </button>
                    </div>
                </div>
            `;
            
            userLocationMarker.getPopup().setContent(popupContent);
        }
    }
    
    function handleLocationErrorFullscreen(error) {
        console.error('Error de geolocalización (fullscreen):', error);
        
        stopTrackingUserFullscreen();
        updateLocateButtonStateFullscreen('error');
        
        let errorMessage = 'No se pudo obtener tu ubicación. ';
        
        switch(error.code) {
            case error.PERMISSION_DENIED:
                errorMessage += 'Permiso denegado. Por favor, permite el acceso a la ubicación en la configuración del navegador.';
                showLocationNotification('Permiso de ubicación denegado', 'error');
                break;
            case error.POSITION_UNAVAILABLE:
                errorMessage += 'La información de ubicación no está disponible.';
                showLocationNotification('Ubicación no disponible', 'error');
                break;
            case error.TIMEOUT:
                errorMessage += 'La solicitud de ubicación ha expirado.';
                showLocationNotification('Tiempo de espera agotado', 'error');
                break;
            case error.UNKNOWN_ERROR:
                errorMessage += 'Ocurrió un error desconocido.';
                showLocationNotification('Error desconocido', 'error');
                break;
        }
        
        alert(errorMessage);
    }
    
    function updateLocateButtonStateFullscreen(state) {
        const controls = document.querySelectorAll('.leaflet-control');
        const locateControl = Array.from(controls).find(c => {
            const button = c.querySelector('a');
            return button && (button.title === 'Mi Ubicación' || button.title.includes('Ubicación'));
        });
        
        if (!locateControl) return;
        
        const button = locateControl.querySelector('a');
        
        switch(state) {
            case 'active':
                button.innerHTML = '<i class="fas fa-location-crosshairs"></i>';
                button.style.color = '#4CAF50';
                button.title = 'Dejar de seguir ubicación';
                break;
            case 'inactive':
                button.innerHTML = '<i class="fas fa-location-crosshairs"></i>';
                button.style.color = '#4a5568';
                button.title = 'Mi Ubicación';
                break;
            case 'loading':
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                button.style.color = '#FF9800';
                button.title = 'Buscando ubicación...';
                break;
            case 'error':
                button.innerHTML = '<i class="fas fa-location-slash"></i>';
                button.style.color = '#f44336';
                button.title = 'Error de ubicación';
                break;
        }
    }
    
    // Función global para centrar en ubicación del usuario en fullscreen
    window.centerOnUserLocationFullscreen = function() {
        if (userLocationMarker) {
            const latLng = userLocationMarker.getLatLng();
            map.setView(latLng, 16);
            userLocationMarker.openPopup();
        }
    };
}

// ============ GEOLOCALIZACIÓN ============

function initGeolocation() {
    const locateBtn = document.getElementById('locateUserBtn');
    
    if (!locateBtn) {
        console.log('Botón de geolocalización no encontrado (modo normal)');
        return;
    }
    
    locateBtn.addEventListener('click', toggleUserTracking);
    
    // Verificar si el navegador soporta geolocalización
    if (!navigator.geolocation) {
        locateBtn.innerHTML = '<i class="fas fa-location-slash"></i> No soportado';
        locateBtn.disabled = true;
        alert('La geolocalización no es soportada por tu navegador.');
        return;
    }
    
    // Solicitar permisos al cargar la página
    requestLocationPermission();
}

function requestLocationPermission() {
    if (navigator.permissions && navigator.permissions.query) {
        navigator.permissions.query({ name: 'geolocation' })
            .then(function(permissionStatus) {
                console.log('Estado del permiso de ubicación:', permissionStatus.state);
                
                permissionStatus.onchange = function() {
                    console.log('Permiso de ubicación cambiado:', this.state);
                    updateLocateButtonState(this.state);
                };
                
                updateLocateButtonState(permissionStatus.state);
            })
            .catch(function(error) {
                console.error('Error al consultar permisos:', error);
            });
    } else {
        // Navegadores antiguos - solicitar permiso al intentar usar
        console.log('API de permisos no disponible');
    }
}

function updateLocateButtonState(permissionState) {
    const locateBtn = document.getElementById('locateUserBtn');
    if (!locateBtn) return;
    
    switch(permissionState) {
        case 'granted':
            locateBtn.innerHTML = '<i class="fas fa-location-crosshairs"></i> Mi Ubicación';
            locateBtn.disabled = false;
            locateBtn.title = 'Mostrar mi ubicación en el mapa';
            break;
        case 'prompt':
            locateBtn.innerHTML = '<i class="fas fa-location-dot"></i> Permitir Ubicación';
            locateBtn.disabled = false;
            locateBtn.title = 'Haz clic para permitir acceso a tu ubicación';
            break;
        case 'denied':
            locateBtn.innerHTML = '<i class="fas fa-location-slash"></i> Ubicación Bloqueada';
            locateBtn.disabled = true;
            locateBtn.title = 'El acceso a la ubicación está bloqueado. Permítelo en la configuración del navegador.';
            break;
    }
}

function toggleUserTracking() {
    if (isTrackingUser) {
        stopTrackingUser();
    } else {
        startTrackingUser();
    }
}

function startTrackingUser() {
    const locateBtn = document.getElementById('locateUserBtn');
    if (!navigator.geolocation) return;
    
    locateBtn.classList.add('active');
    locateBtn.innerHTML = '<i class="fas fa-location-crosshairs"></i> Siguiendo...';
    locateBtn.title = 'Dejar de seguir ubicación';
    isTrackingUser = true;
    
    // Opciones para geolocalización
    const options = {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0
    };
    
    // Obtener ubicación actual
    navigator.geolocation.getCurrentPosition(
        function(position) {
            showUserLocation(position);
            
            // Guardar en sessionStorage
            sessionStorage.setItem('userLocation', JSON.stringify({
                lat: position.coords.latitude,
                lng: position.coords.longitude,
                accuracy: position.coords.accuracy,
                timestamp: new Date().getTime()
            }));
            
            // Iniciar seguimiento continuo
            watchId = navigator.geolocation.watchPosition(
                updateUserLocation,
                handleLocationError,
                options
            );
        },
        handleLocationError,
        options
    );
}

function stopTrackingUser() {
    const locateBtn = document.getElementById('locateUserBtn');
    if (watchId !== null) {
        navigator.geolocation.clearWatch(watchId);
        watchId = null;
    }
    
    locateBtn.classList.remove('active');
    locateBtn.innerHTML = '<i class="fas fa-location-crosshairs"></i> Mi Ubicación';
    locateBtn.title = 'Mostrar mi ubicación en el mapa';
    isTrackingUser = false;
}

function showUserLocation(position) {
    const lat = position.coords.latitude;
    const lng = position.coords.longitude;
    const accuracy = position.coords.accuracy;
    
    console.log('Ubicación del usuario:', lat, lng, 'Precisión:', accuracy + 'm');
    
    // Crear o actualizar marcador
    if (!userLocationMarker) {
        // Crear icono personalizado para ubicación del usuario
        const userIcon = L.divIcon({
            html: `
                <div style="
                    background-color: #2196F3;
                    border: 3px solid white;
                    border-radius: 50%;
                    width: 24px;
                    height: 24px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    box-shadow: 0 0 10px rgba(33, 150, 243, 0.7);
                    animation: pulse 2s infinite;
                ">
                    <i class="fas fa-user" style="color: white; font-size: 12px;"></i>
                </div>
            `,
            iconSize: [24, 24],
            iconAnchor: [12, 12],
            className: 'user-location-marker'
        });
        
        userLocationMarker = L.marker([lat, lng], {
            icon: userIcon,
            zIndexOffset: 2000
        }).addTo(map);
        
        // Crear círculo de precisión
        const accuracyCircle = L.circle([lat, lng], {
            color: '#2196F3',
            fillColor: '#2196F3',
            fillOpacity: 0.2,
            radius: accuracy
        }).addTo(map);
        
        // Vincular el círculo al marcador
        userLocationMarker.accuracyCircle = accuracyCircle;
    } else {
        userLocationMarker.setLatLng([lat, lng]);
        if (userLocationMarker.accuracyCircle) {
            userLocationMarker.accuracyCircle.setLatLng([lat, lng]);
            userLocationMarker.accuracyCircle.setRadius(accuracy);
        }
    }
    
    // Actualizar popup
    const popupContent = `
        <div style="padding: 10px; min-width: 200px;">
            <strong style="color: white; font-size: 14px;">
                <i class="fas fa-user"></i> Tu Ubicación
            </strong>
            <div style="margin-top: 8px;">
                <div style="font-size: 12px; margin-bottom: 4px;">
                    <strong>Coordenadas:</strong>
                </div>
                <div style="font-family: monospace; font-size: 11px; background: rgba(255,255,255,0.1); padding: 5px; border-radius: 4px;">
                    Lat: ${lat.toFixed(6)}° N<br>
                    Lng: ${lng.toFixed(6)}° W
                </div>
                <div style="margin-top: 8px; font-size: 11px;">
                    <i class="fas fa-crosshairs"></i> Precisión: ${Math.round(accuracy)} metros
                </div>
                <div style="margin-top: 8px; font-size: 11px;">
                    <i class="fas fa-clock"></i> Actualizado: ${new Date().toLocaleTimeString()}
                </div>
            </div>
            <div style="margin-top: 10px;">
                <button onclick="centerOnUserLocation()" 
                        style="background: white; color: #2196F3; border: none; padding: 6px 12px; 
                               border-radius: 4px; cursor: pointer; font-size: 12px; width: 100%;
                               font-weight: bold;">
                    <i class="fas fa-search-location"></i> Centrar aquí
                </button>
            </div>
        </div>
    `;
    
    userLocationMarker.bindPopup(popupContent, {
        className: 'user-location-popup',
        closeButton: false,
        autoClose: false,
        closeOnClick: false
    }).openPopup();
    
    // Centrar el mapa en la ubicación del usuario con zoom apropiado
    const zoomLevel = accuracy < 100 ? 16 : accuracy < 500 ? 15 : 14;
    map.setView([lat, lng], zoomLevel);
    
    // Mostrar notificación
    showLocationNotification('Ubicación encontrada', 'success');
}

function updateUserLocation(position) {
    const lat = position.coords.latitude;
    const lng = position.coords.longitude;
    const accuracy = position.coords.accuracy;
    
    // Actualizar sessionStorage
    sessionStorage.setItem('userLocation', JSON.stringify({
        lat: lat,
        lng: lng,
        accuracy: accuracy,
        timestamp: new Date().getTime()
    }));
    
    if (userLocationMarker) {
        userLocationMarker.setLatLng([lat, lng]);
        
        if (userLocationMarker.accuracyCircle) {
            userLocationMarker.accuracyCircle.setLatLng([lat, lng]);
            userLocationMarker.accuracyCircle.setRadius(accuracy);
        }
        
        // Actualizar popup
        const popupContent = `
            <div style="padding: 10px; min-width: 200px;">
                <strong style="color: white; font-size: 14px;">
                    <i class="fas fa-user"></i> Tu Ubicación
                </strong>
                <div style="margin-top: 8px;">
                    <div style="font-size: 12px; margin-bottom: 4px;">
                        <strong>Coordenadas:</strong>
                    </div>
                    <div style="font-family: monospace; font-size: 11px; background: rgba(255,255,255,0.1); padding: 5px; border-radius: 4px;">
                        Lat: ${lat.toFixed(6)}° N<br>
                        Lng: ${lng.toFixed(6)}° W
                    </div>
                    <div style="margin-top: 8px; font-size: 11px;">
                        <i class="fas fa-crosshairs"></i> Precisión: ${Math.round(accuracy)} metros
                    </div>
                    <div style="margin-top: 8px; font-size: 11px;">
                        <i class="fas fa-clock"></i> Actualizado: ${new Date().toLocaleTimeString()}
                    </div>
                </div>
                <div style="margin-top: 10px;">
                    <button onclick="centerOnUserLocation()" 
                            style="background: white; color: #2196F3; border: none; padding: 6px 12px; 
                                   border-radius: 4px; cursor: pointer; font-size: 12px; width: 100%;
                                   font-weight: bold;">
                        <i class="fas fa-search-location"></i> Centrar aquí
                    </button>
                </div>
            </div>
        `;
        
        userLocationMarker.getPopup().setContent(popupContent);
    }
}

function handleLocationError(error) {
    console.error('Error de geolocalización:', error);
    
    stopTrackingUser();
    
    let errorMessage = 'No se pudo obtener tu ubicación. ';
    
    switch(error.code) {
        case error.PERMISSION_DENIED:
            errorMessage += 'Permiso denegado. Por favor, permite el acceso a la ubicación en la configuración del navegador.';
            showLocationNotification('Permiso de ubicación denegado', 'error');
            break;
        case error.POSITION_UNAVAILABLE:
            errorMessage += 'La información de ubicación no está disponible.';
            showLocationNotification('Ubicación no disponible', 'error');
            break;
        case error.TIMEOUT:
            errorMessage += 'La solicitud de ubicación ha expirado.';
            showLocationNotification('Tiempo de espera agotado', 'error');
            break;
        case error.UNKNOWN_ERROR:
            errorMessage += 'Ocurrió un error desconocido.';
            showLocationNotification('Error desconocido', 'error');
            break;
    }
    
    alert(errorMessage);
}

window.centerOnUserLocation = function() {
    if (userLocationMarker) {
        const latLng = userLocationMarker.getLatLng();
        map.setView(latLng, 16);
        userLocationMarker.openPopup();
    }
};

function showLocationNotification(message, type) {
    // Crear elemento de notificación
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        z-index: 10000;
        display: flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        animation: slideIn 0.3s ease;
    `;
    
    if (type === 'success') {
        notification.style.background = 'linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%)';
        notification.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
    } else if (type === 'warning') {
        notification.style.background = 'linear-gradient(135deg, #FF9800 0%, #F57C00 100%)';
        notification.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${message}`;
    } else if (type === 'info') {
        notification.style.background = 'linear-gradient(135deg, #2196F3 0%, #1976D2 100%)';
        notification.innerHTML = `<i class="fas fa-info-circle"></i> ${message}`;
    } else {
        notification.style.background = 'linear-gradient(135deg, #f44336 0%, #d32f2f 100%)';
        notification.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
    }
    
    document.body.appendChild(notification);
    
    // Remover después de 5 segundos
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 5000);
}

// Agregar estilos de animación para notificaciones
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
document.addEventListener('DOMContentLoaded', function() {
    // Coordenadas de Puerto Gaitán, Meta
    const PUERTO_GAITAN = [4.314, -72.082];
    const ZOOM_INICIAL = 12;
    
    // Determinar si estamos en modo fullscreen
    const isFullscreen = <?php echo $isFullscreen ? 'true' : 'false'; ?>;
    
    // Variables globales para el mapa
    let map;
    let capaCalles, capaTopografico;
    let currentLatLng = PUERTO_GAITAN;
    let currentZoom = ZOOM_INICIAL;
    
    // Variables para gestión de puntos
    let puntosLayerGroup = L.layerGroup();
    let puntosGuardados = <?php echo json_encode($puntosUsuario); ?>;
    let editandoPuntoId = null;
    let marcadorSeleccion = null;
    let modalAbierto = false;
    
    // Tipos de puntos desde PHP
    const tiposPuntos = <?php echo json_encode($tiposPuntos); ?>;
    const coloresMarcadores = <?php echo json_encode($coloresMarcadores); ?>;
    
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
    
    // Control de capas
    const capasBase = {
        "Mapa de Calles": capaCalles,
        "Mapa Topográfico": capaTopografico
    };
    
    const capasOverlay = {
        "Mis Puntos": puntosLayerGroup
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
    
    // ============ FUNCIONALIDAD DE PUNTOS ============
    
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
    
    // Función para establecer coordenadas desde los campos de entrada
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
    
    // Cargar puntos iniciales
    cargarPuntosEnMapa();
    
    // Función para centrar mapa en un punto
    window.centrarEnPunto = function(lat, lng) {
        map.setView([lat, lng], 15);
    };
    
    <?php if(!$isFullscreen): ?>
    // ============ MODAL Y FORMULARIO ============
    
    const pointModal = document.getElementById('pointModal');
    const openAddPointModalBtn = document.getElementById('openAddPointModal');
    const addPointModeBtn = document.getElementById('addPointModeBtn');
    const closeModalBtn = document.getElementById('closeModal');
    const cancelBtn = document.getElementById('cancelBtn');
    const pointForm = document.getElementById('pointForm');
    const modalTitle = document.getElementById('modalTitle');
    const pointsList = document.getElementById('pointsList');
    const latitudInput = document.getElementById('latitud');
    const longitudInput = document.getElementById('longitud');
    
    // Abrir modal desde botón "Nuevo"
    openAddPointModalBtn.addEventListener('click', function() {
        abrirModal('nuevo');
    });
    
    // Abrir modal desde botón "Agregar Punto"
    addPointModeBtn.addEventListener('click', function() {
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
    data.id_usuario = <?php echo $_SESSION['id_usuario']; ?>;
    
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
// Agregar estas variables globales al inicio de la sección JavaScript
let userLocationMarker = null;
let isTrackingUser = false;
let watchId = null;
let locateBtn = null;

// Después de inicializar el mapa, agregar esta función:
function initGeolocation() {
    locateBtn = document.getElementById('locateUserBtn');
    
    if (!locateBtn) {
        console.log('Botón de geolocalización no encontrado');
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

// Función para solicitar permiso de ubicación
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

// Función para actualizar el estado del botón según los permisos
function updateLocateButtonState(permissionState) {
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

// Función para alternar el seguimiento de ubicación
function toggleUserTracking() {
    if (isTrackingUser) {
        stopTrackingUser();
    } else {
        startTrackingUser();
    }
}

// Función para iniciar el seguimiento de ubicación
function startTrackingUser() {
    if (!navigator.geolocation) return;
    
    locateBtn.classList.add('active');
    locateBtn.innerHTML = '<i class="fas fa-location-crosshairs"></i> Siguiendo...';
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

// Función para detener el seguimiento de ubicación
function stopTrackingUser() {
    if (watchId !== null) {
        navigator.geolocation.clearWatch(watchId);
        watchId = null;
    }
    
    locateBtn.classList.remove('active');
    locateBtn.innerHTML = '<i class="fas fa-location-crosshairs"></i> Mi Ubicación';
    isTrackingUser = false;
    
    // No eliminar el marcador, solo dejar de actualizarlo
    // El usuario puede seguir viendo su última ubicación conocida
}

// Función para mostrar la ubicación del usuario
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

// Función para actualizar la ubicación del usuario
function updateUserLocation(position) {
    const lat = position.coords.latitude;
    const lng = position.coords.longitude;
    const accuracy = position.coords.accuracy;
    
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

// Función para manejar errores de geolocalización
function handleLocationError(error) {
    console.error('Error de geolocalización:', error);
    
    stopTrackingUser();
    
    let errorMessage = 'No se pudo obtener tu ubicación. ';
    
    switch(error.code) {
        case error.PERMISSION_DENIED:
            errorMessage += 'Permiso denegado. Por favor, permite el acceso a la ubicación en la configuración de tu navegador.';
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

// Función para centrar en la ubicación del usuario
window.centerOnUserLocation = function() {
    if (userLocationMarker) {
        const latLng = userLocationMarker.getLatLng();
        map.setView(latLng, 16);
        userLocationMarker.openPopup();
    }
};

// Función para mostrar notificaciones de ubicación
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

// Inicializar geolocalización después de cargar el mapa
setTimeout(() => {
    initGeolocation();
}, 1000);

// También agregar en modo fullscreen
<?php if($isFullscreen): ?>
// En modo fullscreen, crear botón de ubicación
const locateControl = L.Control.extend({
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
        container.style.marginBottom = '80px';
        
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
                      if (!navigator.geolocation) {
                          alert('La geolocalización no es soportada por tu navegador.');
                          return;
                      }
                      
                      navigator.geolocation.getCurrentPosition(function(position) {
                          const lat = position.coords.latitude;
                          const lng = position.coords.longitude;
                          
                          // Crear marcador de ubicación del usuario
                          const userIcon = L.divIcon({
                              html: `<div style="
                                  background-color: #2196F3;
                                  border: 3px solid white;
                                  border-radius: 50%;
                                  width: 24px;
                                  height: 24px;
                                  display: flex;
                                  align-items: center;
                                  justify-content: center;
                                  box-shadow: 0 0 10px rgba(33, 150, 243, 0.7);
                              "><i class="fas fa-user" style="color: white; font-size: 12px;"></i></div>`,
                              iconSize: [24, 24],
                              iconAnchor: [12, 12]
                          });
                          
                          const marker = L.marker([lat, lng], { icon: userIcon }).addTo(map);
                          marker.bindPopup(`<b>Tu ubicación</b><br>Lat: ${lat.toFixed(6)}<br>Lng: ${lng.toFixed(6)}`).openPopup();
                          
                          map.setView([lat, lng], 16);
                      }, function(error) {
                          alert('Error al obtener ubicación: ' + error.message);
                      });
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

// Agregar control de ubicación en modo fullscreen
map.addControl(new locateControl());
<?php endif; ?>
    // Función para abrir pantalla completa
    function abrirPantallaCompleta() {
        // Obtener la posición y zoom actual del mapa
        const center = map.getCenter();
        const zoom = map.getZoom();
        
        // Guardar en sessionStorage para que el mapa fullscreen lo use
        sessionStorage.setItem('mapPosition', JSON.stringify([center.lat, center.lng]));
        sessionStorage.setItem('mapZoom', zoom.toString());
        
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
    // ============ FIN BOTÓN PANTALLA COMPLETA ============
    <?php else: ?>
    // ============ FUNCIONALIDAD PARA MODO FULLSCREEN ============
    
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
    
    // Log para depuración
    console.log('Evento ESC configurado para salir de pantalla completa');
    <?php endif; ?>
    
    // Log para depuración
    console.log('Mapa de Puerto Gaitán cargado correctamente');
    console.log('Modo fullscreen:', isFullscreen);
    console.log('Puntos cargados:', puntosGuardados.length);
    console.log('Posición inicial:', currentLatLng);
    console.log('Zoom inicial:', currentZoom);
    console.log('Modal abierto inicialmente:', modalAbierto);
    
    <?php if(!$isFullscreen): ?>
    console.log('Sistema de puntos habilitado');
    console.log('Botón de pantalla completa añadido');
    <?php endif; ?>
});
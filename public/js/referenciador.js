// Variables para el progreso
let totalProgress = 0;
const maxProgress = 100;
const progressElements = document.querySelectorAll('[data-progress]');
const progressFill = document.getElementById('progress-fill');
const progressPercentage = document.getElementById('progress-percentage');

// Sistema de rating
let stars = document.querySelectorAll('.star');
const ratingValue = document.getElementById('rating-value');
const afinidadInput = document.getElementById('afinidad');
let currentRating = 0;

// Contador de caracteres para compromiso
const compromisoTextarea = document.getElementById('compromiso');
const compromisoCounter = document.getElementById('compromiso-counter');
const compromisoChars = document.getElementById('compromiso-chars');

// ==================== FUNCIONES DEL SISTEMA DE RATING ====================

function updateCharCount() {
    const length = compromisoTextarea.value.length;
    compromisoChars.textContent = length;
    
    if (length >= 450) {
        compromisoCounter.classList.add('limit-exceeded');
    } else {
        compromisoCounter.classList.remove('limit-exceeded');
    }
}

// Función para actualizar el display de estrellas
function highlightStars(value, isHover = false) {
    stars.forEach((star, index) => {
        if (index < value) {
            star.innerHTML = '<i class="fas fa-star"></i>';
            star.classList.add(isHover ? 'hover' : 'selected');
            if (!isHover) {
                star.classList.remove('hover');
            }
        } else {
            star.innerHTML = '<i class="far fa-star"></i>';
            if (!isHover) {
                star.classList.remove('selected', 'hover');
            } else {
                star.classList.remove('selected');
            }
        }
    });
}

// Inicializar sistema de rating
function setupRatingSystem() {
    // Re-obtener las estrellas (por si se recargaron)
    stars = document.querySelectorAll('.star');
    
    // Remover event listeners anteriores si existen
    stars.forEach(star => {
        const newStar = star.cloneNode(true);
        star.parentNode.replaceChild(newStar, star);
    });
    
    // Re-obtener las estrellas después del clonado
    stars = document.querySelectorAll('.star');
    
    // Agregar eventos a cada estrella
    stars.forEach(star => {
        star.addEventListener('mouseover', function() {
            const value = parseInt(this.getAttribute('data-value'));
            highlightStars(value, true);
        });
        
        star.addEventListener('mouseout', function() {
            highlightStars(currentRating, false);
        });
        
        star.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const value = parseInt(this.getAttribute('data-value'));
            currentRating = value;
            afinidadInput.value = value;
            ratingValue.textContent = value + '/5';
            
            // Actualizar visualmente
            highlightStars(currentRating, false);
            
            // Actualizar progreso
            updateProgress();
            
            console.log('Rating seleccionado:', currentRating);
        });
    });
    
    // Inicializar display
    highlightStars(currentRating, false);
}

// ==================== SISTEMA DE PROGRESO ====================

// Actualizar progreso
function updateProgress() {
    let filledProgress = 0;
    
    progressElements.forEach(element => {
        if (element.type === 'hidden' || element.tagName === 'SELECT' || element.tagName === 'INPUT' || element.tagName === 'TEXTAREA') {
            const progressValue = parseInt(element.getAttribute('data-progress')) || 0;
            
            if (element.type === 'hidden' && element.value !== '0' && element.value !== '') {
                filledProgress += progressValue;
            } else if (element.tagName === 'SELECT' && element.value !== '') {
                filledProgress += progressValue;
            } else if (element.tagName === 'TEXTAREA' && element.value.trim() !== '') {
                filledProgress += progressValue;
            } else if (element.value && element.value.trim() !== '') {
                filledProgress += progressValue;
            }
        }
    });
    
    // AGREGAR ESTO PARA LOS INSUMOS (cada uno vale 2 puntos)
    const insumosSeleccionados = document.querySelectorAll('.insumo-checkbox:checked').length;
    filledProgress += (insumosSeleccionados * 2); // 2% por cada insumo
    
    totalProgress = Math.min(filledProgress, maxProgress);
    const percentage = Math.round((totalProgress / maxProgress) * 100);
    
    if (progressFill) {
        progressFill.style.width = percentage + '%';
    }
    if (progressPercentage) {
        progressPercentage.textContent = percentage + '%';
    }
}

// Escuchar cambios en los campos
function setupFormListeners() {
    document.querySelectorAll('input, select, textarea').forEach(element => {
        element.addEventListener('input', updateProgress);
        element.addEventListener('change', updateProgress);
    });
}

// ==================== FUNCIONES AUXILIARES ====================

// Abrir consulta de censo
function abrirConsultaCenso() {
    window.open('https://consultacenso.registraduria.gov.co/consultar/', '_blank');
}

// Mostrar notificaciones
function showNotification(message, type = 'info') {
    // Eliminar notificación anterior si existe
    const oldNotification = document.querySelector('.notification');
    if (oldNotification) {
        oldNotification.remove();
    }
    
    // Crear nueva notificación
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    
    let icon = 'info-circle';
    if (type === 'success') icon = 'check-circle';
    if (type === 'error') icon = 'exclamation-circle';
    if (type === 'warning') icon = 'exclamation-triangle';
    
    notification.innerHTML = `
        <i class="fas fa-${icon}"></i>
        <span>${message}</span>
        <button class="btn-close" onclick="this.parentElement.remove()">×</button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-eliminar después de 5 segundos
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// ==================== MANEJO DE INSUMOS ====================

let insumosCheckboxes = [];
let insumosSelectedDiv = null;

function updateInsumosDisplay() {
    if (!insumosSelectedDiv) return;
    
    const selectedInsumos = [];
    
    insumosCheckboxes.forEach(checkbox => {
        if (checkbox.checked) {
            const label = checkbox.nextElementSibling;
            const texto = label.querySelector('.insumo-text').textContent;
            selectedInsumos.push(texto);
        }
    });
    
    if (selectedInsumos.length > 0) {
        insumosSelectedDiv.textContent = 'Seleccionados: ' + selectedInsumos.join(', ');
        insumosSelectedDiv.classList.add('insumos-active');
    } else {
        insumosSelectedDiv.textContent = 'Ningún insumo seleccionado';
        insumosSelectedDiv.classList.remove('insumos-active');
    }
    
    updateProgress();
}

function setupInsumos() {
    insumosCheckboxes = document.querySelectorAll('.insumo-checkbox');
    insumosSelectedDiv = document.getElementById('insumos-selected');
    
    if (!insumosCheckboxes.length || !insumosSelectedDiv) {
        console.log('Elementos de insumos no encontrados');
        return;
    }
    
    // Agregar evento a cada checkbox
    insumosCheckboxes.forEach(checkbox => {
        // Remover eventos previos si existen
        const newCheckbox = checkbox.cloneNode(true);
        checkbox.parentNode.replaceChild(newCheckbox, checkbox);
        
        // Agregar nuevo evento
        newCheckbox.addEventListener('change', updateInsumosDisplay);
        
        // Hacer clicable toda la tarjeta
        const label = newCheckbox.nextElementSibling;
        if (label) {
            label.addEventListener('click', function(e) {
                if (e.target !== newCheckbox && !e.target.closest('button')) {
                    newCheckbox.checked = !newCheckbox.checked;
                    newCheckbox.dispatchEvent(new Event('change'));
                }
            });
        }
    });
    
    // Actualizar la referencia después del clonado
    insumosCheckboxes = document.querySelectorAll('.insumo-checkbox');
    
    // Inicializar display
    updateInsumosDisplay();
}

// ==================== FUNCIONES AJAX PARA SELECTS ====================

// Cargar sectores basados en zona seleccionada
document.getElementById('zona').addEventListener('change', function() {
    const zonaId = this.value;
    const sectorSelect = document.getElementById('sector');
    const puestoSelect = document.getElementById('puesto_votacion');
    
    if (zonaId) {
        sectorSelect.disabled = false;
        sectorSelect.innerHTML = '<option value="">Cargando sectores...</option>';
        
        // Llamada AJAX para obtener sectores
        fetch(`ajax/cargar_sectores.php?zona_id=${zonaId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    sectorSelect.innerHTML = '<option value="">Seleccione un sector</option>';
                    data.sectores.forEach(sector => {
                        const option = document.createElement('option');
                        option.value = sector.id_sector;
                        option.textContent = sector.nombre;
                        sectorSelect.appendChild(option);
                    });
                } else {
                    sectorSelect.innerHTML = '<option value="">Error al cargar sectores</option>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                sectorSelect.innerHTML = '<option value="">Error al cargar</option>';
            });
    } else {
        sectorSelect.disabled = true;
        sectorSelect.innerHTML = '<option value="">Primero seleccione una zona</option>';
        puestoSelect.disabled = true;
        puestoSelect.innerHTML = '<option value="">Primero seleccione un sector</option>';
    }
    
    updateProgress();
});

// Cargar puestos de votación basados en sector seleccionado
document.getElementById('sector').addEventListener('change', function() {
    const sectorId = this.value;
    const puestoSelect = document.getElementById('puesto_votacion');
    
    if (sectorId) {
        puestoSelect.disabled = false;
        puestoSelect.innerHTML = '<option value="">Cargando puestos...</option>';
        
        // Llamada AJAX para obtener puestos
        fetch(`ajax/cargar_puestos.php?sector_id=${sectorId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    puestoSelect.innerHTML = '<option value="">Seleccione un puesto</option>';
                    data.puestos.forEach(puesto => {
                        const option = document.createElement('option');
                        option.value = puesto.id_puesto;
                        option.textContent = puesto.nombre;
                        puestoSelect.appendChild(option);
                    });
                } else {
                    puestoSelect.innerHTML = '<option value="">Error al cargar puestos</option>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                puestoSelect.innerHTML = '<option value="">Error al cargar</option>';
            });
    } else {
        puestoSelect.disabled = true;
        puestoSelect.innerHTML = '<option value="">Primero seleccione un sector</option>';
    }
    
    updateProgress();
});

// Cargar municipios basados en departamento seleccionado
document.getElementById('departamento').addEventListener('change', function() {
    const departamentoId = this.value;
    const municipioSelect = document.getElementById('municipio');
    
    if (departamentoId) {
        municipioSelect.disabled = false;
        municipioSelect.innerHTML = '<option value="">Cargando municipios...</option>';
        
        // Llamada AJAX para obtener municipios
        fetch(`ajax/cargar_municipios.php?departamento_id=${departamentoId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    municipioSelect.innerHTML = '<option value="">Seleccione un municipio</option>';
                    data.municipios.forEach(municipio => {
                        const option = document.createElement('option');
                        option.value = municipio.id_municipio;
                        option.textContent = municipio.nombre;
                        municipioSelect.appendChild(option);
                    });
                } else {
                    municipioSelect.innerHTML = '<option value="">Error al cargar municipios</option>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                municipioSelect.innerHTML = '<option value="">Error al cargar</option>';
            });
    } else {
        municipioSelect.disabled = true;
        municipioSelect.innerHTML = '<option value="">Primero seleccione un departamento</option>';
    }
    
    updateProgress();
});

// ==================== MANEJO DEL ENVÍO DEL FORMULARIO ====================

document.getElementById('referenciacion-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('submit-btn');
    const originalText = submitBtn.innerHTML;
    
    // Validación básica
    const requiredFields = ['nombre', 'apellido', 'cedula', 'direccion', 'email', 'telefono'];
    let isValid = true;
    let errorMessage = '';
    
    requiredFields.forEach(field => {
        const element = document.getElementById(field);
        if (!element.value.trim()) {
            isValid = false;
            errorMessage = 'Por favor complete todos los campos obligatorios (*)';
            element.focus();
        }
    });
    
    if (!isValid) {
        showNotification(errorMessage, 'error');
        return;
    }
    
    // Validar cédula (solo números)
    const cedula = document.getElementById('cedula').value;
    if (!/^\d+$/.test(cedula)) {
        showNotification('La cédula solo debe contener números', 'error');
        return;
    }
    
    // Validar email
    const email = document.getElementById('email').value;
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        showNotification('Por favor ingrese un email válido', 'error');
        return;
    }
    
    // Validar afinidad (debe ser 1-5)
    if (currentRating === 0) {
        showNotification('Por favor seleccione el nivel de afinidad (1-5 estrellas)', 'error');
        return;
    }
    
    // Cambiar estado del botón
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
    submitBtn.disabled = true;
    
    try {
        // Preparar datos para enviar usando FormData
        const formData = new FormData(this);
        
        // Asegurar que la afinidad se envíe correctamente
        formData.set('afinidad', currentRating.toString());
        
        // Enviar datos al servidor
        const response = await fetch('ajax/guardar_referenciado.php', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success) {
            // Éxito
            showNotification(data.message || 'Registro guardado exitosamente', 'success');
            
            // Resetear formulario
            this.reset();
            
            // Resetear selects dependientes
            document.getElementById('sector').disabled = true;
            document.getElementById('sector').innerHTML = '<option value="">Primero seleccione una zona</option>';
            document.getElementById('puesto_votacion').disabled = true;
            document.getElementById('puesto_votacion').innerHTML = '<option value="">Primero seleccione un sector</option>';
            document.getElementById('municipio').disabled = true;
            document.getElementById('municipio').innerHTML = '<option value="">Primero seleccione un departamento</option>';
            
            // Resetear rating
            currentRating = 0;
            afinidadInput.value = '0';
            ratingValue.textContent = '0/5';
            setupRatingSystem(); // Reinicializar el sistema de rating
            
            // Resetear contador de caracteres
            updateCharCount();
            
            // Resetear insumos
            document.querySelectorAll('.insumo-checkbox').forEach(cb => {
                cb.checked = false;
            });
            updateInsumosDisplay();
            
            // Resetear progreso
            updateProgress();
            
        } else {
            showNotification(data.message || 'Error al guardar el registro', 'error');
        }
        
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error de conexión: ' + error.message, 'error');
    } finally {
        // Restaurar botón
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
});

// ==================== INICIALIZACIÓN ====================

// Inicializar al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado - Inicializando sistema...');
    
    // Inicializar contador de caracteres
    compromisoTextarea.addEventListener('input', updateCharCount);
    updateCharCount();
    
    // Configurar selects dependientes
    document.getElementById('sector').disabled = true;
    document.getElementById('puesto_votacion').disabled = true;
    document.getElementById('municipio').disabled = true;
    
    // Configurar sistema de rating
    setupRatingSystem();
    
    // Configurar insumos
    setupInsumos();
    
    // Configurar listeners del formulario
    setupFormListeners();
    
    // Validar número de mesa
    const mesaInput = document.getElementById('mesa');
    if (mesaInput) {
        mesaInput.addEventListener('change', function() {
            const value = parseInt(this.value);
            if (value < 1) this.value = 1;
            if (value > 30) this.value = 30;
        });
    }
    
    // Inicializar progreso
    updateProgress();
    
    // Debug: Verificar elementos importantes
    console.log('Estrellas encontradas:', stars.length);
    console.log('Elementos de progreso:', progressElements.length);
});

// Evento para clics en etiquetas de insumos
document.addEventListener('click', function(e) {
    if (e.target.closest('.insumo-label')) {
        const label = e.target.closest('.insumo-label');
        const checkbox = label.previousElementSibling;
        if (checkbox && checkbox.classList.contains('insumo-checkbox')) {
            checkbox.checked = !checkbox.checked;
            checkbox.dispatchEvent(new Event('change'));
        }
    }
});
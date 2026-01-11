// Variables para el progreso
let totalProgress = 0;
const maxProgress = 100;
const progressElements = document.querySelectorAll('[data-progress]');
const progressFill = document.getElementById('progress-fill');
const progressPercentage = document.getElementById('progress-percentage');

// Sistema de rating
const stars = document.querySelectorAll('.star');
const ratingValue = document.getElementById('rating-value');
const afinidadInput = document.getElementById('afinidad');
let currentRating = 0;

// Contador de caracteres para compromiso
const compromisoTextarea = document.getElementById('compromiso');
const compromisoCounter = document.getElementById('compromiso-counter');
const compromisoChars = document.getElementById('compromiso-chars');

// Inicializar contador de caracteres
function updateCharCount() {
    const length = compromisoTextarea.value.length;
    compromisoChars.textContent = length;
    
    if (length >= 450) {
        compromisoCounter.classList.add('limit-exceeded');
    } else {
        compromisoCounter.classList.remove('limit-exceeded');
    }
}

compromisoTextarea.addEventListener('input', updateCharCount);
compromisoTextarea.addEventListener('keyup', updateCharCount);

// Inicializar rating
stars.forEach(star => {
    star.addEventListener('mouseover', function() {
        const value = parseInt(this.getAttribute('data-value'));
        highlightStars(value);
    });
    
    star.addEventListener('mouseout', function() {
        highlightStars(currentRating);
    });
    
    star.addEventListener('click', function() {
        const value = parseInt(this.getAttribute('data-value'));
        currentRating = value;
        afinidadInput.value = value;
        ratingValue.textContent = value + '/5';
        updateProgress();
        
        // Marcar estrellas seleccionadas
        stars.forEach((s, index) => {
            if (index < value) {
                s.innerHTML = '<i class="fas fa-star"></i>';
                s.classList.add('selected');
            } else {
                s.innerHTML = '<i class="far fa-star"></i>';
                s.classList.remove('selected');
            }
        });
    });
});

function highlightStars(value) {
    stars.forEach((star, index) => {
        if (index < value) {
            star.innerHTML = '<i class="fas fa-star"></i>';
            star.classList.add('hover');
        } else {
            star.innerHTML = '<i class="far fa-star"></i>';
            star.classList.remove('hover');
        }
    });
}

// Actualizar progreso
// Actualizar progreso (modifica esta función en tu referenciador.js)
function updateProgress() {
    let filledProgress = 0;
    
    progressElements.forEach(element => {
        if (element.type === 'hidden' || element.tagName === 'SELECT' || element.tagName === 'INPUT' || element.tagName === 'TEXTAREA') {
            const progressValue = parseInt(element.getAttribute('data-progress'));
            
            if (element.type === 'hidden' && element.value !== '0') {
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
    
    progressFill.style.width = percentage + '%';
    progressPercentage.textContent = percentage + '%';
}

// Escuchar cambios en los campos
document.querySelectorAll('input, select, textarea').forEach(element => {
    element.addEventListener('input', updateProgress);
    element.addEventListener('change', updateProgress);
});

// Abrir consulta de censo
function abrirConsultaCenso() {
    window.open('https://consultacenso.registraduria.gov.co/consultar/', '_blank');
}

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

// Manejar envío del formulario
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
            stars.forEach(star => {
                star.innerHTML = '<i class="far fa-star"></i>';
                star.classList.remove('selected', 'hover');
            });
            
            // Resetear contador de caracteres
            compromisoChars.textContent = '0';
            compromisoCounter.classList.remove('limit-exceeded');
            
            // Resetear progreso
            totalProgress = 0;
            progressFill.style.width = '0%';
            progressPercentage.textContent = '0%';
            
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

// Inicializar al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar contador de caracteres
    updateCharCount();
    
    // Configurar selects dependientes
    document.getElementById('sector').disabled = true;
    document.getElementById('sector').innerHTML = '<option value="">Primero seleccione una zona</option>';
    document.getElementById('puesto_votacion').disabled = true;
    document.getElementById('puesto_votacion').innerHTML = '<option value="">Primero seleccione un sector</option>';
    document.getElementById('municipio').disabled = true;
    document.getElementById('municipio').innerHTML = '<option value="">Primero seleccione un departamento</option>';
    
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
});
// Variables globales
let currentRating = 0;
let maxMesasPuestoActual = 0;

// ==================== INICIALIZACIÓN ====================
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado - Inicializando sistema...');
    
    // Inicializar contador de caracteres
    setupCharCounter();
    
    // Inicializar sistema de rating (ESTRELLAS)
    setupStarsSystem();
    
    // Inicializar insumos
    setupInsumos();
    
    // Configurar selects dependientes
    setupDependentSelects();
    
    // Configurar eventos del formulario
    setupFormEvents();
    
    // Inicializar progreso
    updateProgress();
    
    console.log('Sistema inicializado correctamente');
});

// ==================== SISTEMA DE ESTRELLAS (RATING) ====================
function setupStarsSystem() {
    const stars = document.querySelectorAll('.star');
    const ratingValue = document.getElementById('rating-value');
    const afinidadInput = document.getElementById('afinidad');
    
    if (!stars.length || !ratingValue || !afinidadInput) {
        console.error('Elementos del sistema de rating no encontrados');
        return;
    }
    
    // Función para actualizar el display
    function updateStars(selectedValue, isHover = false) {
        stars.forEach((star, index) => {
            const starValue = parseInt(star.getAttribute('data-value'));
            const icon = star.querySelector('i');
            
            if (icon) {
                if (starValue <= selectedValue) {
                    // Estrella seleccionada o en hover
                    icon.className = 'fas fa-star';
                    if (isHover) {
                        star.classList.add('hover');
                        star.classList.remove('selected');
                    } else {
                        star.classList.add('selected');
                        star.classList.remove('hover');
                    }
                } else {
                    // Estrella vacía
                    icon.className = 'far fa-star';
                    star.classList.remove('selected', 'hover');
                }
            }
        });
    }
    
    // Agregar eventos a cada estrella
    stars.forEach(star => {
        // Evento mouseover (hover)
        star.addEventListener('mouseenter', function() {
            const value = parseInt(this.getAttribute('data-value'));
            updateStars(value, true);
        });
        
        // Evento mouseout (salir)
        star.addEventListener('mouseleave', function() {
            updateStars(currentRating, false);
        });
        
        // Evento click (seleccionar)
        star.addEventListener('click', function() {
            const value = parseInt(this.getAttribute('data-value'));
            currentRating = value;
            afinidadInput.value = value;
            ratingValue.textContent = value + '/5';
            
            // Actualizar visualmente
            updateStars(currentRating, false);
            
            // Actualizar progreso
            updateProgress();
            
            console.log('Rating seleccionado:', value);
        });
    });
    
    // Inicializar con valor 0
    updateStars(0, false);
}

// ==================== CONTADOR DE CARACTERES ====================
function setupCharCounter() {
    const compromisoTextarea = document.getElementById('compromiso');
    const compromisoCounter = document.getElementById('compromiso-counter');
    const compromisoChars = document.getElementById('compromiso-chars');
    
    if (!compromisoTextarea || !compromisoCounter || !compromisoChars) {
        console.log('Elementos del contador no encontrados');
        return;
    }
    
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
    updateCharCount(); // Inicializar
}

// ==================== SISTEMA DE PROGRESO ====================
function updateProgress() {
    const progressFill = document.getElementById('progress-fill');
    const progressPercentage = document.getElementById('progress-percentage');
    
    if (!progressFill || !progressPercentage) {
        console.error('Elementos de progreso no encontrados');
        return;
    }
    
    let filledProgress = 0;
    const maxProgress = 100;
    
    // Campos con data-progress
    document.querySelectorAll('[data-progress]').forEach(element => {
        const progressValue = parseInt(element.getAttribute('data-progress')) || 0;
        const value = element.value;
        
        if (element.tagName === 'INPUT') {
            if (element.type === 'hidden') {
                if (value && value !== '0' && value !== '') {
                    filledProgress += progressValue;
                }
            } else if (element.type === 'text' || element.type === 'email' || element.type === 'tel' || element.type === 'number') {
                if (value && value.trim() !== '') {
                    filledProgress += progressValue;
                }
            } else if (element.type === 'checkbox') {
                // Los checkboxes se manejan aparte
            }
        } else if (element.tagName === 'SELECT') {
            if (value && value !== '') {
                filledProgress += progressValue;
            }
        } else if (element.tagName === 'TEXTAREA') {
            if (value && value.trim() !== '') {
                filledProgress += progressValue;
            }
        }
    });
    
    // Insumos (2% por cada uno)
    const insumosSeleccionados = document.querySelectorAll('.insumo-checkbox:checked').length;
    filledProgress += (insumosSeleccionados * 2);
    
    // Asegurar que no supere el máximo
    filledProgress = Math.min(filledProgress, maxProgress);
    const percentage = Math.round((filledProgress / maxProgress) * 100);
    
    // Actualizar visualmente
    progressFill.style.width = percentage + '%';
    progressPercentage.textContent = percentage + '%';
    
    console.log('Progreso actualizado:', percentage + '%');
}

// ==================== SISTEMA DE MESAS DINÁMICAS ====================

// Configurar campo de mesa según puesto seleccionado
function configurarCampoMesa(puestoId) {
    const mesaInput = document.getElementById('mesa');
    const mesaInfo = document.getElementById('mesa-info');
    
    if (!puestoId) {
        mesaInput.disabled = true;
        mesaInput.placeholder = "Número de mesa";
        mesaInput.value = "";
        mesaInput.max = "30";
        maxMesasPuestoActual = 0;
        
        if (mesaInfo) {
            mesaInfo.innerHTML = 'Seleccione un puesto de votación para ver las mesas disponibles';
            mesaInfo.style.color = '#666';
        }
        return;
    }
    
    // Obtener información del puesto seleccionado
    const puestoSelect = document.getElementById('puesto_votacion');
    const selectedOption = puestoSelect.options[puestoSelect.selectedIndex];
    const numMesas = parseInt(selectedOption.getAttribute('data-mesas')) || 0;
    const puestoNombre = selectedOption.textContent.split(' (')[0]; // Remover texto entre paréntesis
    
    maxMesasPuestoActual = numMesas;
    
    // Si el puesto tiene 0 mesas, deshabilitar campo
    if (numMesas === 0) {
        mesaInput.disabled = true;
        mesaInput.value = "";
        mesaInput.placeholder = "Este puesto no tiene mesas";
        mesaInput.max = "0";
        
        if (mesaInfo) {
            mesaInfo.innerHTML = `<i class="fas fa-exclamation-triangle"></i> <strong>${puestoNombre}</strong> no tiene mesas disponibles para votación`;
            mesaInfo.style.color = '#e67e22';
            mesaInfo.style.fontWeight = '500';
        }
        return;
    }
    
    // Habilitar y configurar el campo de mesa
    mesaInput.disabled = false;
    mesaInput.placeholder = `Número de mesa (Máx. ${numMesas})`;
    mesaInput.max = numMesas;
    mesaInput.min = 1;
    mesaInput.value = "";
    
    // Agregar validación en tiempo real
    mesaInput.addEventListener('input', function() {
        validarNumeroMesa(this);
    });
    
    // Mostrar información sobre el total de mesas
    if (mesaInfo) {
        mesaInfo.innerHTML = `<i class="fas fa-info-circle"></i> <strong>${puestoNombre}</strong> tiene <strong>${numMesas}</strong> mesa${numMesas !== 1 ? 's' : ''} disponible${numMesas !== 1 ? 's' : ''}`;
        mesaInfo.style.color = '#27ae60';
        mesaInfo.style.fontWeight = '500';
    }
    
    updateProgress();
}

// Validar número de mesa ingresado
function validarNumeroMesa(input) {
    const value = parseInt(input.value);
    const mesaInfo = document.getElementById('mesa-info');
    
    if (isNaN(value) || value < 1) {
        input.value = "";
        if (mesaInfo) {
            mesaInfo.innerHTML += ' <span style="color:#e74c3c">(Ingrese un número válido)</span>';
        }
        return;
    }
    
    if (value > maxMesasPuestoActual) {
        input.value = maxMesasPuestoActual;
        if (mesaInfo) {
            mesaInfo.innerHTML += ' <span style="color:#e74c3c">(Máximo permitido: ' + maxMesasPuestoActual + ')</span>';
        }
    }
}

// ==================== INSUMOS ====================
function setupInsumos() {
    const insumosCheckboxes = document.querySelectorAll('.insumo-checkbox');
    const insumosSelectedDiv = document.getElementById('insumos-selected');
    
    if (!insumosCheckboxes.length || !insumosSelectedDiv) {
        console.log('Elementos de insumos no encontrados');
        return;
    }
    
    function updateInsumosDisplay() {
        const selectedInsumos = [];
        
        insumosCheckboxes.forEach(checkbox => {
            if (checkbox.checked) {
                const label = checkbox.nextElementSibling;
                if (label) {
                    const texto = label.querySelector('.insumo-text');
                    if (texto) {
                        selectedInsumos.push(texto.textContent);
                    }
                }
            }
        });
        
        if (selectedInsumos.length > 0) {
            insumosSelectedDiv.textContent = 'Seleccionados: ' + selectedInsumos.join(', ');
            insumosSelectedDiv.classList.add('insumos-active');
        } else {
            insumosSelectedDiv.textContent = 'Ningún insumo seleccionado';
            insumosSelectedDiv.classList.remove('insumos-active');
        }
        
        // Actualizar progreso
        updateProgress();
    }
    
    // Agregar evento a cada checkbox
    insumosCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateInsumosDisplay);
        
        // Hacer clicable toda la tarjeta
        const label = checkbox.nextElementSibling;
        if (label) {
            label.addEventListener('click', function(e) {
                // Evitar clics en el switch interno
                if (!e.target.closest('.insumo-switch')) {
                    checkbox.checked = !checkbox.checked;
                    checkbox.dispatchEvent(new Event('change'));
                }
            });
        }
    });
    
    // Inicializar display
    updateInsumosDisplay();
}

// ==================== SELECTS DEPENDIENTES ====================
function setupDependentSelects() {
    // Configurar selects como deshabilitados inicialmente
    document.getElementById('sector').disabled = true;
    document.getElementById('puesto_votacion').disabled = true;
    document.getElementById('municipio').disabled = true;
    document.getElementById('mesa').disabled = true;
    
    // Zona -> Sector
    document.getElementById('zona').addEventListener('change', function() {
        const zonaId = this.value;
        const sectorSelect = document.getElementById('sector');
        const puestoSelect = document.getElementById('puesto_votacion');
        const mesaInput = document.getElementById('mesa');
        
        if (zonaId) {
            sectorSelect.disabled = false;
            sectorSelect.innerHTML = '<option value="">Cargando sectores...</option>';
            
            fetch(`ajax/cargar_sectores.php?zona_id=${zonaId}`)
                .then(response => response.json())
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
            mesaInput.disabled = true;
            configurarCampoMesa(null);
        }
        
        updateProgress();
    });
    
    // Sector -> Puesto
    document.getElementById('sector').addEventListener('change', function() {
        const sectorId = this.value;
        const puestoSelect = document.getElementById('puesto_votacion');
        const mesaInput = document.getElementById('mesa');
        
        if (sectorId) {
            puestoSelect.disabled = false;
            puestoSelect.innerHTML = '<option value="">Cargando puestos...</option>';
            
            // Llamada AJAX para obtener puestos CON número de mesas
            fetch(`ajax/cargar_puestos.php?sector_id=${sectorId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        puestoSelect.innerHTML = '<option value="">Seleccione un puesto</option>';
                        data.puestos.forEach(puesto => {
                            const option = document.createElement('option');
                            option.value = puesto.id_puesto;
                            
                            // Mostrar información con número de mesas
                            let texto = puesto.nombre;
                            let mesaText = '';
                            
                            if (puesto.num_mesas === 0) {
                                mesaText = ' (Sin mesas)';
                                option.style.color = '#e67e22';
                            } else {
                                mesaText = ` (${puesto.num_mesas} mesa${puesto.num_mesas !== 1 ? 's' : ''})`;
                                option.style.color = '#27ae60';
                            }
                            
                            option.textContent = texto + mesaText;
                            option.setAttribute('data-mesas', puesto.num_mesas || 0);
                            
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
            mesaInput.disabled = true;
            configurarCampoMesa(null);
        }
        
        updateProgress();
    });
    
    // Puesto -> Configurar campo Mesa
    document.getElementById('puesto_votacion').addEventListener('change', function() {
        const puestoId = this.value;
        configurarCampoMesa(puestoId);
    });
    
    // Departamento -> Municipio
    document.getElementById('departamento').addEventListener('change', function() {
        const departamentoId = this.value;
        const municipioSelect = document.getElementById('municipio');
        
        if (departamentoId) {
            municipioSelect.disabled = false;
            municipioSelect.innerHTML = '<option value="">Cargando municipios...</option>';
            
            fetch(`ajax/cargar_municipios.php?departamento_id=${departamentoId}`)
                .then(response => response.json())
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
}

// ==================== EVENTOS DEL FORMULARIO ====================
function setupFormEvents() {
    // Escuchar cambios en todos los campos para actualizar progreso
    document.querySelectorAll('input, select, textarea').forEach(element => {
        element.addEventListener('input', updateProgress);
        element.addEventListener('change', updateProgress);
    });
    
    // Validar campo de mesa cuando se pierde el foco
    const mesaInput = document.getElementById('mesa');
    if (mesaInput) {
        mesaInput.addEventListener('blur', function() {
            validarNumeroMesa(this);
        });
    }
    
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
            if (!element || !element.value.trim()) {
                isValid = false;
                errorMessage = 'Por favor complete todos los campos obligatorios (*)';
                if (element) element.focus();
            }
        });
        
        // Validar mesa si está habilitada
        const mesaInput = document.getElementById('mesa');
        if (mesaInput && !mesaInput.disabled) {
            const mesaValue = parseInt(mesaInput.value);
            if (isNaN(mesaValue) || mesaValue < 1 || mesaValue > maxMesasPuestoActual) {
                isValid = false;
                errorMessage = `Por favor ingrese un número de mesa válido (1-${maxMesasPuestoActual})`;
                mesaInput.focus();
            }
        }
        
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
            const formData = new FormData(this);
            
            // Asegurar que la afinidad se envíe
            const afinidadInput = document.getElementById('afinidad');
            if (afinidadInput) {
                formData.set('afinidad', currentRating.toString());
            }
            
            // Enviar datos
            const response = await fetch('ajax/guardar_referenciado.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showNotification(data.message || 'Registro guardado exitosamente', 'success');
                
                // Resetear formulario
                this.reset();
                currentRating = 0;
                
                // Resetear elementos específicos
                resetForm();
                
            } else {
                showNotification(data.message || 'Error al guardar el registro', 'error');
            }
            
        } catch (error) {
            console.error('Error:', error);
            showNotification('Error de conexión: ' + error.message, 'error');
        } finally {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    });
}

// ==================== FUNCIONES AUXILIARES ====================

// Abrir consulta de censo
function abrirConsultaCenso() {
    window.open('https://consultacenso.registraduria.gov.co/consultar/', '_blank');
}

// Mostrar notificaciones
function showNotification(message, type = 'info') {
    const oldNotification = document.querySelector('.notification');
    if (oldNotification) oldNotification.remove();
    
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
    
    setTimeout(() => {
        if (notification.parentNode) notification.remove();
    }, 5000);
}

// Resetear formulario después de envío
function resetForm() {
    // Resetear rating
    const ratingValue = document.getElementById('rating-value');
    const afinidadInput = document.getElementById('afinidad');
    const stars = document.querySelectorAll('.star');
    
    if (ratingValue) ratingValue.textContent = '0/5';
    if (afinidadInput) afinidadInput.value = '0';
    
    stars.forEach(star => {
        const icon = star.querySelector('i');
        if (icon) {
            icon.className = 'far fa-star';
        }
        star.classList.remove('selected', 'hover');
    });
    
    // Resetear contador de caracteres
    setupCharCounter();
    
    // Resetear selects dependientes
    document.getElementById('sector').disabled = true;
    document.getElementById('sector').innerHTML = '<option value="">Primero seleccione una zona</option>';
    document.getElementById('puesto_votacion').disabled = true;
    document.getElementById('puesto_votacion').innerHTML = '<option value="">Primero seleccione un sector</option>';
    document.getElementById('municipio').disabled = true;
    document.getElementById('municipio').innerHTML = '<option value="">Primero seleccione un departamento</option>';
    
    // Resetear campo de mesas
    const mesaInput = document.getElementById('mesa');
    const mesaInfo = document.getElementById('mesa-info');
    if (mesaInput) {
        mesaInput.disabled = true;
        mesaInput.value = "";
        mesaInput.placeholder = "Número de mesa";
        mesaInput.max = "30";
    }
    
    if (mesaInfo) {
        mesaInfo.innerHTML = 'Seleccione un puesto de votación para ver las mesas disponibles';
        mesaInfo.style.color = '#666';
    }
    
    maxMesasPuestoActual = 0;
    
    // Resetear insumos
    document.querySelectorAll('.insumo-checkbox').forEach(cb => {
        cb.checked = false;
    });
    
    // Forzar actualización de insumos
    const insumosCheckboxes = document.querySelectorAll('.insumo-checkbox');
    if (insumosCheckboxes.length) {
        insumosCheckboxes[0].dispatchEvent(new Event('change'));
    }
    
    // Resetear progreso
    updateProgress();
}
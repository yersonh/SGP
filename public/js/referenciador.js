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
    
    // Configurar switch de Vota Fuera
    setupVotaFueraSwitch();
    
    // Configurar campos de votación dependientes
    setupVotacionCampos();
    
    // Configurar validación de cédula
    setupCedulaValidation();
    
    // Configurar eventos del formulario
    setupFormEvents();
    
    // Configurar asteriscos dinámicos (NUEVO)
    setupAsteriscosDinamicos();
    
    // Inicializar progreso del formulario
    updateProgress();
    
    // Inicializar barra de tope
    updateTopeProgress();
    
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
        // Verificar si el campo está visible
        const fieldContainer = element.closest('.form-group');
        if (fieldContainer && fieldContainer.style.display === 'none') {
            return; // Saltar campos ocultos
        }
        
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

// ==================== BARRA DE TOPE ====================
function updateTopeProgress() {
    const topeProgressFill = document.getElementById('tope-progress-fill');
    const topePercentage = document.getElementById('tope-percentage');
    
    if (!topeProgressFill || !topePercentage) {
        console.error('Elementos de tope no encontrados');
        return;
    }
    
    // Obtener valores del HTML
    const topeText = document.querySelector('.progress-header span:first-child').textContent;
    const match = topeText.match(/(\d+)\/(\d+)/);
    
    if (!match) return;
    
    const actual = parseInt(match[1]);
    const maximo = parseInt(match[2]);
    
    if (maximo === 0) return;
    
    const percentage = Math.min((actual / maximo) * 100, 100);
    
    // Actualizar visualmente
    topeProgressFill.style.width = percentage + '%';
    topePercentage.textContent = Math.round(percentage) + '%';
    
    console.log('Tope actualizado:', percentage + '%');
}

// ==================== FUNCIÓN PARA INCREMENTAR TOPE ====================
function incrementarTope() {
    // Obtener el texto actual del tope
    const topeSpan = document.querySelector('.progress-header span:first-child');
    if (!topeSpan) return;
    
    const texto = topeSpan.textContent;
    const match = texto.match(/(\d+)\/(\d+)/);
    
    if (!match) return;
    
    const actual = parseInt(match[1]);
    const maximo = parseInt(match[2]);
    
    // Incrementar en 1
    const nuevoActual = actual + 1;
    
    // Actualizar el texto
    topeSpan.textContent = `Progreso del Tope: ${nuevoActual}/${maximo}`;
    
    // Actualizar la barra de tope
    updateTopeProgress();
}

// ==================== SISTEMA DE MESAS DINÁMICAS ====================

// Configurar campo de mesa según puesto seleccionado
function configurarCampoMesa(puestoId) {
    const mesaInput = document.getElementById('mesa');
    const mesaInfo = document.getElementById('mesa-info');
    
    // Limpiar mensajes de error existentes
    if (mesaInfo) {
        const existingErrors = mesaInfo.querySelectorAll('.error-message');
        existingErrors.forEach(error => error.remove());
    }
    
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
    
    // Resto de la función se mantiene igual...
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
        // Crear el contenido HTML limpio
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
            // Solo mostrar el mensaje si no existe ya
            if (!mesaInfo.querySelector('.error-message')) {
                const errorSpan = document.createElement('span');
                errorSpan.className = 'error-message';
                errorSpan.style.color = '#e74c3c';
                errorSpan.textContent = ' (Ingrese un número válido)';
                mesaInfo.appendChild(errorSpan);
            }
        }
        return;
    }
    
    if (value > maxMesasPuestoActual) {
        input.value = maxMesasPuestoActual;
        if (mesaInfo) {
            // Limpiar mensajes de error previos
            const existingErrors = mesaInfo.querySelectorAll('.error-message');
            existingErrors.forEach(error => error.remove());
            
            // Agregar solo un mensaje de error
            const errorSpan = document.createElement('span');
            errorSpan.className = 'error-message';
            errorSpan.style.color = '#e74c3c';
            errorSpan.textContent = ` (Máximo permitido: ${maxMesasPuestoActual})`;
            mesaInfo.appendChild(errorSpan);
        }
    } else {
        // Si el valor es válido, eliminar mensajes de error
        if (mesaInfo) {
            const existingErrors = mesaInfo.querySelectorAll('.error-message');
            existingErrors.forEach(error => error.remove());
        }
    }
}

// Validar número de mesa fuera (máximo 40)
function validarNumeroMesaFuera(input) {
    const value = parseInt(input.value);
    
    if (isNaN(value) || value < 1) {
        input.value = "";
        return false;
    }
    
    if (value > 40) {
        input.value = 40;
        showNotification('El número máximo de mesas fuera es 40', 'warning');
        return false;
    }
    
    return true;
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
        
        // Verificar si el campo zona está visible
        const zonaContainer = this.closest('.form-group');
        if (zonaContainer && zonaContainer.style.display === 'none') {
            return; // No hacer nada si el campo está oculto
        }
        
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
        
        // Verificar si el campo sector está visible
        const sectorContainer = this.closest('.form-group');
        if (sectorContainer && sectorContainer.style.display === 'none') {
            return; // No hacer nada si el campo está oculto
        }
        
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
        
        // Verificar si el campo puesto está visible
        const puestoContainer = this.closest('.form-group');
        if (puestoContainer && puestoContainer.style.display === 'none') {
            return; // No hacer nada si el campo está oculto
        }
        
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

// ==================== MANEJO DEL SWITCH VOTA FUERA ====================
function setupVotaFueraSwitch() {
    const votaFueraSwitch = document.getElementById('vota_fuera_switch');
    const votaFueraHidden = document.getElementById('vota_fuera');
    
    if (!votaFueraSwitch || !votaFueraHidden) {
        console.log('Elementos de Vota Fuera no encontrados');
        return;
    }
    
    votaFueraSwitch.addEventListener('change', function() {
        // Cambiar a 'Si' o 'No' según corresponda
        votaFueraHidden.value = this.checked ? 'Si' : 'No';
        console.log('Vota Fuera cambiado a:', votaFueraHidden.value);
        updateProgress();
    });
    
    console.log('Switch Vota Fuera configurado correctamente');
}

// ==================== MANEJO DE CAMPOS VOTACIÓN DEPENDIENTES ====================
function setupVotacionCampos() {
    const votaFueraSwitch = document.getElementById('vota_fuera_switch');
    const votaFueraHidden = document.getElementById('vota_fuera');
    
    if (!votaFueraSwitch || !votaFueraHidden) {
        console.log('Elementos de Vota Fuera no encontrados');
        return;
    }
    
    // Agregar clase a los campos que dependen del switch
    agregarClaseCamposVotacion();
    
    // Configurar evento change del switch
    votaFueraSwitch.addEventListener('change', function() {
        const votaFueraValor = this.checked ? 'Si' : 'No';
        votaFueraHidden.value = votaFueraValor;
        
        // Mostrar/ocultar campos según el estado
        toggleCamposVotacion(votaFueraValor);
        
        console.log('Vota Fuera cambiado a:', votaFueraValor);
        updateProgress();
    });
    
    // Inicializar estado al cargar
    const estadoInicial = votaFueraSwitch.checked ? 'Si' : 'No';
    toggleCamposVotacion(estadoInicial);
    
    console.log('Campos de votación configurados correctamente');
}

// Agregar clase a los campos que dependen de Vota Fuera
function agregarClaseCamposVotacion() {
    const camposIds = ['zona', 'sector', 'puesto_votacion', 'mesa'];
    const camposFueraIds = ['puesto_votacion_fuera', 'mesa_fuera'];
    
    // Campos para votación normal
    camposIds.forEach(campoId => {
        const campo = document.getElementById(campoId);
        if (campo) {
            const container = campo.closest('.form-group');
            if (container) {
                container.classList.add('campo-votacion');
            }
        }
    });
    
    // Campos para votación fuera
    camposFueraIds.forEach(campoId => {
        const campo = document.getElementById(campoId);
        if (campo) {
            const container = campo.closest('.form-group');
            if (container) {
                container.classList.add('campo-fuera');
            }
        }
    });
}

// Mostrar/ocultar campos de votación según el estado de Vota Fuera
function toggleCamposVotacion(votaFueraEstado) {
    const camposVotacion = document.querySelectorAll('.campo-votacion');
    const camposFuera = document.querySelectorAll('.campo-fuera');
    
    if (votaFueraEstado === 'Si') {
        // Si vota fuera -> OCULTAR campos normales y MOSTRAR campos fuera
        camposVotacion.forEach(campo => {
            campo.style.display = 'none';
            
            // Obtener el input/select dentro del campo
            const input = campo.querySelector('select, input');
            if (input) {
                input.disabled = true;
                input.required = false;
                
                // Limpiar valores si están ocultos
                if (input.tagName === 'SELECT') {
                    input.selectedIndex = 0;
                } else if (input.tagName === 'INPUT') {
                    input.value = '';
                }
            }
        });
        
        // Mostrar campos fuera
        camposFuera.forEach(campo => {
            campo.style.display = 'block';
            
            // Obtener el input/select dentro del campo
            const input = campo.querySelector('select, input');
            if (input) {
                input.disabled = false;
                input.required = true;
            }
        });
        
        // Resetear información de mesa
        const mesaInfo = document.getElementById('mesa-info');
        if (mesaInfo) {
            mesaInfo.textContent = 'Este campo está oculto porque el referido vota fuera';
            mesaInfo.style.color = '#666';
        }
        
    } else {
        // Si NO vota fuera -> MOSTRAR campos normales y OCULTAR campos fuera
        camposVotacion.forEach(campo => {
            campo.style.display = 'block';
            
            // Obtener el input/select dentro del campo
            const input = campo.querySelector('select, input');
            if (input) {
                const id = input.id;
                
                // Habilitar/deshabilitar según la lógica de dependencias
                if (id === 'zona' || id === 'mesa') {
                    // Zona y mesa son campos principales
                    if (id === 'mesa') {
                        // Mesa depende de puesto_votacion
                        const puestoSelect = document.getElementById('puesto_votacion');
                        input.disabled = !puestoSelect || !puestoSelect.value;
                    } else {
                        input.disabled = false;
                        input.required = true;
                    }
                } else if (id === 'sector') {
                    // Sector depende de zona
                    const zonaSelect = document.getElementById('zona');
                    input.disabled = !zonaSelect || !zonaSelect.value;
                } else if (id === 'puesto_votacion') {
                    // Puesto depende de sector
                    const sectorSelect = document.getElementById('sector');
                    input.disabled = !sectorSelect || !sectorSelect.value;
                }
            }
        });
        
        // Ocultar campos fuera
        camposFuera.forEach(campo => {
            campo.style.display = 'none';
            
            // Obtener el input/select dentro del campo
            const input = campo.querySelector('select, input');
            if (input) {
                input.disabled = true;
                input.required = false;
                input.value = ''; // Limpiar valores
            }
        });
        
        // Restaurar información de mesa si está configurada
        const mesaInfo = document.getElementById('mesa-info');
        if (mesaInfo && mesaInfo.textContent.includes('oculto')) {
            mesaInfo.textContent = 'Seleccione un puesto de votación para ver las mesas disponibles';
        }
    }
    
    // Actualizar validación de campo mesa
    const mesaInput = document.getElementById('mesa');
    if (mesaInput) {
        const puestoSelect = document.getElementById('puesto_votacion');
        if (puestoSelect && puestoSelect.value) {
            configurarCampoMesa(puestoSelect.value);
        }
    }
    
    // Configurar validación para mesa fuera
    const mesaFueraInput = document.getElementById('mesa_fuera');
    if (mesaFueraInput) {
        mesaFueraInput.addEventListener('input', function() {
            validarNumeroMesaFuera(this);
        });
    }
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
    
    // Validar campo de mesa fuera cuando se pierde el foco
    const mesaFueraInput = document.getElementById('mesa_fuera');
    if (mesaFueraInput) {
        mesaFueraInput.addEventListener('blur', function() {
            validarNumeroMesaFuera(this);
        });
    }
    
    // Manejar envío del formulario
    document.getElementById('referenciacion-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const submitBtn = document.getElementById('submit-btn');
        const originalText = submitBtn.innerHTML;
        
        // 1. VALIDAR CAMPOS OBLIGATORIOS FIJOS
        const camposObligatoriosFijos = [
            {id: 'nombre', nombre: 'Nombre'},
            {id: 'apellido', nombre: 'Apellido'},
            {id: 'cedula', nombre: 'Cédula'},
            {id: 'email', nombre: 'Email'},
            {id: 'telefono', nombre: 'Teléfono'},
            {id: 'direccion', nombre: 'Dirección'},
            {id: 'sexo', nombre: 'Sexo'},
            {id: 'barrio', nombre: 'Barrio'},
            {id: 'departamento', nombre: 'Departamento'},
            {id: 'municipio', nombre: 'Municipio'},
            {id: 'apoyo', nombre: 'Oferta de Apoyo'},
            {id: 'grupo_poblacional', nombre: 'Grupo Poblacional'}
        ];
        
        let isValid = true;
        let errorMessage = '';
        let firstErrorField = null;
        
        // Validar campos obligatorios fijos
        camposObligatoriosFijos.forEach(campo => {
            const element = document.getElementById(campo.id);
            if (!element || !element.value || element.value.trim() === '') {
                isValid = false;
                errorMessage = 'Por favor complete todos los campos obligatorios (*)';
                if (element && !firstErrorField) {
                    firstErrorField = element;
                }
            }
        });
        
        // Validar afinidad (estrellas)
        if (currentRating === 0) {
            isValid = false;
            errorMessage = 'Por favor seleccione el nivel de afinidad (1-5 estrellas)';
            if (!firstErrorField) {
                firstErrorField = document.getElementById('rating-stars');
            }
        }
        
        // 2. VALIDAR CAMPOS CONDICIONALES SEGÚN "VOTA FUERA"
        const votaFueraSwitch = document.getElementById('vota_fuera_switch');
        const votaFuera = votaFueraSwitch ? votaFueraSwitch.checked : false;
        
        if (votaFuera) {
            // SI vota fuera - validar campos "fuera"
            const puestoVotacionFuera = document.getElementById('puesto_votacion_fuera');
            const mesaFuera = document.getElementById('mesa_fuera');
            
            if (!puestoVotacionFuera || !puestoVotacionFuera.value.trim()) {
                isValid = false;
                errorMessage = 'Por favor ingrese el puesto de votación fuera (obligatorio cuando vota fuera)';
                if (puestoVotacionFuera && !firstErrorField) {
                    firstErrorField = puestoVotacionFuera;
                }
            }
            
            if (!mesaFuera || !mesaFuera.value || parseInt(mesaFuera.value) < 1) {
                isValid = false;
                errorMessage = 'Por favor ingrese un número de mesa válido (1-40) para voto fuera';
                if (mesaFuera && !firstErrorField) {
                    firstErrorField = mesaFuera;
                }
            } else if (parseInt(mesaFuera.value) > 40) {
                isValid = false;
                errorMessage = 'El número de mesa fuera no puede ser mayor a 40';
                if (mesaFuera && !firstErrorField) {
                    firstErrorField = mesaFuera;
                }
            }
            // NOTA: Cuando vota fuera, los campos zona, sector, puesto_votacion, mesa NO son obligatorios
            
        } else {
            // NO vota fuera - validar campos locales de votación
            const zonaSelect = document.getElementById('zona');
            if (!zonaSelect || !zonaSelect.value) {
                isValid = false;
                errorMessage = 'Por favor seleccione la zona de votación (obligatorio cuando NO vota fuera)';
                if (zonaSelect && !firstErrorField) {
                    firstErrorField = zonaSelect;
                }
            }
            
            const sectorSelect = document.getElementById('sector');
            if (sectorSelect && !sectorSelect.disabled && !sectorSelect.value) {
                isValid = false;
                errorMessage = 'Por favor seleccione el sector (obligatorio cuando NO vota fuera)';
                if (sectorSelect && !firstErrorField) {
                    firstErrorField = sectorSelect;
                }
            }
            
            const puestoSelect = document.getElementById('puesto_votacion');
            if (puestoSelect && !puestoSelect.disabled && !puestoSelect.value) {
                isValid = false;
                errorMessage = 'Por favor seleccione el puesto de votación (obligatorio cuando NO vota fuera)';
                if (puestoSelect && !firstErrorField) {
                    firstErrorField = puestoSelect;
                }
            }
            
            const mesaInput = document.getElementById('mesa');
            if (mesaInput && !mesaInput.disabled) {
                const mesaValue = parseInt(mesaInput.value);
                if (isNaN(mesaValue) || mesaValue < 1) {
                    isValid = false;
                    errorMessage = 'Por favor ingrese un número de mesa válido (obligatorio cuando NO vota fuera)';
                    if (mesaInput && !firstErrorField) {
                        firstErrorField = mesaInput;
                    }
                } else if (mesaValue > maxMesasPuestoActual) {
                    isValid = false;
                    errorMessage = `El número de mesa no puede ser mayor a ${maxMesasPuestoActual}`;
                    if (mesaInput && !firstErrorField) {
                        firstErrorField = mesaInput;
                    }
                }
            } else if (mesaInput && mesaInput.disabled) {
                // Si está deshabilitado pero debería estar habilitado
                const puestoSelect = document.getElementById('puesto_votacion');
                if (puestoSelect && puestoSelect.value) {
                    isValid = false;
                    errorMessage = 'Por favor ingrese el número de mesa (obligatorio cuando NO vota fuera)';
                    if (mesaInput && !firstErrorField) {
                        firstErrorField = mesaInput;
                    }
                }
            }
            // NOTA: Cuando NO vota fuera, los campos puesto_votacion_fuera y mesa_fuera NO son obligatorios
        }
        
        // 3. SI HAY ERRORES, MOSTRAR Y CANCELAR ENVÍO
        if (!isValid) {
            showNotification(errorMessage, 'error');
            if (firstErrorField) {
                firstErrorField.focus();
            }
            return;
        }
        
        // 4. VALIDACIONES ADICIONALES
        // Validar cédula (solo números)
        const cedula = document.getElementById('cedula').value;
        if (!/^\d+$/.test(cedula)) {
            showNotification('La cédula solo debe contener números', 'error');
            document.getElementById('cedula').focus();
            return;
        }
        
        // Validar email
        const email = document.getElementById('email').value;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            showNotification('Por favor ingrese un email válido', 'error');
            document.getElementById('email').focus();
            return;
        }
        
        // Validar teléfono
        const telefono = document.getElementById('telefono').value;
        if (!/^\d{7,10}$/.test(telefono)) {
            showNotification('El teléfono debe contener entre 7 y 10 dígitos', 'error');
            document.getElementById('telefono').focus();
            return;
        }
        
        // 5. ENVIAR FORMULARIO
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
        submitBtn.disabled = true;
        
        try {
            const formData = new FormData(this);
            
            // Asegurar que la afinidad se envíe
            const afinidadInput = document.getElementById('afinidad');
            if (afinidadInput) {
                formData.set('afinidad', currentRating.toString());
            }
            
            // Asegurar que vota_fuera se envíe correctamente
            const votaFueraHidden = document.getElementById('vota_fuera');
            if (votaFueraHidden && votaFueraSwitch) {
                votaFueraHidden.value = votaFuera ? 'Si' : 'No';
                formData.set('vota_fuera', votaFueraHidden.value);
            }
            
            // Enviar datos
            const response = await fetch('ajax/guardar_referenciado.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showNotification(data.message || 'Registro guardado exitosamente', 'success');
                
                // ACTUALIZAR TOPE (incrementar contador)
                incrementarTope();
                
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
// ==================== MANEJO DE ASTERISCOS DINÁMICOS ====================
function setupAsteriscosDinamicos() {
    const votaFueraSwitch = document.getElementById('vota_fuera_switch');
    const asteriscosLocal = document.querySelectorAll('.obligatorio-campo-local');
    
    if (!votaFueraSwitch) return;
    
    function actualizarAsteriscos() {
        const votaFuera = votaFueraSwitch.checked;
        
        if (votaFuera) {
            // SI vota fuera - ocultar asteriscos de campos locales
            asteriscosLocal.forEach(asterisco => {
                asterisco.style.display = 'none';
            });
        } else {
            // NO vota fuera - mostrar asteriscos de campos locales
            asteriscosLocal.forEach(asterisco => {
                asterisco.style.display = 'inline';
            });
        }
    }
    
    votaFueraSwitch.addEventListener('change', actualizarAsteriscos);
    actualizarAsteriscos(); // Estado inicial
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
    const cedulaInput = document.getElementById('cedula');
    const cedulaValidationMessage = document.getElementById('cedula-validation-message');
    
    if (ratingValue) ratingValue.textContent = '0/5';
    if (afinidadInput) afinidadInput.value = '0';
    if (cedulaInput) {
        cedulaInput.classList.remove('error', 'success');
        cedulaInput.value = '';
    }
    
    if (cedulaValidationMessage) {
        cedulaValidationMessage.style.display = 'none';
    }
    
    stars.forEach(star => {
        const icon = star.querySelector('i');
        if (icon) {
            icon.className = 'far fa-star';
        }
        star.classList.remove('selected', 'hover');
    });
    
    // Resetear switch de Vota Fuera
    const votaFueraSwitch = document.getElementById('vota_fuera_switch');
    const votaFueraHidden = document.getElementById('vota_fuera');
    if (votaFueraSwitch) {
        votaFueraSwitch.checked = false;
        // Forzar actualización de campos de votación
        const event = new Event('change');
        votaFueraSwitch.dispatchEvent(event);
    }
    if (votaFueraHidden) votaFueraHidden.value = 'No';
    
    // Resetear contador de caracteres
    const compromisoTextarea = document.getElementById('compromiso');
    const compromisoChars = document.getElementById('compromiso-chars');
    if (compromisoTextarea) compromisoTextarea.value = '';
    if (compromisoChars) compromisoChars.textContent = '0';
    
    // Resetear selects dependientes
    document.getElementById('sector').disabled = true;
    document.getElementById('sector').innerHTML = '<option value="">Primero seleccione una zona</option>';
    document.getElementById('puesto_votacion').disabled = true;
    document.getElementById('puesto_votacion').innerHTML = '<option value="">Primero seleccione un sector</option>';
    document.getElementById('municipio').disabled = true;
    document.getElementById('municipio').innerHTML = '<option value="">Primero seleccione un departamento</option>';
    document.getElementById('sexo').selectedIndex = 0; // Resetear combo sexo
    
    // Resetear campo de mesas normal
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
    
    // Resetear campos fuera
    const puestoVotacionFuera = document.getElementById('puesto_votacion_fuera');
    const mesaFuera = document.getElementById('mesa_fuera');
    if (puestoVotacionFuera) {
        puestoVotacionFuera.value = '';
    }
    if (mesaFuera) {
        mesaFuera.value = '';
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

// ==================== VALIDACIÓN DE CÉDULA ====================

// Configurar validación de cédula
function setupCedulaValidation() {
    const cedulaInput = document.getElementById('cedula');
    const validationMessage = document.getElementById('cedula-validation-message');
    
    if (!cedulaInput || !validationMessage) {
        console.log('Elementos de validación de cédula no encontrados');
        return;
    }
    
    // Variable para controlar el timeout de validación
    let validationTimeout = null;
    let lastValidatedCedula = '';
    let isChecking = false;
    
    // Permitir solo números en el campo de cédula
    cedulaInput.addEventListener('input', function(e) {
        // Remover caracteres no numéricos
        this.value = this.value.replace(/[^\d]/g, '');
        
        // Validar longitud mínima y máxima
        const value = this.value;
        if (value.length < 6 || value.length > 10) {
            this.classList.add('error');
            this.classList.remove('success');
            hideValidationMessage();
        } else {
            this.classList.remove('error');
            this.classList.remove('success');
        }
    });
    
    // Validar cédula cuando el usuario termina de escribir
    cedulaInput.addEventListener('keyup', function(e) {
        const cedula = this.value.trim();
        
        // Limpiar timeout anterior
        if (validationTimeout) {
            clearTimeout(validationTimeout);
        }
        
        // Validaciones básicas
        if (cedula.length < 6 || cedula.length > 10) {
            return;
        }
        
        // Si la cédula no ha cambiado, no validar de nuevo
        if (cedula === lastValidatedCedula) {
            return;
        }
        
        // Esperar 1 segundo después de que el usuario termine de escribir
        validationTimeout = setTimeout(() => {
            checkCedulaInDatabase(cedula);
        }, 1000);
    });
    
    // También validar cuando el campo pierde el foco
    cedulaInput.addEventListener('blur', function() {
        const cedula = this.value.trim();
        
        if (cedula.length >= 6 && cedula.length <= 10 && cedula !== lastValidatedCedula) {
            checkCedulaInDatabase(cedula);
        }
    });
    
    // Función para verificar cédula en la base de datos
    function checkCedulaInDatabase(cedula) {
        if (isChecking || !cedula) return;
        
        isChecking = true;
        lastValidatedCedula = cedula;
        
        // Mostrar estado de carga
        showValidationMessage('Validando cédula...', 'loading');
        
        // Hacer petición AJAX al servidor
        fetch('ajax/verificar_cedula.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `cedula=${encodeURIComponent(cedula)}`
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la conexión');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                if (data.exists) {
                    // Cédula ya existe
                    cedulaInput.classList.add('error');
                    cedulaInput.classList.remove('success');
                    showValidationMessage('Esta cédula ya está registrada en el sistema', 'error');
                } else {
                    // Cédula disponible
                    cedulaInput.classList.remove('error');
                    cedulaInput.classList.add('success');
                    showValidationMessage('Cédula disponible', 'success');
                }
            } else {
                // Error en la validación
                cedulaInput.classList.remove('error', 'success');
                showValidationMessage('Error al validar la cédula', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            cedulaInput.classList.remove('error', 'success');
            showValidationMessage('Error de conexión al validar', 'error');
        })
        .finally(() => {
            isChecking = false;
        });
    }
    
    // Función para mostrar mensaje de validación
    function showValidationMessage(message, type) {
        if (!validationMessage) return;
        
        validationMessage.innerHTML = '';
        
        let icon = '';
        if (type === 'error') {
            icon = '<i class="fas fa-exclamation-circle"></i>';
            validationMessage.className = 'validation-message error';
        } else if (type === 'success') {
            icon = '<i class="fas fa-check-circle"></i>';
            validationMessage.className = 'validation-message success';
        } else if (type === 'loading') {
            icon = '<div class="spinner-small" style="border-top-color: #666;"></div>';
            validationMessage.className = 'validation-message';
        }
        
        validationMessage.innerHTML = `
            ${icon}
            <span>${message}</span>
        `;
        validationMessage.style.display = 'flex';
    }
    
    // Función para ocultar mensaje de validación
    function hideValidationMessage() {
        if (validationMessage) {
            validationMessage.style.display = 'none';
        }
    }
    
    console.log('Validación de cédula configurada correctamente');
}
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
            
            // Validar afinidad
            if (currentRating === 0) {
                showNotification('Por favor seleccione el nivel de afinidad', 'error');
                return;
            }
            
            // Cambiar estado del botón
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            submitBtn.disabled = true;
            
            try {
                // Aquí iría la llamada AJAX para guardar en la base de datos
                // Por ahora simulamos una respuesta exitosa
                
                // Simular tiempo de procesamiento
                await new Promise(resolve => setTimeout(resolve, 1500));
                
                // Éxito
                showNotification('Registro guardado exitosamente', 'success');
                
                // Resetear formulario
                this.reset();
                
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
                
            } catch (error) {
                showNotification('Error al guardar el registro: ' + error.message, 'error');
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
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                <span>${message}</span>
                <button class="btn-close" onclick="this.parentElement.remove()"></button>
            `;
            
            document.body.appendChild(notification);
            
            // Auto-eliminar después de 5 segundos
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 5000);
        }
        
        // Cargar combos desde base de datos (simulación)
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar contador de caracteres
            updateCharCount();
            
            // Simular carga de datos para combos
            const comboSelectors = ['#zona', '#sector', '#puesto_votacion', '#departamento', '#municipio'];
            
            comboSelectors.forEach(selector => {
                const select = document.querySelector(selector);
                if (select) {
                    // Simular carga (en producción esto sería una llamada AJAX)
                    setTimeout(() => {
                        // Por ahora agregamos opciones de ejemplo
                        if (selector === '#zona') {
                            ['Zona Norte', 'Zona Sur', 'Zona Este', 'Zona Oeste', 'Zona Centro'].forEach(zona => {
                                const option = document.createElement('option');
                                option.value = zona;
                                option.textContent = zona;
                                select.appendChild(option);
                            });
                        } else if (selector === '#sector') {
                            ['Sector A', 'Sector B', 'Sector C', 'Sector D', 'Sector E'].forEach(sector => {
                                const option = document.createElement('option');
                                option.value = sector;
                                option.textContent = sector;
                                select.appendChild(option);
                            });
                        } else if (selector === '#departamento') {
                            ['Antioquia', 'Bogotá D.C.', 'Valle del Cauca', 'Cundinamarca', 'Santander'].forEach(depto => {
                                const option = document.createElement('option');
                                option.value = depto;
                                option.textContent = depto;
                                select.appendChild(option);
                            });
                        }
                    }, 500);
                }
            });
            
            // Validar número de mesa
            const mesaInput = document.getElementById('mesa');
            if (mesaInput) {
                mesaInput.addEventListener('change', function() {
                    const value = parseInt(this.value);
                    if (value < 1) this.value = 1;
                    if (value > 30) this.value = 30;
                });
            }
        });
// Función para mostrar el modal del sistema
function mostrarModalSistema() {
    const modalElement = document.getElementById('modalSistema');
    if (modalElement) {
        // Verificar si ya hay una instancia de modal abierta
        const existingModal = bootstrap.Modal.getInstance(modalElement);
        if (existingModal) {
            existingModal.hide();
        }
        
        // Crear nueva instancia del modal
        const modal = new bootstrap.Modal(modalElement, {
            backdrop: true, // Permite cerrar haciendo clic fuera
            keyboard: true, // Permite cerrar con ESC
            focus: true // Enfoca el modal al abrir
        });
        
        // Mostrar el modal
        modal.show();
        
        // Limpiar el backdrop cuando se cierre el modal
        modalElement.addEventListener('hidden.bs.modal', function() {
            // Remover el backdrop si existe
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.remove();
            }
            
            // Remover la clase del body que bloquea el scroll
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        });
        
        // Manejar el botón de cerrar
        modalElement.addEventListener('click', function(e) {
            if (e.target.classList.contains('btn-close') || 
                e.target.classList.contains('btn-primary') && 
                e.target.textContent.includes('Cerrar')) {
                modal.hide();
            }
        });
    }
}

// Función para cerrar el modal manualmente
function cerrarModalSistema() {
    const modalElement = document.getElementById('modalSistema');
    if (modalElement) {
        const modal = bootstrap.Modal.getInstance(modalElement);
        if (modal) {
            modal.hide();
        } else {
            // Si no hay instancia, crear una y cerrarla
            const newModal = new bootstrap.Modal(modalElement);
            newModal.hide();
        }
    }
}

// Función para limpiar el backdrop manualmente
function limpiarBackdropModal() {
    const backdrop = document.querySelector('.modal-backdrop');
    if (backdrop) {
        backdrop.remove();
    }
    
    // Restaurar el body
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
}

// Inicializar modal si existe en la página
document.addEventListener('DOMContentLoaded', function() {
    // Agregar evento al logo clickable
    const logoClickable = document.querySelector('.logo-clickable');
    if (logoClickable) {
        logoClickable.addEventListener('click', mostrarModalSistema);
    }
    
    // Prevenir problemas con múltiples backdrops
    const modalElement = document.getElementById('modalSistema');
    if (modalElement) {
        // Limpiar backdrop cuando se cierra el modal
        modalElement.addEventListener('hidden.bs.modal', function() {
            // Pequeño delay para asegurar que Bootstrap haya terminado
            setTimeout(limpiarBackdropModal, 150);
        });
        
        // También limpiar en caso de que haya un error
        modalElement.addEventListener('hide.bs.modal', function() {
            // Preparar para limpiar
            setTimeout(function() {
                const backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(function(backdrop) {
                    backdrop.remove();
                });
            }, 100);
        });
    }
    
    // También agregar listener global para tecla ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modalElement = document.getElementById('modalSistema');
            if (modalElement && modalElement.classList.contains('show')) {
                cerrarModalSistema();
            }
        }
    });
    
    // Listener para clics fuera del modal
    document.addEventListener('click', function(e) {
        const modalElement = document.getElementById('modalSistema');
        if (modalElement && modalElement.classList.contains('show')) {
            if (e.target.classList.contains('modal-backdrop')) {
                cerrarModalSistema();
            }
        }
    });
});

// Versión alternativa más simple (si la anterior no funciona)
function mostrarModalSistemaSimple() {
    const modalElement = document.getElementById('modalSistema');
    if (modalElement) {
        // Remover cualquier instancia previa
        const existingModal = bootstrap.Modal.getInstance(modalElement);
        if (existingModal) {
            existingModal.dispose();
        }
        
        // Remover backdrops existentes
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
        
        // Crear nueva instancia
        const modal = new bootstrap.Modal(modalElement, {
            backdrop: true,
            keyboard: true
        });
        
        // Mostrar modal
        modal.show();
    }
}
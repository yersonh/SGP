// contador-elecciones.js
function iniciarContadorCompacto() {
    console.log('Iniciando contador de elecciones...');
    
    // Fecha específica para Colombia: 8 de marzo de 2026, 8:00 AM
    const fechaElecciones = new Date(2026, 2, 8, 8, 0, 0).getTime(); // Marzo es 2 (0=enero, 1=feb, 2=mar)
    
    console.log('Contador iniciado - Fecha objetivo:', new Date(fechaElecciones).toLocaleString('es-CO'));
    console.log('Hora actual:', new Date().toLocaleString('es-CO'));
    
    // Función que se ejecuta cada segundo
    setInterval(function() {
        const ahora = new Date().getTime();
        const tiempoRestante = fechaElecciones - ahora;
        
        // Verificar que los elementos existan
        const daysEl = document.getElementById('compact-days');
        const hoursEl = document.getElementById('compact-hours');
        const minutesEl = document.getElementById('compact-minutes');
        const secondsEl = document.getElementById('compact-seconds');
        
        if (!daysEl || !hoursEl || !minutesEl || !secondsEl) {
            console.warn('Elementos del contador no encontrados');
            return;
        }
        
        if (tiempoRestante <= 0) {
            // Fecha pasada
            daysEl.textContent = '00';
            hoursEl.textContent = '00';
            minutesEl.textContent = '00';
            secondsEl.textContent = '00';
            daysEl.style.color = '#e74c3c';
            return;
        }
        
        // Calcular
        const totalSegundos = Math.floor(tiempoRestante / 1000);
        const dias = Math.floor(totalSegundos / 86400);
        const horas = Math.floor((totalSegundos % 86400) / 3600);
        const minutos = Math.floor((totalSegundos % 3600) / 60);
        const segundos = totalSegundos % 60;
        
        // Actualizar
        daysEl.textContent = dias.toString().padStart(2, '0');
        hoursEl.textContent = horas.toString().padStart(2, '0');
        minutesEl.textContent = minutos.toString().padStart(2, '0');
        secondsEl.textContent = segundos.toString().padStart(2, '0');
        
        // Color según días restantes
        if (dias <= 7) {
            daysEl.style.color = '#e74c3c';
            daysEl.style.fontWeight = 'bold';
        } else if (dias <= 30) {
            daysEl.style.color = '#f39c12';
            daysEl.style.fontWeight = 'bold';
        } else {
            daysEl.style.color = '#ffffff';
            daysEl.style.fontWeight = 'normal';
        }
    }, 1000);
}

// Iniciar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado, iniciando contador...');
    iniciarContadorCompacto();
});

// También iniciar si el DOM ya está cargado (por si el script se carga después)
if (document.readyState === 'loading') {
    // DOM todavía cargando, esperar al evento
    document.addEventListener('DOMContentLoaded', iniciarContadorCompacto);
} else {
    // DOM ya cargado, iniciar inmediatamente
    iniciarContadorCompacto();
}
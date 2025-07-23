/**
 * PetDay - JavaScript Principal
 * Funcionalidades interactivas para la aplicación
 */

// Inicialización cuando el DOM está listo
document.addEventListener('DOMContentLoaded', function() {
    console.log('🐾 PetDay iniciando...');
    initializeApp();
});

/**
 * Inicializar la aplicación principal
 */
function initializeApp() {
    // Configurar el botón flotante (FAB)
    initializeFAB();
    
    // Actualizar la fecha y hora actual
    updateCurrentDateTime();

    // Configurar la funcionalidad de marcar rutina como completada
    setupMarkRoutineComplete();

    // Configurar el menú desplegable del usuario
    initializeUserDropdown();

    console.log('✅ PetDay iniciado correctamente');
}

/**
 * Configurar botón flotante (FAB)
 */
function initializeFAB() {
    const fabMain = document.getElementById('fabMain');
    const fabMenu = document.getElementById('fabMenu');
    
    if (fabMain && fabMenu) {
        fabMain.addEventListener('click', (e) => {
            e.stopPropagation();
            fabMenu.classList.toggle('open');
            fabMain.classList.toggle('open');
        });

        // Cerrar menú al hacer clic fuera
        document.addEventListener('click', (e) => {
            if (fabMenu.classList.contains('open') && !fabMain.contains(e.target) && !fabMenu.contains(e.target)) {
                fabMenu.classList.remove('open');
                fabMain.classList.remove('open');
            }
        });
    }
}

/**
 * Actualizar fecha y hora actual
 */
function updateCurrentDateTime() {
    const dateElement = document.getElementById('current-date');
    if (dateElement) {
        const now = new Date();
        const options = {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        };
        dateElement.textContent = now.toLocaleDateString('es-ES', options);
    }
}

/**
 * Configura los event listeners para marcar rutinas como completadas.
 */
function setupMarkRoutineComplete() {
    const buttons = document.querySelectorAll('.mark-complete-btn');
    buttons.forEach(button => {
        button.addEventListener('click', function() {
            const routineId = this.dataset.routineId;
            const routineItem = this.closest('.routine-item');

            fetch('php/routines/mark_complete.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded', // Cambiado a form-urlencoded
                },
                body: `routine_id=${routineId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (routineItem) {
                        routineItem.classList.remove('pending', 'overdue');
                        routineItem.classList.add('completed');
                        // Reemplazar el botón con el checkmark
                        const statusDiv = routineItem.querySelector('.routine-status');
                        if (statusDiv) {
                            statusDiv.innerHTML = '<span class="status-badge completed">✅</span>';
                        }
                    }
                } else {
                    alert('Error al marcar la rutina como completada: ' + (data.message || ''));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error de conexión al intentar marcar la rutina.');
            });
        });
    });
}

/**
 * Configura el menú desplegable del usuario.
 */
function initializeUserDropdown() {
    const userDropdown = document.querySelector('.user-dropdown');
    const userBtn = userDropdown ? userDropdown.querySelector('.user-btn') : null;
    const dropdownContent = userDropdown ? userDropdown.querySelector('.dropdown-content') : null;

    if (userBtn && dropdownContent) {
        userBtn.addEventListener('click', (e) => {
            e.stopPropagation(); // Evita que el clic se propague al documento
            userDropdown.classList.toggle('open');
        });

        // Cerrar el dropdown si se hace clic fuera de él
        document.addEventListener('click', (e) => {
            if (userDropdown.classList.contains('open') && !userDropdown.contains(e.target)) {
                userDropdown.classList.remove('open');
            }
        });
    }
}
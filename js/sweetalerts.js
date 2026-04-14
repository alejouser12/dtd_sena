/* === CONFIRMACIÓN DE ELIMINACIÓN CON ALERTA PERSONALIZADA === */
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btn-swal-eliminar').forEach(button => {
        button.addEventListener('click', function () {
            const registroId = this.getAttribute('data-id');
            const registroNombre = this.getAttribute('data-nombre') || 'este registro';
            const formId = 'form-eliminar-' + registroId;
            Swal.fire({
                title: '¿Eliminar registro?',
                html: `¿Estás seguro de eliminar <strong>${registroNombre}</strong>?<br>Esta acción no se puede deshacer.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-trash-alt"></i> Sí, eliminar',
                cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
                reverseButtons: true,
                customClass: {
                    confirmButton: 'swal2-confirm',
                    cancelButton: 'swal2-cancel'
                },
                willOpen: () => {
                    document.body.classList.add('swal2-open');
                },
                willClose: () => {
                    document.body.classList.remove('swal2-open');
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(formId).submit();
                }
            });
        });
    });
});
//Configuración cierre de sesión
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btn-swal-logout').forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            Swal.fire({
                title: '¿Cerrar sesión?',
                html: '¿Estás seguro que te quieres marchar?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-sign-out-alt"></i> Sí',
                cancelButtonText: '<i class="fas fa-times"></i> No',
                reverseButtons: true,
                customClass: {
                    confirmButton: 'swal2-confirm',
                    cancelButton: 'swal2-cancel'
                },
                willOpen: () => {
                    document.body.classList.add('swal2-open');
                },
                willClose: () => {
                    document.body.classList.remove('swal2-open');
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'login.php';
                }
            });
        });
    });
});

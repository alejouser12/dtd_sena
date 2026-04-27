(function () {
    function prepararEstadoCarga() {
        if (document.documentElement) {
            document.documentElement.classList.add('page-loading');
        }

        if (document.body) {
            document.body.classList.add('page-loading');
            document.body.classList.remove('page-ready');
        }
    }

    function mostrarContenido() {
        var loader = document.getElementById('loader');
        var contenido = document.getElementById('contenido-principal');

        if (document.documentElement) {
            document.documentElement.classList.remove('page-loading');
        }

        if (document.body) {
            document.body.classList.remove('page-loading');
            document.body.classList.add('page-ready');
        }

        if (loader) {
            loader.classList.add('is-hidden');
            setTimeout(function () {
                loader.style.display = 'none';
            }, 360);
        }
        if (contenido) {
            if (window.getComputedStyle(contenido).display === 'none') {
                contenido.style.display = 'block';
            }
            requestAnimationFrame(function () {
                contenido.style.opacity = '1';
            });
        }
    }

    prepararEstadoCarga();

    if (document.readyState === 'complete') {
        setTimeout(mostrarContenido, 80);
    } else {
        window.addEventListener('load', function () {
            setTimeout(mostrarContenido, 80);
        });
    }
})();

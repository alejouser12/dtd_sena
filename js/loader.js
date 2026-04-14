(function () {
    function mostrarContenido() {
        var loader = document.getElementById('loader');
        var contenido = document.getElementById('contenido-principal');
        document.body.classList.remove('page-loading');
        if (loader) {
            loader.style.opacity = '0';
            setTimeout(function () {
                loader.style.display = 'none';
            }, 280);
        }
        if (contenido) {
            contenido.style.display = 'block';
            requestAnimationFrame(function () {
                contenido.style.opacity = '1';
            });
        }
    }

    if (document.readyState === 'complete') {
        setTimeout(mostrarContenido, 120);
    } else {
        window.addEventListener('load', function () {
            setTimeout(mostrarContenido, 120);
        });
    }
})();

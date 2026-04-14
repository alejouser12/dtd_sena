//BLOQUEO DE DESPLAZAMIENTO (SCROLL LOCK) PARA MENÚS MODALES 
const menuToggle = document.getElementById('menu-toggle');
const menuDropdowns = document.getElementById('menu-dropdowns');

if (!window.__scrollLock) {
    window.__scrollLock = (function () {
        let count = 0;
        let allowedSelectors = [];
        function isAllowedTarget(target) {
            try {
                return allowedSelectors.some(sel => target && target.closest && target.closest(sel));
            } catch (err) {
                return false;
            }
        }
        function wheelHandler(e) {
            if (!isAllowedTarget(e.target)) e.preventDefault();
        }
        function touchHandler(e) {
            if (!isAllowedTarget(e.target)) e.preventDefault();
        }
        return {
            lock(selector) {
                if (count === 0) {
                    document.documentElement.style.overflow = 'hidden';
                    document.body.style.overflow = 'hidden';
                    window.addEventListener('wheel', wheelHandler, { passive: false });
                    window.addEventListener('touchmove', touchHandler, { passive: false });
                }
                count++;
                if (selector && !allowedSelectors.includes(selector)) allowedSelectors.push(selector);
            },
            unlock(selector) {
                count = Math.max(0, count - 1);
                if (selector) allowedSelectors = allowedSelectors.filter(s => s !== selector);
                if (count === 0) {
                    document.documentElement.style.overflow = '';
                    document.body.style.overflow = '';
                    window.removeEventListener('wheel', wheelHandler);
                    window.removeEventListener('touchmove', touchHandler);
                }
            }
        };
    })();
}

// Control del menú principal desplegable (navbar)
if (menuToggle && menuDropdowns) {
    let menuBackdrop = document.getElementById('menu-backdrop');
    if (!menuBackdrop) {
        menuBackdrop = document.createElement('div');
        menuBackdrop.id = 'menu-backdrop';
        menuBackdrop.className = 'profile-backdrop';
        menuBackdrop.style.zIndex = '989';
        document.body.appendChild(menuBackdrop);
    }
    const openMenu = () => {
        const profileMenu = document.getElementById('profile-menu');
        if (profileMenu && profileMenu.classList.contains('active')) {
            const profileEvent = new Event('closeProfile');
            document.dispatchEvent(profileEvent);
        }
        menuDropdowns.classList.add('active');
        menuBackdrop.style.display = 'block';
        document.body.classList.add('menu-open');
        window.__scrollLock.lock('#menu-dropdowns');
    };
    const closeMenu = () => {
        menuDropdowns.classList.remove('active');
        menuBackdrop.style.display = 'none';
        document.body.classList.remove('menu-open');
        window.__scrollLock.unlock('#menu-dropdowns');
    };
    document.addEventListener('closeDropdown', () => {
        closeMenu();
    });
    document.addEventListener('click', (e) => {
        if (!menuDropdowns.contains(e.target) && !menuToggle.contains(e.target)) {
            closeMenu();
        }
    });
    menuToggle.addEventListener('click', (e) => {
        e.stopPropagation();
        if (menuDropdowns.classList.contains('active')) {
            closeMenu();
        } else {
            openMenu();
        }
    });
    menuDropdowns.addEventListener('click', (e) => {
        e.stopPropagation();
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeMenu();
    });
}
// Submenús dentro de las tarjetas del menú
document.addEventListener('DOMContentLoaded', () => {
    const toggleButtons = document.querySelectorAll('.menu-toggle-card');
    toggleButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const parentCard = button.closest('.menu-dropdown-card');
            const content = parentCard.querySelector('.menu-dropdown-content');
            if (content.classList.contains('active')) {
                content.style.maxHeight = '0';
                content.classList.remove('active');
                button.classList.remove('active');
            } else {
                content.classList.add('active');
                button.classList.add('active');
                const scrollHeight = content.scrollHeight;
                content.style.maxHeight = scrollHeight + 'px';
            }
        });
    });
});

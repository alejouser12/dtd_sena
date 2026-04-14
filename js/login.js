// CONFIGURACIÓN DE LOGOS FLOTANTES DE FONDO
const logoUrl = 'img/logo_sena_verde.png';
const numLogos = 30;
const logoSize = 75; 
const minDistance = 90; 
const logos = [];
class Logo {
    constructor(x, y, vx, vy) {
        this.x = x;
        this.y = y;
        this.vx = vx;
        this.vy = vy;
        this.size = logoSize;
        this.element = document.createElement('div');
        this.element.className = 'logo-bg';
        this.element.innerHTML = `<img src="${logoUrl}" alt="SENA">`;
        document.body.appendChild(this.element);
        this.updatePosition();
    }
    updatePosition() {
        this.element.style.left = this.x + 'px';
        this.element.style.top = this.y + 'px';
    }
    move() {
        this.x += this.vx;
        this.y += this.vy;
        if (this.x <= 0 || this.x >= window.innerWidth - this.size) {
            this.vx *= -1;
            this.x = Math.max(0, Math.min(this.x, window.innerWidth - this.size));
        }
        if (this.y <= 0 || this.y >= window.innerHeight - this.size) {
            this.vy *= -1;
            this.y = Math.max(0, Math.min(this.y, window.innerHeight - this.size));
        }
        this.updatePosition();
    }
}
function distance(x1, y1, x2, y2) {
    return Math.sqrt((x2 - x1) ** 2 + (y2 - y1) ** 2);
}
function isPositionValid(x, y, existingLogos) {
    const centerX = window.innerWidth / 2;
    const centerY = window.innerHeight / 2;
    if (distance(x, y, centerX, centerY) < 300) {
        return false;
    }
    for (let logo of existingLogos) {
        if (distance(x, y, logo.x, logo.y) < minDistance) {
            return false;
        }
    }
    return true;
}
function createLogos() {
    for (let i = 0; i < numLogos; i++) {
        let x, y;
        let attempts = 0;
        const maxAttempts = 100;
        do {
            x = Math.random() * (window.innerWidth - logoSize);
            y = Math.random() * (window.innerHeight - logoSize);
            attempts++;
        } while (!isPositionValid(x, y, logos) && attempts < maxAttempts);
        if (attempts < maxAttempts) {
            const speed = 0.1 + Math.random() * 0.1;
            const angle = Math.random() * Math.PI * 2;
            const vx = Math.cos(angle) * speed;
            const vy = Math.sin(angle) * speed;
            logos.push(new Logo(x, y, vx, vy));
        }
    }
}
function checkCollisions() {
    for (let i = 0; i < logos.length; i++) {
        for (let j = i + 1; j < logos.length; j++) {
            const logo1 = logos[i];
            const logo2 = logos[j];
            const dist = distance(logo1.x, logo1.y, logo2.x, logo2.y);
            if (dist < logoSize) {
                const angle = Math.atan2(logo2.y - logo1.y, logo2.x - logo1.x);
                const targetX = logo1.x + Math.cos(angle) * logoSize;
                const targetY = logo1.y + Math.sin(angle) * logoSize;
                const ax = (targetX - logo2.x) * 0.05;
                const ay = (targetY - logo2.y) * 0.05;
                logo1.vx -= ax;
                logo1.vy -= ay;
                logo2.vx += ax;
                logo2.vy += ay;
            }
        }
    }
}
function animate() {
    checkCollisions();
    logos.forEach(logo => logo.move());
    requestAnimationFrame(animate);
}
createLogos();
animate();
window.addEventListener('resize', () => {
    logos.forEach(logo => {
        logo.x = Math.min(logo.x, window.innerWidth - logoSize);
        logo.y = Math.min(logo.y, window.innerHeight - logoSize);
        logo.updatePosition();
    });
});
let isAnimating = false;
document.querySelectorAll('.login-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        if (isAnimating) return;
        const currentActiveForm = document.querySelector('.login-form.active');
        const targetForm = document.getElementById(`${tab.dataset.tab}-form`);
        if (!currentActiveForm || currentActiveForm === targetForm) return;
        isAnimating = true;
        document.querySelectorAll('.login-tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        const isLoginToSignup = tab.dataset.tab === 'signup';
        if (isLoginToSignup) {
            currentActiveForm.classList.add('to-right');
        } else {
            currentActiveForm.classList.add('to-left');
        }
        setTimeout(() => {
            currentActiveForm.classList.remove('active', 'to-left', 'to-right');
            if (isLoginToSignup) {
                targetForm.classList.add('from-right');
            } else {
                targetForm.classList.add('from-left');
            }
            targetForm.classList.add('active');
            setTimeout(() => {
                targetForm.classList.remove('from-left', 'from-right');
                isAnimating = false;
            }, 350);
        }, 350);
    });
});
document.querySelectorAll('.toggle-password').forEach(icon => {
    icon.addEventListener('click', () => {
        const input = icon.previousElementSibling;
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    });
});
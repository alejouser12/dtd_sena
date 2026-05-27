<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('ROL_ADMIN', 'admin');
define('ROL_INSTRUCTOR', 'instructor');
define('ROL_APRENDIZ', 'aprendiz');

/**
 * Verifica si el usuario actual tiene el rol especificado
 */
function tieneRol($rol) {
    return isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === $rol;
}

/**
 * Verifica si el usuario es administrador
 */
function esAdmin() {
    return tieneRol(ROL_ADMIN);
}

/**
 * Verifica si el usuario es instructor (docente)
 */
function esInstructor() {
    return tieneRol(ROL_INSTRUCTOR);
}

/**
 * Verifica si el usuario es aprendiz
 */
function esAprendiz() {
    return tieneRol(ROL_APRENDIZ);
}

/**
 * Verifica si el usuario puede crear nuevos registros en un módulo
 * @param string $modulo Nombre del módulo (ej. 'aprendices', 'programas', etc.)
 */
function puedeCrear($modulo) {
    if (esAdmin()) return true;
    if (esInstructor()) {
        // Instructores pueden crear aprendices y calificar evidencias
        $modulos_permitidos = ['aprendices', 'evidencias'];
        return in_array($modulo, $modulos_permitidos);
    }
    return false;
}

/**
 * Verifica si el usuario puede editar registros
 */
function puedeEditar($modulo) {
    return esAdmin(); // Solo admin puede editar
}

/**
 * Verifica si el usuario puede eliminar registros
 */
function puedeEliminar($modulo) {
    return esAdmin(); // Solo admin puede eliminar
}

/**
 * Verifica si el usuario puede ver el módulo (acceso de lectura)
 * Todos los usuarios autenticados pueden ver, excepto algunos módulos restringidos
 */
function puedeVerModulo($modulo) {
    if (esAdmin()) return true;
    // Instructores pueden ver todos los módulos excepto quizás algunos
    if (esInstructor()) {
        $modulos_restringidos = ['regionales', 'centros']; // Ajusta según sea necesario
        return !in_array($modulo, $modulos_restringidos);
    }
    return false;
}
?>
<?php
// laboratorio/src/Helpers/helpers.php

if (!function_exists('getLabUserRole')) {
    function getLabUserRole(): string {
        return strtolower(trim($_SESSION['user_rol'] ?? ''));
    }
}

if (!function_exists('isLabAdmin')) {
    function isLabAdmin(): bool {
        return in_array(getLabUserRole(), ['admin_lab', 'admin', 'administrador']);
    }
}

if (!function_exists('isLabTecnico')) {
    function isLabTecnico(): bool {
        return in_array(getLabUserRole(), ['tecnico_laboratorio', 'tecnico_lab']) || isLabAdmin();
    }
}

// Helpers de Estilos para el Menú
if (!function_exists('setLabActive')) {
    function setLabActive(string $route, ?string $currentUri = null): string {
        $uri = $currentUri ?? parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        return (strpos($uri, $route) !== false) 
            ? 'modulo-activo-plata' 
            : 'text-gray-400 hover:bg-neutral-900 hover:text-slate-200';
    }
}

if (!function_exists('setLabIcon')) {
    function setLabIcon(string $route, ?string $currentUri = null): string {
        $uri = $currentUri ?? parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        return (strpos($uri, $route) !== false) 
            ? 'text-slate-950' 
            : 'text-slate-400 group-hover:text-slate-200';
    }
}

if (!function_exists('isLabGroupOpen')) {
    function isLabGroupOpen(array $routes, ?string $currentUri = null): string {
        $uri = $currentUri ?? parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        foreach ($routes as $route) {
            if (strpos($uri, $route) !== false) {
                return 'open';
            }
        }
        return '';
    }
}
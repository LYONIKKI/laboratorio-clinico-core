<?php
// /var/www/html/laboratorio/src/Config/auth.php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response;

return function (array $rolesPermitidos = []) {
    return function (Request $request, Handler $handler) use ($rolesPermitidos) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // 1. Validar si existe sesión activa desde login.php
        if (!isset($_SESSION['user_id']) && !isset($_SESSION['user_name'])) {
            $response = new Response();
            return $response->withHeader('Location', '/login.php')->withStatus(302);
        }

        // 2. Control de tiempo de inactividad (1 hora)
        $tiempo_maximo = 3600;
        if (isset($_SESSION['ultimo_acceso']) && (time() - $_SESSION['ultimo_acceso'] > $tiempo_maximo)) {
            session_unset();
            session_destroy();
            $response = new Response();
            return $response->withHeader('Location', '/login.php?error=sesion_expirada')->withStatus(302);
        }
        $_SESSION['ultimo_acceso'] = time();

        // 3. Normalizar rol del usuario
        $rolUsuario = strtolower(trim((string)($_SESSION['user_rol'] ?? '')));

        // Acceso irrestricto para administradores
        if ($rolUsuario === 'admin' || $rolUsuario === 'administrador') {
            return $handler->handle($request);
        }

        // 4. Validar rol según los permisos del módulo
        if (!empty($rolesPermitidos)) {
            $rolesPermitidosMin = array_map('strtolower', $rolesPermitidos);
            
            if (!in_array($rolUsuario, $rolesPermitidosMin)) {
                $response = new Response();
                if ($request->getHeaderLine('Accept') === 'application/json' || 
                    strpos($request->getUri()->getPath(), '/api/') !== false) {
                    $response->getBody()->write(json_encode([
                        'status' => 'error',
                        'message' => 'Acceso denegado al módulo de laboratorio.'
                    ]));
                    return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
                }
                return $response->withHeader('Location', '/login.php?error=modulo_no_autorizado')->withStatus(302);
            }
        }

        return $handler->handle($request);
    };
};
<?php
// src/Controllers/UsuarioController.php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UsuarioController {
    protected $container;

    public function __construct($container) {
        $this->container = $container;
    }

    // 1. RENDERIZAR VISTA
    public function vistaUsuarios(Request $request, Response $response) {
        ob_start();
        include dirname(__DIR__, 2) . '/public/views/usuarios.php';
        $html = ob_get_clean();
        $response->getBody()->write($html);
        return $response;
    }

    // 2. API: LISTAR USUARIOS DE LABORATORIO
    public function listarUsuarios(Request $request, Response $response) {
        $pg = $this->container->get('db_auth');
        $query = "SELECT id, nombre, usuario, rol, estado, slug_destino 
                  FROM public.usuarios_sigh 
                  WHERE slug_destino = 'LAB' OR rol IN ('admin_lab', 'tecnico_lab', 'medico_lab')
                  ORDER BY id DESC";

        $res = pg_query($pg, $query);
        $usuarios = [];

        if ($res) {
            while ($row = pg_fetch_assoc($res)) {
                $usuarios[] = [
                    'id'      => intval($row['id']),
                    'nombre'  => trim($row['nombre']),
                    'usuario' => trim($row['usuario']),
                    'rol'     => trim($row['rol']),
                    'estado'  => intval($row['estado'])
                ];
            }
        }

        $response->getBody()->write(json_encode(['status' => 'success', 'data' => $usuarios]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    // 3. API: CREAR USUARIO (EXCLUSIVO)
    public function crearUsuario(Request $request, Response $response) {
        $pg = $this->container->get('db_auth');
        $body = $request->getParsedBody() ?? json_decode(file_get_contents('php://input'), true) ?? [];

        $nombre   = trim($body['nombre'] ?? '');
        $usuario  = trim($body['usuario'] ?? '');
        $password = trim($body['password'] ?? '');
        $rol      = trim($body['rol'] ?? 'tecnico_lab');
        $slug     = 'LAB';

        if (empty($nombre) || empty($usuario) || empty($password)) {
            $response->getBody()->write(json_encode(['status' => 'error', 'message' => 'Todos los campos son obligatorios.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Verificar si el login ya existe
        $check = pg_query_params($pg, "SELECT id FROM public.usuarios_sigh WHERE usuario = $1", [$usuario]);
        if (pg_num_rows($check) > 0) {
            $response->getBody()->write(json_encode(['status' => 'error', 'message' => "El usuario '{$usuario}' ya se encuentra registrado."]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $sql = "INSERT INTO public.usuarios_sigh (nombre, usuario, password, rol, estado, slug_destino) 
                VALUES ($1, $2, $3, $4, 1, $5)";
        $res = pg_query_params($pg, $sql, [$nombre, $usuario, $password, $rol, $slug]);

        if (!$res) {
            $response->getBody()->write(json_encode(['status' => 'error', 'message' => 'Error al insertar en PostgreSQL.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }

        $response->getBody()->write(json_encode(['status' => 'success', 'message' => 'Usuario creado exitosamente.']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    // 4. API: ACTUALIZAR USUARIO (EXCLUSIVO)
    public function actualizarUsuario(Request $request, Response $response) {
        $pg = $this->container->get('db_auth');
        $body = $request->getParsedBody() ?? json_decode(file_get_contents('php://input'), true) ?? [];

        $id       = intval($body['id'] ?? 0);
        $nombre   = trim($body['nombre'] ?? '');
        $usuario  = trim($body['usuario'] ?? '');
        $password = trim($body['password'] ?? '');
        $rol      = trim($body['rol'] ?? 'tecnico_lab');
        $slug     = 'LAB';

        if ($id <= 0 || empty($nombre) || empty($usuario)) {
            $response->getBody()->write(json_encode(['status' => 'error', 'message' => 'Datos inválidos para actualizar.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        if (!empty($password)) {
            $sql = "UPDATE public.usuarios_sigh 
                    SET nombre = $1, usuario = $2, password = $3, rol = $4, slug_destino = $5 
                    WHERE id = $6";
            $res = pg_query_params($pg, $sql, [$nombre, $usuario, $password, $rol, $slug, $id]);
        } else {
            $sql = "UPDATE public.usuarios_sigh 
                    SET nombre = $1, usuario = $2, rol = $3, slug_destino = $4 
                    WHERE id = $5";
            $res = pg_query_params($pg, $sql, [$nombre, $usuario, $rol, $slug, $id]);
        }

        if (!$res) {
            $response->getBody()->write(json_encode(['status' => 'error', 'message' => 'Error al actualizar usuario.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }

        $response->getBody()->write(json_encode(['status' => 'success', 'message' => 'Usuario actualizado correctamente.']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    // 5. API: CAMBIAR ESTADO (ACTIVAR / DESACTIVAR)
    public function conmutarEstado(Request $request, Response $response) {
        $pg = $this->container->get('db_auth');
        $body = $request->getParsedBody() ?? json_decode(file_get_contents('php://input'), true);
        $id = intval($body['id'] ?? 0);

        if ($id <= 0) {
            $response->getBody()->write(json_encode(['status' => 'error', 'message' => 'ID inválido']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $sql = "UPDATE public.usuarios_sigh 
                SET estado = CASE WHEN estado = 1 THEN 0 ELSE 1 END 
                WHERE id = $1";
        $res = pg_query_params($pg, $sql, [$id]);

        $response->getBody()->write(json_encode(['status' => $res ? 'success' : 'error']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    // 6. API: ELIMINAR USUARIO DEFINITIVAMENTE (POSTGRESQL)
    public function eliminarUsuario(Request $request, Response $response) {
        $pg = $this->container->get('db_auth');
        $body = $request->getParsedBody() ?? json_decode(file_get_contents('php://input'), true);
        $id = intval($body['id'] ?? 0);

        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        $idSesion = intval($_SESSION['user_id'] ?? 0);

        if ($id <= 0) {
            $response->getBody()->write(json_encode(['status' => 'error', 'message' => 'Identificador de usuario inválido.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Medida de seguridad: Evitar que el administrador se borre a sí mismo
        if ($id === $idSesion) {
            $response->getBody()->write(json_encode(['status' => 'error', 'message' => 'No puedes eliminar tu propia cuenta en sesión activa.']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $sql = "DELETE FROM public.usuarios_sigh WHERE id = $1";
        $res = pg_query_params($pg, $sql, [$id]);

        if (!$res) {
            $errorMsg = pg_last_error($pg);
            // Si tiene registros vinculados por llave foránea
            if (strpos($errorMsg, 'violates foreign key constraint') !== false) {
                $mensaje = 'No se puede eliminar el usuario porque tiene registros clínicos o auditorías asociadas. Te sugerimos desactivarlo.';
            } else {
                $mensaje = 'Error al eliminar usuario en PostgreSQL.';
            }

            $response->getBody()->write(json_encode(['status' => 'error', 'message' => $mensaje]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }

        $response->getBody()->write(json_encode(['status' => 'success', 'message' => 'Usuario eliminado correctamente del sistema.']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
}
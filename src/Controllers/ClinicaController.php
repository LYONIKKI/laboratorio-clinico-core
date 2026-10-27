<?php
// src/Controllers/ClinicaController.php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ClinicaController {
    protected $container;

    public function __construct($container) {
        $this->container = $container;
    }

    // API: Búsqueda reactiva de Diagnósticos CIE-10
    public function buscarDiagnostico(Request $request, Response $response) {
        $conn = $this->container->get('db');
        $params = $request->getQueryParams();
        $term = trim($params['term'] ?? '');

        if (strlen($term) < 2) {
            $response->getBody()->write(json_encode(['status' => 'success', 'data' => []]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        $sql = "SELECT TOP 12 IdDiagnostico, RTRIM(CodigoCIE2004) AS codigo, RTRIM(Descripcion) AS Descripcion 
                FROM Diagnosticos 
                WHERE Descripcion LIKE ? OR CodigoCIE2004 LIKE ? 
                ORDER BY Descripcion ASC";
        
        $stmt = sqlsrv_query($conn, $sql, ["%$term%", "$term%"]);
        $diagnosticos = [];
        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $diagnosticos[] = [
                    'id'          => $row['IdDiagnostico'],
                    'codigo'      => trim($row['codigo']),
                    'descripcion' => trim($row['Descripcion'])
                ];
            }
        }

        $response->getBody()->write(json_encode(['status' => 'success', 'data' => $diagnosticos]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    // API: Búsqueda reactiva de Médicos Prescriptores
    public function buscarMedico(Request $request, Response $response) {
        $conn = $this->container->get('db');
        $params = $request->getQueryParams();
        $term = trim($params['term'] ?? '');

        if (strlen($term) < 2) {
            $response->getBody()->write(json_encode(['status' => 'success', 'data' => []]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        $sql = "SELECT TOP 10 m.IdMedico, 
                       UPPER(ISNULL(e.ApellidoPaterno + ' ' + e.ApellidoMaterno + ' ' + e.Nombres, 'MEDICO SOLICITANTE')) AS Medico_solicitante
                FROM Medicos m 
                INNER JOIN Empleados e ON m.IdEmpleado = e.IdEmpleado
                WHERE e.ApellidoPaterno LIKE ? OR e.ApellidoMaterno LIKE ? OR e.Nombres LIKE ? 
                ORDER BY e.ApellidoPaterno ASC";
        
        $stmt = sqlsrv_query($conn, $sql, ["%$term%", "%$term%", "%$term%"]);
        $medicos = [];
        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $medicos[] = [
                    'id'     => $row['IdMedico'],
                    'nombre' => trim($row['Medico_solicitante'])
                ];
            }
        }

        $response->getBody()->write(json_encode(['status' => 'success', 'data' => $medicos]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    // API: Búsqueda reactiva de Servicios / Procedencias
    public function buscarServicio(Request $request, Response $response) {
        $conn = $this->container->get('db');
        $params = $request->getQueryParams();
        $term = trim($params['term'] ?? '');

        $sql = "SELECT TOP 10 IdServicio, UPPER(Nombre) AS Nombre 
                FROM Servicios 
                WHERE Nombre LIKE ? 
                ORDER BY Nombre ASC";
        
        $stmt = sqlsrv_query($conn, $sql, ["%$term%"]);
        $servicios = [];
        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $servicios[] = [
                    'id'     => $row['IdServicio'],
                    'nombre' => trim($row['Nombre'])
                ];
            }
        }

        $response->getBody()->write(json_encode(['status' => 'success', 'data' => $servicios]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    // API: Actualizar Cama, Diagnóstico, Médico y Servicio en dbo.LabMovimientoLaboratorio
    public function actualizarCabeceraOrden(Request $request, Response $response) {
        $conn = $this->container->get('db');
        $body = $request->getParsedBody() ?: json_decode(file_get_contents('php://input'), true);

        $idMovimiento = intval($body['idMovimiento'] ?? 0);
        $cama         = trim($body['cama'] ?? '---');
        $diagnostico  = trim($body['diagnostico'] ?? '---');
        $medico       = trim($body['medico'] ?? '');
        $servicio     = trim($body['servicio'] ?? '');

        if ($idMovimiento <= 0) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Movimiento inválido']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $sql = "UPDATE dbo.LabMovimientoLaboratorio 
                SET NroCama = ?, 
                    Diagnostico = ?,
                    OrdenaPrueba = CASE WHEN ? <> '' THEN ? ELSE OrdenaPrueba END,
                    ProcedenciaServicio = CASE WHEN ? <> '' THEN ? ELSE ProcedenciaServicio END
                WHERE IdMovimiento = ?";
        
        $stmt = sqlsrv_query($conn, $sql, [$cama, $diagnostico, $medico, $medico, $servicio, $servicio, $idMovimiento]);

        if ($stmt === false) {
            $err = sqlsrv_errors();
            $response->getBody()->write(json_encode(['success' => false, 'error' => $err[0]['message'] ?? 'Error SQL']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }

        $response->getBody()->write(json_encode(['success' => true, 'message' => 'Cabecera clínica actualizada']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    // API: Búsqueda reactiva de productos CPT de Laboratorio
    public function buscarCatalogoCPT(Request $request, Response $response) {
        $conn = $this->container->get('db');
        $params = $request->getQueryParams();
        $term = trim($params['term'] ?? '');

        if (strlen($term) < 2) {
            $response->getBody()->write(json_encode(['status' => 'success', 'data' => []]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        $sql = "SELECT TOP 15
                    fcs.IdProducto,
                    ISNULL(RTRIM(fcs.Codigo), '') AS Codigo,
                    RTRIM(fcs.Nombre) AS Nombre
                FROM dbo.FactCatalogoServicios fcs
                WHERE fcs.Nombre LIKE ?
                   OR fcs.Codigo LIKE ?
                ORDER BY fcs.Nombre ASC";

        $paramLike = "%$term%";
        $stmt = sqlsrv_query($conn, $sql, [$paramLike, $paramLike]);
        $data = [];

        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $data[] = [
                    'idProducto' => intval($row['IdProducto']),
                    'codigo'     => trim($row['Codigo']),
                    'nombre'     => trim($row['Nombre'])
                ];
            }
        }

        $response->getBody()->write(json_encode(['status' => 'success', 'data' => $data]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
}
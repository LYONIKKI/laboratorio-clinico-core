<?php
// src/Controllers/ResultadosController.php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ResultadosController {
    protected $container;

    public function __construct($container) {
        $this->container = $container;
    }

    public function vistaResultados(Request $request, Response $response) {
        ob_start();
        include dirname(__DIR__, 2) . '/public/views/resultados.php';
        $html = ob_get_clean();
        $response->getBody()->write($html);
        return $response;
    }

    // API: Búsqueda histórica sin límite de días por Cuenta, Atención, Movimiento, DNI o Paciente
    public function buscarOrdenesPaciente(Request $request, Response $response) {
        $conn = $this->container->get('db');
        $params = $request->getQueryParams();
        $q = trim($params['q'] ?? '');

        if (empty($q)) {
            $response->getBody()->write(json_encode(['status' => 'success', 'data' => []]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        }

        if (is_numeric($q)) {
            $num = intval($q);
            $sql = "SELECT DISTINCT TOP 50
                        l.IdMovimiento AS Nro_Movimiento,
                        a.IdAtencion AS Nro_Atencion,
                        lml.IdCuentaAtencion AS Nro_Cuenta,
                        (CONVERT(VARCHAR(5), l.Fecha, 103) + ' ' + CONVERT(VARCHAR(5), l.Fecha, 108)) AS Hora_Format,
                        p.NroDocumento AS DNI,
                        p.NroHistoriaClinica AS HC,
                        p.ApellidoPaterno + ' ' + p.ApellidoMaterno + ' ' + p.PrimerNombre AS Nombre_Lab,
                        UPPER(ISNULL(s.Nombre, ISNULL(lml.ProcedenciaServicio, 'CONSULTA EXTERNA'))) AS Servicio_Procedencia,
                        UPPER(ISNULL(lml.EstadoEntrega, 'PENDIENTE')) AS Estado,
                        (SELECT COUNT(*) FROM dbo.LabMovimientoCPT WHERE idMovimiento = l.IdMovimiento) AS Cantidad_Examenes,
                        (SELECT COUNT(DISTINCT IdProductoCPT) 
                         FROM dbo.LabResultadoDetalle 
                         WHERE IdMovimiento = l.IdMovimiento 
                           AND ValorResultado IS NOT NULL 
                           AND RTRIM(LTRIM(ValorResultado)) <> '') AS Cantidad_Completados
                    FROM dbo.Atenciones a
                    INNER JOIN dbo.Pacientes p 
                        ON a.IdPaciente = p.IdPaciente
                    INNER JOIN dbo.LabMovimientoLaboratorio lml 
                        ON CAST(a.IdCuentaAtencion AS VARCHAR(50)) = RTRIM(LTRIM(lml.IdCuentaAtencion))
                    INNER JOIN dbo.LabMovimiento l 
                        ON lml.IdMovimiento = l.IdMovimiento
                    LEFT JOIN dbo.FactOrdenServicio fos 
                        ON lml.IdOrden = fos.IdOrden
                    LEFT JOIN dbo.Servicios s 
                        ON fos.IdServicioPaciente = s.IdServicio
                    WHERE a.IdAtencion = ? 
                       OR a.IdCuentaAtencion = ? 
                       OR l.IdMovimiento = ? 
                       OR p.NroDocumento = ?
                    ORDER BY l.IdMovimiento DESC";

            $queryParams = [$num, $num, $num, $q];
        } else {
            $sql = "SELECT DISTINCT TOP 50
                        l.IdMovimiento AS Nro_Movimiento,
                        a.IdAtencion AS Nro_Atencion,
                        lml.IdCuentaAtencion AS Nro_Cuenta,
                        (CONVERT(VARCHAR(5), l.Fecha, 103) + ' ' + CONVERT(VARCHAR(5), l.Fecha, 108)) AS Hora_Format,
                        p.NroDocumento AS DNI,
                        p.NroHistoriaClinica AS HC,
                        p.ApellidoPaterno + ' ' + p.ApellidoMaterno + ' ' + p.PrimerNombre AS Nombre_Lab,
                        UPPER(ISNULL(s.Nombre, ISNULL(lml.ProcedenciaServicio, 'CONSULTA EXTERNA'))) AS Servicio_Procedencia,
                        UPPER(ISNULL(lml.EstadoEntrega, 'PENDIENTE')) AS Estado,
                        (SELECT COUNT(*) FROM dbo.LabMovimientoCPT WHERE idMovimiento = l.IdMovimiento) AS Cantidad_Examenes,
                        (SELECT COUNT(DISTINCT IdProductoCPT) 
                         FROM dbo.LabResultadoDetalle 
                         WHERE IdMovimiento = l.IdMovimiento 
                           AND ValorResultado IS NOT NULL 
                           AND RTRIM(LTRIM(ValorResultado)) <> '') AS Cantidad_Completados
                    FROM dbo.Atenciones a
                    INNER JOIN dbo.Pacientes p 
                        ON a.IdPaciente = p.IdPaciente
                    INNER JOIN dbo.LabMovimientoLaboratorio lml 
                        ON CAST(a.IdCuentaAtencion AS VARCHAR(50)) = RTRIM(LTRIM(lml.IdCuentaAtencion))
                    INNER JOIN dbo.LabMovimiento l 
                        ON lml.IdMovimiento = l.IdMovimiento
                    LEFT JOIN dbo.FactOrdenServicio fos 
                        ON lml.IdOrden = fos.IdOrden
                    LEFT JOIN dbo.Servicios s 
                        ON fos.IdServicioPaciente = s.IdServicio
                    WHERE (p.ApellidoPaterno + ' ' + p.ApellidoMaterno + ' ' + p.PrimerNombre) LIKE ?
                       OR lml.Paciente LIKE ?
                    ORDER BY l.IdMovimiento DESC";

            $paramLike = "%$q%";
            $queryParams = [$paramLike, $paramLike];
        }

        $stmt = sqlsrv_query($conn, $sql, $queryParams);

        if ($stmt === false) {
            $err = sqlsrv_errors();
            $response->getBody()->write(json_encode([
                'status'  => 'error', 
                'message' => $err[0]['message'] ?? 'Error SQL'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }

        $data = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $total = intval($row['Cantidad_Examenes'] ?? 0);
            $completados = intval($row['Cantidad_Completados'] ?? 0);
            $porcentaje = ($total > 0) ? round(($completados / $total) * 100) : 0;

            $data[] = [
                'Nro_Movimiento'       => $row['Nro_Movimiento'],
                'Nro_Atencion'         => $row['Nro_Atencion'],
                'Nro_Cuenta'           => $row['Nro_Cuenta'],
                'hora_format'          => $row['Hora_Format'] ?? '---',
                'Nombre_Lab'           => $row['Nombre_Lab'],
                'DNI'                  => $row['DNI'] ?? 'SIN DNI',
                'HC'                   => $row['HC'] ?? '---',
                'Estado'               => $row['Estado'] ?? 'PENDIENTE',
                'Cantidad_Examenes'    => $total,
                'Cantidad_Completados' => $completados,
                'Porcentaje_Avance'    => $porcentaje,
                'Servicio_Procedencia' => $row['Servicio_Procedencia'] ?? 'CONSULTA EXTERNA'
            ];
        }

        $response->getBody()->write(json_encode(['status' => 'success', 'data' => $data]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
}
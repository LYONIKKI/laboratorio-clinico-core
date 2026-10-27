<?php
// src/Controllers/SeguimientoController.php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class SeguimientoController {
    protected $container;

    public function __construct($container) {
        $this->container = $container;
    }

    public function vistaSeguimiento(Request $request, Response $response) {
        ob_start();
        include dirname(__DIR__, 2) . '/public/views/seguimiento.php';
        $html = ob_get_clean();
        $response->getBody()->write($html);
        return $response;
    }

    // API: Búsqueda para médicos (por Cuenta, Atención, DNI o Paciente)
    public function buscarOrdenesMedico(Request $request, Response $response) {
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
            $response->getBody()->write(json_encode(['status' => 'error', 'message' => 'Error SQL']));
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

    // API: Detalle clínico de solo lectura trayendo analitos asentados directamente desde LabResultadoDetalle
    public function detalleResultadosLectura(Request $request, Response $response) {
        $conn = $this->container->get('db');
        $params = $request->getQueryParams();
        $idMovimiento = intval($params['idMovimiento'] ?? 0);

        if ($idMovimiento <= 0) {
            $response->getBody()->write(json_encode(['error' => 'Movimiento inválido']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // 1. Cabecera clínica completa
        $sqlCab = "SELECT TOP 1
                        l.IdMovimiento,
                        ISNULL(a.IdAtencion, 0) AS IdAtencion,
                        ISNULL(lml.IdCuentaAtencion, 0) AS IdCuentaAtencion,
                        lml.Paciente AS NombrePaciente,
                        p.NroDocumento AS DNI,
                        p.NroHistoriaClinica AS HC,
                        ISNULL(DATEDIFF(YEAR, p.FechaNacimiento, GETDATE()), 0) AS Edad,
                        ISNULL(NULLIF(RTRIM(lml.NroCama), ''), '---') AS Cama,
                        ISNULL(NULLIF(RTRIM(lml.Diagnostico), ''), '---') AS Diagnostico,
                        UPPER(ISNULL(NULLIF(RTRIM(lml.OrdenaPrueba), ''), 'POR ASIGNAR')) AS Medico,
                        UPPER(ISNULL(s.Nombre, ISNULL(lml.ProcedenciaServicio, 'CONSULTA EXTERNA'))) AS Servicio,
                        UPPER(ISNULL(f.Descripcion, 'SIS')) AS Seguro
                   FROM dbo.LabMovimiento l
                   INNER JOIN dbo.LabMovimientoLaboratorio lml ON l.IdMovimiento = lml.IdMovimiento
                   LEFT JOIN dbo.Atenciones a ON CAST(a.IdCuentaAtencion AS VARCHAR(50)) = RTRIM(LTRIM(lml.IdCuentaAtencion))
                   LEFT JOIN dbo.Pacientes p ON a.IdPaciente = p.IdPaciente
                   LEFT JOIN dbo.FuentesFinanciamiento f ON a.IdFuenteFinanciamiento = f.idFuenteFinanciamiento
                   LEFT JOIN dbo.FactOrdenServicio fos ON lml.IdOrden = fos.IdOrden
                   LEFT JOIN dbo.Servicios s ON fos.IdServicioPaciente = s.IdServicio
                   WHERE l.IdMovimiento = ?";

        $stmtCab = sqlsrv_query($conn, $sqlCab, [$idMovimiento]);
        $cabecera = ($stmtCab !== false) ? sqlsrv_fetch_array($stmtCab, SQLSRV_FETCH_ASSOC) : null;

        // 2. Extraer los analitos agrupados por CPT evitando filas repetidas
        $sqlResultados = "SELECT 
                            lrd.IdResultadoDetalle,
                            lrd.IdMovimiento,
                            ISNULL(lrd.IdProductoCPT, 0) AS IdProductoCPT,
                            ISNULL(cat.Nombre, 'EXAMEN CLÍNICO') AS Examen_Catalogo,
                            lrd.NombrePrueba AS Analito,
                            ISNULL(lrd.ValorResultado, '---') AS Resultado,
                            ISNULL(NULLIF(lrd.ValorReferencial, 'N/A'), ISNULL(pr.ValorReferencial, 'N/A')) AS Referencial,
                            ISNULL(pr.Metodo, 'Automatizado') AS Metodo,
                            ISNULL(lrd.Observaciones, '') AS Observaciones
                          FROM dbo.LabResultadoDetalle lrd
                          LEFT JOIN dbo.FactCatalogoServicios cat 
                              ON lrd.IdProductoCPT = cat.IdProducto
                          LEFT JOIN dbo.LabPlantillaReferenciales pr 
                              ON pr.NombreAnalito COLLATE Modern_Spanish_CI_AS = lrd.NombrePrueba COLLATE Modern_Spanish_CI_AS 
                             AND pr.Estado = 1
                          WHERE lrd.IdMovimiento = ?
                          ORDER BY lrd.IdResultadoDetalle ASC";

        $stmtRes = sqlsrv_query($conn, $sqlResultados, [$idMovimiento]);
        
        // Mapeo organizado por CPT con filtro estricto anti-duplicados
        $mapaAnalitosPorCpt = [];
        $analitosVistos = [];

        if ($stmtRes !== false) {
            while ($rowA = sqlsrv_fetch_array($stmtRes, SQLSRV_FETCH_ASSOC)) {
                $cptItem = intval($rowA['IdProductoCPT']);
                $nombreAnalito = trim($rowA['Analito']);
                $claveUnica = $cptItem . '_' . strtoupper($nombreAnalito);

                // Si ya se agregó este analito en este CPT, descartar duplicado
                if (isset($analitosVistos[$claveUnica])) {
                    continue;
                }
                $analitosVistos[$claveUnica] = true;

                if (!isset($mapaAnalitosPorCpt[$cptItem])) {
                    $mapaAnalitosPorCpt[$cptItem] = [];
                }

                $mapaAnalitosPorCpt[$cptItem][] = [
                    'analito'     => $nombreAnalito,
                    'resultado'   => trim($rowA['Resultado']),
                    'referencial' => trim($rowA['Referencial']),
                    'metodo'      => trim($rowA['Metodo']),
                    'observacion' => trim($rowA['Observaciones'])
                ];
            }
        }

        // 3. Obtener exámenes de la orden (CPT) y asignar exclusivamente sus analitos
        $sqlExamenes = "SELECT 
                            cpt.idProductoCPT,
                            ISNULL(cat.Nombre, 'EXAMEN CLÍNICO') AS ExamenPrincipal
                        FROM dbo.LabMovimientoCPT cpt
                        LEFT JOIN dbo.FactCatalogoServicios cat ON cpt.idProductoCPT = cat.IdProducto
                        WHERE cpt.idMovimiento = ?
                        ORDER BY cpt.idProductoCPT ASC";

        $stmtEx = sqlsrv_query($conn, $sqlExamenes, [$idMovimiento]);
        $examenes = [];

        if ($stmtEx !== false) {
            while ($rowE = sqlsrv_fetch_array($stmtEx, SQLSRV_FETCH_ASSOC)) {
                $cptId = intval($rowE['idProductoCPT']);
                $nombreEx = trim($rowE['ExamenPrincipal']);

                // Extraer únicamente los analitos que corresponden a este CPT
                $analitosDelExamen = $mapaAnalitosPorCpt[$cptId] ?? [];
                
                // Determinar si tiene resultados asentados
                $tieneResultados = false;
                foreach ($analitosDelExamen as $a) {
                    if (!empty($a['resultado']) && $a['resultado'] !== '---') {
                        $tieneResultados = true;
                        break;
                    }
                }

                $examenes[] = [
                    'idProductoCPT'  => $cptId,
                    'examen'         => $nombreEx,
                    'analitos'       => $analitosDelExamen,
                    'tieneResultado' => $tieneResultados,
                    'responsable'    => 'TECNÓLOGO DE TURNO'
                ];
            }
        }

        $data = [
            'cabecera' => [
                'idMovimiento' => $idMovimiento,
                'idAtencion'   => $cabecera['IdAtencion'] ?? 0,
                'cuenta'       => $cabecera['IdCuentaAtencion'] ?? 0,
                'paciente'     => $cabecera['NombrePaciente'] ?? 'PACIENTE SIGH',
                'dni'          => $cabecera['DNI'] ?? '---',
                'hc'           => $cabecera['HC'] ?? '---',
                'edad'         => $cabecera['Edad'] ?? '---',
                'cama'         => $cabecera['Cama'] ?? '---',
                'diagnostico'  => $cabecera['Diagnostico'] ?? '---',
                'medico'       => $cabecera['Medico'] ?? 'POR ASIGNAR',
                'servicio'     => $cabecera['Servicio'] ?? 'CONSULTA EXTERNA',
                'seguro'       => $cabecera['Seguro'] ?? 'SIS'
            ],
            'examenes' => $examenes
        ];

        $response->getBody()->write(json_encode(['status' => 'success', 'data' => $data]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
}
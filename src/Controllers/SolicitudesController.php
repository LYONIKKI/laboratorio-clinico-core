<?php
// src/Controllers/SolicitudesController.php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class SolicitudesController {
    protected $container;

    public function __construct($container) {
        $this->container = $container;
    }

    // Renderizar la vista principal
    public function vistaSolicitudes(Request $request, Response $response) {
        ob_start();
        include dirname(__DIR__, 2) . '/public/views/solicitudes.php';
        $html = ob_get_clean();
        $response->getBody()->write($html);
        return $response;
    }

    // API: Listar solicitudes con IdAtencion, IdCuentaAtencion y porcentaje
    public function listarSolicitudes(Request $request, Response $response) {
        $conn = $this->container->get('db');
        $params = $request->getQueryParams();
        $rango = $params['rango'] ?? '3';

        $clausulaWhere = ($rango === '5') 
            ? "L.Fecha >= DATEADD(day, -5, GETDATE())" 
            : "L.Fecha >= DATEADD(day, -3, GETDATE())";

        $sql = "SELECT 
                    L.IdMovimiento AS Nro_Movimiento,
                    ISNULL(A.IdAtencion, ISNULL(LML.IdCuentaAtencion, 0)) AS Nro_Atencion,
                    ISNULL(LML.IdCuentaAtencion, 0) AS Nro_Cuenta,
                    L.Fecha,
                    (CONVERT(VARCHAR(5), L.Fecha, 103) + ' ' + CONVERT(VARCHAR(5), L.Fecha, 108)) AS Hora_Format,
                    LML.Paciente AS Nombre_Lab,
                    P.NroDocumento AS DNI,
                    P.NroHistoriaClinica AS HC,
                    UPPER(ISNULL(LML.EstadoEntrega, 'PENDIENTE')) AS Estado,
                    (SELECT COUNT(*) FROM dbo.LabMovimientoCPT WHERE idMovimiento = L.IdMovimiento) AS Cantidad_Examenes,
                    (SELECT COUNT(DISTINCT IdProductoCPT) 
                     FROM dbo.LabResultadoDetalle 
                     WHERE IdMovimiento = L.IdMovimiento 
                       AND ValorResultado IS NOT NULL 
                       AND RTRIM(LTRIM(ValorResultado)) <> '') AS Cantidad_Completados,
                    UPPER(ISNULL(S.Nombre, 'CONSULTA EXTERNA')) AS Servicio_Procedencia
                FROM dbo.LabMovimiento AS L
                INNER JOIN dbo.LabMovimientoLaboratorio AS LML ON L.IdMovimiento = LML.IdMovimiento
                INNER JOIN dbo.Atenciones A ON LML.IdCuentaAtencion = A.IdCuentaAtencion
                INNER JOIN dbo.Pacientes P ON A.IdPaciente = P.IdPaciente
                LEFT JOIN dbo.FactOrdenServicio FOS ON LML.IdOrden = FOS.IdOrden
                LEFT JOIN dbo.Servicios S ON FOS.IdServicioPaciente = S.IdServicio
                WHERE $clausulaWhere AND L.IdPuntoCarga = 2
                ORDER BY 
                    CASE WHEN UPPER(ISNULL(LML.EstadoEntrega, 'PENDIENTE')) = 'PENDIENTE' THEN 0 ELSE 1 END ASC,
                    L.Fecha desc";

        $stmt = sqlsrv_query($conn, $sql);
        $data = [];

        if ($stmt !== false) {
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
        }

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    // API: Detalle de analitos de la orden con seguro dinámico según IdCuentaAtencion
    public function detalleExamenesOrden(Request $request, Response $response) {
        $conn = $this->container->get('db');
        $params = $request->getQueryParams();
        $idMovimiento = intval($params['id'] ?? 0);

        if ($idMovimiento <= 0) {
            $response->getBody()->write(json_encode(['error' => 'ID inválido']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // 1. OBTENER CABECERA DEL PACIENTE CON FUENTE DE FINANCIAMIENTO REAL (SIGH)
        $sqlCab = "SELECT TOP 1
                        L.IdMovimiento,
                        L.Fecha AS FechaRecepcion,
                        ISNULL(NULLIF(RTRIM(LML.Paciente), ''), ISNULL(RTRIM(P.ApellidoPaterno + ' ' + P.ApellidoMaterno + ' ' + P.PrimerNombre), 'PACIENTE SIGH')) AS NombrePaciente,
                        ISNULL(LML.IdCuentaAtencion, 0) AS IdCuentaAtencion,
                        ISNULL(NULLIF(RTRIM(LML.NroCama), ''), '---') AS NroCama,
                        ISNULL(NULLIF(RTRIM(LML.Diagnostico), ''), '---') AS Diagnostico,
                        ISNULL(NULLIF(RTRIM(S.Nombre), ''), ISNULL(NULLIF(RTRIM(LML.ProcedenciaServicio), ''), 'CONSULTA EXTERNA')) AS ProcedenciaServicio,
                        UPPER(ISNULL(NULLIF(RTRIM(LML.OrdenaPrueba), ''), 'POR ASIGNAR')) AS MedicoSolicitante,
                        ISNULL(NULLIF(RTRIM(P.NroDocumento), ''), '---') AS DNI,
                        ISNULL(NULLIF(RTRIM(P.NroHistoriaClinica), ''), '---') AS HC,
                        ISNULL(DATEDIFF(YEAR, P.FechaNacimiento, GETDATE()), 0) AS Edad,
                        UPPER(ISNULL(LML.EstadoEntrega, 'PENDIENTE')) AS EstadoEntrega,
                        -- Seguro real desde FuentesFinanciamiento
                        UPPER(ISNULL(NULLIF(RTRIM(F.Descripcion), ''), 'PARTICULAR / PAGANTE')) AS TipoSeguro
                   FROM dbo.LabMovimiento L
                   INNER JOIN dbo.LabMovimientoLaboratorio LML ON L.IdMovimiento = LML.IdMovimiento
                   LEFT JOIN dbo.Atenciones A ON LML.IdCuentaAtencion = A.IdCuentaAtencion
                   LEFT JOIN dbo.Pacientes P ON A.IdPaciente = P.IdPaciente
                   LEFT JOIN dbo.FuentesFinanciamiento F ON A.IdFuenteFinanciamiento = F.idFuenteFinanciamiento
                   LEFT JOIN dbo.FactOrdenServicio FOS ON LML.IdOrden = FOS.IdOrden
                   LEFT JOIN dbo.Servicios S ON FOS.IdServicioPaciente = S.IdServicio
                   WHERE L.IdMovimiento = ?";

        $stmtCab = sqlsrv_query($conn, $sqlCab, [$idMovimiento]);
        $rowCab = ($stmtCab !== false) ? sqlsrv_fetch_array($stmtCab, SQLSRV_FETCH_ASSOC) : null;

        // 2. BUSCAR EL RESPONSABLE QUE GUARDÓ
        $sqlResp = "SELECT TOP 1 ISNULL(u.Nombres + ' ' + u.Apellidos, 'TECNÓLOGO DE TURNO') AS ResponsableDigitacion
                    FROM dbo.LabResultadoDetalle LRD
                    LEFT JOIN dbo.SegUsuarios u ON LRD.IdUsuario = u.IdUsuario
                    WHERE LRD.IdMovimiento = ?";
        $stmtResp = sqlsrv_query($conn, $sqlResp, [$idMovimiento]);
        $rowResp = ($stmtResp !== false) ? sqlsrv_fetch_array($stmtResp, SQLSRV_FETCH_ASSOC) : null;

        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        $usuarioSesion = $_SESSION['user_name'] ?? $_SESSION['usuario'] ?? 'TECNÓLOGO DE TURNO';

        $data = [
            'paciente'         => $rowCab ? trim($rowCab['NombrePaciente']) : 'PACIENTE SIGH',
            'dni'              => $rowCab ? trim($rowCab['DNI']) : '---',
            'hc'               => $rowCab ? trim($rowCab['HC']) : '---',
            'fecha'            => ($rowCab && $rowCab['FechaRecepcion'] instanceof \DateTime) ? $rowCab['FechaRecepcion']->format('d/m/Y H:i') : '---',
            'edad'             => ($rowCab && $rowCab['Edad'] > 0) ? $rowCab['Edad'] : '---',
            'cama'             => $rowCab ? trim($rowCab['NroCama']) : '---',
            'diagnostico'      => $rowCab ? trim($rowCab['Diagnostico']) : '---',
            'servicio'         => $rowCab ? trim($rowCab['ProcedenciaServicio']) : 'CONSULTA EXTERNA',
            'medico'           => $rowCab ? trim($rowCab['MedicoSolicitante']) : 'POR ASIGNAR',
            'seguro'           => $rowCab ? trim($rowCab['TipoSeguro']) : 'PARTICULAR / PAGANTE',
            'responsable'      => $rowResp ? trim($rowResp['ResponsableDigitacion']) : $usuarioSesion,
            'idCuentaAtencion' => $rowCab ? intval($rowCab['IdCuentaAtencion']) : 0,
            'examenes'         => []
        ];

        // 3. EXÁMENES (CPT)
        $sqlExamenes = "SELECT 
                            LCPT.idProductoCPT,
                            ISNULL(Cat.Nombre, 'EXAMEN CPT #' + CAST(LCPT.idProductoCPT AS VARCHAR(20))) AS Examen,
                            ISNULL(LCPT.cantidad, 1) AS Cantidad,
                            ISNULL(LCPT.observaciones, '') AS Observacion,
                            (SELECT COUNT(*) 
                             FROM dbo.LabResultadoDetalle LRD 
                             WHERE LRD.IdMovimiento = LCPT.idMovimiento 
                               AND LRD.IdProductoCPT = LCPT.idProductoCPT 
                               AND LRD.ValorResultado IS NOT NULL 
                               AND RTRIM(LTRIM(LRD.ValorResultado)) <> '') AS TieneResultado
                        FROM dbo.LabMovimientoCPT LCPT
                        LEFT JOIN dbo.FactCatalogoServicios Cat ON LCPT.idProductoCPT = Cat.IdProducto
                        WHERE LCPT.idMovimiento = ?";

        $stmtEx = sqlsrv_query($conn, $sqlExamenes, [$idMovimiento]);
        if ($stmtEx !== false) {
            while ($rowE = sqlsrv_fetch_array($stmtEx, SQLSRV_FETCH_ASSOC)) {
                $data['examenes'][] = [
                    'idProductoCPT' => intval($rowE['idProductoCPT']),
                    'Examen'        => trim($rowE['Examen']),
                    'Cantidad'      => intval($rowE['Cantidad']),
                    'Observacion'   => trim($rowE['Observacion']),
                    'completado'    => (intval($rowE['TieneResultado'] ?? 0) > 0)
                ];
            }
        }

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    // API: Cambiar estado general de la orden
    public function cambiarEstadoEntrega(Request $request, Response $response) {
        $conn = $this->container->get('db');
        $body = $request->getParsedBody();
        $idMovimiento = intval($body['id'] ?? 0);

        if ($idMovimiento <= 0) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Movimiento inválido']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $sql = "UPDATE dbo.LabMovimientoLaboratorio
                SET EstadoEntrega = CASE WHEN UPPER(ISNULL(EstadoEntrega, 'PENDIENTE')) = 'ENTREGADO' THEN 'PENDIENTE' ELSE 'ENTREGADO' END
                WHERE IdMovimiento = ?";

        $stmt = sqlsrv_query($conn, $sql, [$idMovimiento]);
        $success = ($stmt !== false && sqlsrv_rows_affected($stmt) > 0);

        $response->getBody()->write(json_encode(['success' => $success]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    // API: Guardar resultados digitados por analito en dbo.LabResultadoDetalle
    public function guardarResultadoManual(Request $request, Response $response) {
        $conn = $this->container->get('db');
        
        $body = $request->getParsedBody();
        if (empty($body)) {
            $input = file_get_contents('php://input');
            $body = json_decode($input, true) ?? [];
        }

        $idMovimiento     = intval($body['idMovimiento'] ?? 0);
        $idCuentaAtencion = intval($body['idCuentaAtencion'] ?? 0);
        $idProductoCpt    = intval($body['idProductoCpt'] ?? 0);
        $nombrePrueba     = trim($body['nombrePrueba'] ?? '');
        $valorResultado   = trim($body['resultado'] ?? '');
        $valorReferencial = trim($body['referencial'] ?? 'NO REACTIVO');
        $observaciones    = trim($body['observaciones'] ?? '');
        $idUsuario        = intval($_SESSION['user_id'] ?? 3135);

        if ($idMovimiento <= 0 || empty($nombrePrueba)) {
            $response->getBody()->write(json_encode([
                'success' => false, 
                'message' => 'Faltan parámetros mínimos: Movimiento o Nombre de Prueba.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        if ($idCuentaAtencion <= 0) {
            $sqlCuenta = "SELECT IdCuentaAtencion FROM dbo.LabMovimientoLaboratorio WHERE IdMovimiento = ?";
            $stmtC = sqlsrv_query($conn, $sqlCuenta, [$idMovimiento]);
            if ($stmtC && $rC = sqlsrv_fetch_array($stmtC, SQLSRV_FETCH_ASSOC)) {
                $idCuentaAtencion = intval($rC['IdCuentaAtencion'] ?? 0);
            }
        }

        if ($idProductoCpt <= 0) {
            $sqlCpt = "SELECT TOP 1 idProductoCPT FROM dbo.LabMovimientoCPT WHERE idMovimiento = ?";
            $stmtP = sqlsrv_query($conn, $sqlCpt, [$idMovimiento]);
            if ($stmtP && $rP = sqlsrv_fetch_array($stmtP, SQLSRV_FETCH_ASSOC)) {
                $idProductoCpt = intval($rP['idProductoCPT'] ?? 0);
            }
        }

        $sql = "MERGE dbo.LabResultadoDetalle AS Target
                USING (SELECT ? AS IdMovimiento, ? AS IdProductoCPT, ? AS NombrePrueba) AS Source
                ON (Target.IdMovimiento = Source.IdMovimiento 
                    AND Target.IdProductoCPT = Source.IdProductoCPT 
                    AND Target.NombrePrueba = Source.NombrePrueba)
                WHEN MATCHED THEN
                    UPDATE SET 
                        ValorResultado   = ?,
                        ValorReferencial = ?,
                        Observaciones    = ?,
                        FechaRegistro    = GETDATE(),
                        IdUsuario        = ?
                WHEN NOT MATCHED THEN
                    INSERT (IdMovimiento, IdCuentaAtencion, IdProductoCPT, NombrePrueba, ValorResultado, ValorReferencial, Observaciones, IdUsuario)
                    VALUES (Source.IdMovimiento, ?, Source.IdProductoCPT, Source.NombrePrueba, ?, ?, ?, ?);";

        $params = [
            $idMovimiento, $idProductoCpt, $nombrePrueba,
            $valorResultado, $valorReferencial, $observaciones, $idUsuario,
            $idCuentaAtencion, $valorResultado, $valorReferencial, $observaciones, $idUsuario
        ];

        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            $errors = sqlsrv_errors();
            $response->getBody()->write(json_encode([
                'success' => false, 
                'error' => $errors[0]['message'] ?? 'Error SQL Server'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }

        $response->getBody()->write(json_encode(['success' => true]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    // API: Obtener componentes de la plantilla según el examen (Blindado total)
    public function obtenerPlantillaExamen(Request $request, Response $response) {
        $conn = $this->container->get('db');
        $params = $request->getQueryParams();
        
        $idMovimiento = isset($params['idMovimiento']) ? intval($params['idMovimiento']) : 0;
        $examenParam  = isset($params['examen']) ? trim($params['examen']) : '';

        if ($idMovimiento <= 0) {
            $response->getBody()->write(json_encode(['error' => 'Falta el ID de movimiento']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $examenLimpio = str_ireplace(['á','é','í','ó','ú','Á','É','Í','Ó','Ú'], ['a','e','i','o','u','A','E','I','O','U'], $examenParam);
        
        $clausulaFiltro = "";
        $queryParams = [$idMovimiento];

        if ($examenLimpio !== '') {
            $clausulaFiltro = " AND (Cat.Nombre LIKE ? OR Cat.Nombre LIKE ?) ";
            $queryParams[] = '%' . $examenLimpio . '%';
            $queryParams[] = '%' . $examenParam . '%';
        }

        $sql = "SELECT DISTINCT
                    ISNULL(NULLIF(LML.IdOrden, 0), LML.IdMovimiento) AS idOrden,
                    LML.IdCuentaAtencion,
                    LCPT.idProductoCPT,
                    Cat.Nombre AS Examen_Principal,
                    ISNULL(LRD.NombrePrueba, Cat.Nombre) AS SubPrueba,
                    CASE 
                        WHEN LRD.ValorReferencial IS NOT NULL AND LRD.ValorReferencial <> 'N/A' AND RTRIM(LRD.ValorReferencial) <> '' 
                        THEN LRD.ValorReferencial
                        ELSE ISNULL(PR.ValorReferencial, 'Menor a 6.0 mg/L (Negativo)')
                    END AS ValorReferencial,
                    ISNULL(PR.Metodo, 'Automatizado') AS Metodo,
                    ISNULL(PR.TipoControl, 'INPUT') AS TipoControl,
                    ISNULL(PR.OpcionesSelect, '') AS OpcionesSelect,
                    LRD.ValorResultado AS Resultado_Actual,
                    LRD.Observaciones AS ObservacionesClinicas
                FROM dbo.LabMovimiento L
                INNER JOIN dbo.LabMovimientoLaboratorio LML ON L.IdMovimiento = LML.IdMovimiento
                INNER JOIN dbo.LabMovimientoCPT LCPT ON L.IdMovimiento = LCPT.idMovimiento
                INNER JOIN dbo.FactCatalogoServicios Cat ON LCPT.idProductoCPT = Cat.IdProducto
                LEFT JOIN dbo.LabResultadoDetalle LRD
                    ON LRD.IdMovimiento = L.IdMovimiento
                   AND LRD.IdProductoCPT = LCPT.idProductoCPT
                LEFT JOIN dbo.LabPlantillaReferenciales PR
                    ON (
                        PR.NombreAnalito COLLATE Modern_Spanish_CI_AI = ISNULL(LRD.NombrePrueba, Cat.Nombre) COLLATE Modern_Spanish_CI_AI
                        OR Cat.Nombre COLLATE Modern_Spanish_CI_AI LIKE '%' + PR.ExamenPatron COLLATE Modern_Spanish_CI_AI + '%'
                        OR (PR.ExamenPatron = 'PCR' AND (Cat.Nombre LIKE '%PROTEINA C%' OR Cat.Nombre LIKE '%PCR%'))
                    )
                   AND PR.Estado = 1
                WHERE L.IdMovimiento = ? 
                  $clausulaFiltro";

        $stmt = sqlsrv_query($conn, $sql, $queryParams);

        if ($stmt === false) {
            $errors = sqlsrv_errors();
            $errorDetalle = $errors ? ($errors[0]['message'] ?? 'Error SQL') : 'Falla desconocida';
            $response->getBody()->write(json_encode([
                'error' => 'Falla en consulta SQL Server',
                'detalles' => $errorDetalle
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }

        $plantilla = [];
        $vistos = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $subPrueba = $row['SubPrueba'] ? trim($row['SubPrueba']) : 'Examen General';
            // Deduplicación en memoria por nombre de subprueba
            $claveUnica = strtoupper($subPrueba);
            if (isset($vistos[$claveUnica])) {
                continue;
            }
            $vistos[$claveUnica] = true;

            $plantilla[] = [
                'idOrden'          => $row['idOrden'],
                'IdCuentaAtencion' => intval($row['IdCuentaAtencion']),
                'idProductoCPT'    => $row['idProductoCPT'],
                'Examen_Principal' => $row['Examen_Principal'] ? trim($row['Examen_Principal']) : 'EXAMEN',
                'SubPrueba'        => $subPrueba,
                'ValorReferencial' => $row['ValorReferencial'] ? trim($row['ValorReferencial']) : 'N/A',
                'Metodo'           => $row['Metodo'] ? trim($row['Metodo']) : 'Automatizado',
                'TipoControl'      => $row['TipoControl'] ? trim($row['TipoControl']) : 'INPUT',
                'OpcionesSelect'   => $row['OpcionesSelect'] ? trim($row['OpcionesSelect']) : '',
                'Resultado_Actual' => $row['Resultado_Actual'] !== null ? trim($row['Resultado_Actual']) : '',
                'Observaciones'    => $row['ObservacionesClinicas'] ? trim($row['ObservacionesClinicas']) : ''
            ];
        }

        $response->getBody()->write(json_encode($plantilla));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
       
}
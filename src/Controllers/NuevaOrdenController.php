<?php
// src/Controllers/NuevaOrdenController.php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class NuevaOrdenController {
    protected $container;

    public function __construct($container) {
        $this->container = $container;
    }

    // 1. Buscar paciente únicamente por número de cuenta (Blindado)
    public function buscarPorCuenta(Request $request, Response $response) {
        $conn = $this->container->get('db');
        $params = $request->getQueryParams();
        $cuentaRaw = trim($params['cuenta'] ?? '');

        if (!is_numeric($cuentaRaw) || intval($cuentaRaw) <= 0) {
            $response->getBody()->write(json_encode([
                'status'  => 'error', 
                'message' => 'Ingrese un número de cuenta numérico válido.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $idCuentaAtencion = intval($cuentaRaw);

        // Consulta exacta probada y validada en SSMS
        $sql = "SELECT TOP 1
                    a.IdCuentaAtencion,
                    a.IdAtencion,
                    p.IdPaciente,
                    ISNULL(RTRIM(p.NroHistoriaclinica), '---') AS Historia_Clinica,
                    ISNULL(RTRIM(p.NroDocumento), 'SIN DNI') AS DNI,
                    RTRIM(p.ApellidoPaterno + ' ' + p.ApellidoMaterno + ' ' + p.PrimerNombre) AS Paciente,
                    p.FechaNacimiento,
                    ISNULL(p.IdTipoSexo, 1) AS IdTipoSexo,
                    CASE 
                        WHEN p.IdTipoSexo = 1 THEN 'MASCULINO'
                        WHEN p.IdTipoSexo = 2 THEN 'FEMENINO'
                        ELSE 'NO ESPECIFICADO'
                    END AS Genero,
                    ISNULL(DATEDIFF(YEAR, p.FechaNacimiento, GETDATE()), 0) AS Edad,
                    UPPER(ISNULL(s.Nombre, 'CONSULTA EXTERNA')) AS Procedencia,
                    UPPER(ISNULL(f.Descripcion, 'PARTICULAR / PAGANTE')) AS Seguro,
                    UPPER(ISNULL(e.ApellidoPaterno + ' ' + e.ApellidoMaterno + ' ' + e.Nombres, 'POR ASIGNAR')) AS Medico_solicita,
                    ISNULL(lm.NroCama, '---') AS NroCama
                FROM dbo.Atenciones a
                INNER JOIN dbo.Pacientes p ON a.IdPaciente = p.IdPaciente
                LEFT JOIN dbo.FuentesFinanciamiento f ON a.IdFuenteFinanciamiento = f.idFuenteFinanciamiento
                LEFT JOIN dbo.Servicios s ON a.IdServicioIngreso = s.IdServicio
                LEFT JOIN dbo.LabMovimientoLaboratorio lm ON a.IdCuentaAtencion = lm.IdCuentaAtencion
                LEFT JOIN dbo.Medicos m ON a.IdMedicoIngreso = m.IdMedico
                LEFT JOIN dbo.Empleados e ON m.IdEmpleado = e.IdEmpleado
                WHERE a.IdCuentaAtencion = ?";

        $stmt = sqlsrv_query($conn, $sql, [$idCuentaAtencion]);

        if ($stmt === false) {
            $errors = sqlsrv_errors();
            $detalle = $errors ? ($errors[0]['message'] ?? 'Falla SQL') : 'Error desconocido';
            $response->getBody()->write(json_encode([
                'status'  => 'error', 
                'message' => 'Falla en consulta SQL: ' . $detalle
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }

        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

        if (!$row) {
            $response->getBody()->write(json_encode([
                'status'  => 'error', 
                'message' => "No se encontró ninguna atención médica para la cuenta #{$idCuentaAtencion}."
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        // Manejo seguro de FechaNacimiento
        $fechaNacFormat = '---';
        if (isset($row['FechaNacimiento'])) {
            if ($row['FechaNacimiento'] instanceof \DateTime) {
                $fechaNacFormat = $row['FechaNacimiento']->format('d/m/Y');
            } elseif (is_string($row['FechaNacimiento'])) {
                $fechaNacFormat = substr($row['FechaNacimiento'], 0, 10);
            }
        }

        $data = [
            'idCuentaAtencion' => intval($row['IdCuentaAtencion']),
            'idAtencion'       => intval($row['IdAtencion']),
            'idPaciente'       => intval($row['IdPaciente']),
            'dni'              => trim($row['DNI'] ?? 'SIN DNI'),
            'hc'               => trim($row['Historia_Clinica'] ?? '---'),
            'paciente'         => trim($row['Paciente'] ?? 'PACIENTE SIGH'),
            'idTipoSexo'       => intval($row['IdTipoSexo'] ?? 1),
            'genero'           => trim($row['Genero'] ?? 'NO ESPECIFICADO'),
            'edad'             => intval($row['Edad'] ?? 0),
            'fechaNacimiento'  => $fechaNacFormat,
            'servicio'         => trim($row['Procedencia'] ?? 'CONSULTA EXTERNA'),
            'seguro'           => trim($row['Seguro'] ?? 'SIS'),
            'medico'           => trim($row['Medico_solicita'] ?? 'POR ASIGNAR'),
            'cama'             => trim($row['NroCama'] ?? '---')
        ];

        $response->getBody()->write(json_encode(['status' => 'success', 'data' => $data]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    // 2. Transacción de creación atómica con FactOrdenServicio, LabMovimiento y LabMovimientoCPT
    public function registrarOrden(Request $request, Response $response) {
        $conn = $this->container->get('db');
        $body = $request->getParsedBody();
        if (empty($body)) {
            $input = file_get_contents('php://input');
            $body = json_decode($input, true) ?? [];
        }

        $idCuentaAtencion = intval($body['idCuentaAtencion'] ?? 0);
        $examenesCpt      = $body['examenes'] ?? []; // Array de IDs de FactCatalogoServicios
        $medico           = trim($body['medico'] ?? 'POR ASIGNAR');
        $cama             = trim($body['cama'] ?? '---');
        $diagnosticoTxt   = trim($body['diagnostico'] ?? '---');
        $servicioTxt      = trim($body['servicio'] ?? 'CONSULTA EXTERNA');
        $idUsuario        = intval($_SESSION['user_id'] ?? 3135);

        if ($idCuentaAtencion <= 0 || empty($examenesCpt)) {
            $response->getBody()->write(json_encode([
                'success' => false, 
                'message' => 'Debe asociar una Cuenta de Atención y al menos un examen de laboratorio.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // 1. Obtener datos clínicos y de financiamiento del paciente (SIGH)
        $sqlP = "SELECT TOP 1
                    p.IdPaciente,
                    RTRIM(p.ApellidoPaterno + ' ' + p.ApellidoMaterno + ' ' + p.PrimerNombre) AS Paciente,
                    ISNULL(p.IdTipoSexo, 1) AS IdTipoSexo,
                    p.FechaNacimiento,
                    ISNULL(a.IdServicioIngreso, 1) AS IdServicio,
                    a.IdFuenteFinanciamiento,
                    ISNULL(ff.IdTipoFinanciamiento, 2) AS IdTipoFinanciamiento
                 FROM dbo.Atenciones a
                 INNER JOIN dbo.Pacientes p ON a.IdPaciente = p.IdPaciente
                 LEFT JOIN dbo.FuentesFinanciamiento ff ON a.IdFuenteFinanciamiento = ff.idFuenteFinanciamiento
                 WHERE a.IdCuentaAtencion = ?";

        $stmtP = sqlsrv_query($conn, $sqlP, [$idCuentaAtencion]);
        if (!$stmtP || !($paciente = sqlsrv_fetch_array($stmtP, SQLSRV_FETCH_ASSOC))) {
            $response->getBody()->write(json_encode([
                'success' => false, 
                'message' => 'No se encontró la atención de la cuenta en SIGH.'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $idPaciente      = intval($paciente['IdPaciente']);
        $nombrePaciente  = substr($paciente['Paciente'], 0, 100);
        $idTipoSexo      = intval($paciente['IdTipoSexo']);
        $fechaNacimiento = $paciente['FechaNacimiento'];
        $idServicio      = intval($paciente['IdServicio']);
        $idFuenteFinanc  = !empty($paciente['IdFuenteFinanciamiento']) ? intval($paciente['IdFuenteFinanciamiento']) : null;
        $idTipoFinanc    = intval($paciente['IdTipoFinanciamiento']);

        sqlsrv_begin_transaction($conn);

        try {
            // A. Insertar en FactOrdenServicio (IdPuntoCarga = 2)
            $sqlFOS = "INSERT INTO dbo.FactOrdenServicio (
                            IdPuntoCarga, 
                            IdPaciente, 
                            IdCuentaAtencion, 
                            IdServicioPaciente, 
                            idTipoFinanciamiento, 
                            idFuenteFinanciamiento, 
                            IdUsuario,
                            IdEstadoFacturacion,
                            FechaCreacion,
                            FechaDespacho,
                            IdUsuarioDespacho,
                            FechaHoraRealizaCpt
                       ) VALUES (
                            2, ?, ?, ?, ?, ?, ?, 1, GETDATE(), GETDATE(), ?, GETDATE()
                       );
                       SELECT SCOPE_IDENTITY() AS NuevoIdOrden;";

            $paramsFOS = [
                $idPaciente,
                $idCuentaAtencion,
                $idServicio,
                $idTipoFinanc,
                $idFuenteFinanc,
                $idUsuario,
                $idUsuario
            ];

            $stmtFOS = sqlsrv_query($conn, $sqlFOS, $paramsFOS);
            if ($stmtFOS === false) {
                $err = sqlsrv_errors();
                throw new Exception('Error al registrar FactOrdenServicio: ' . ($err[0]['message'] ?? ''));
            }

            sqlsrv_next_result($stmtFOS);
            $rowFOS = sqlsrv_fetch_array($stmtFOS, SQLSRV_FETCH_ASSOC);
            $nuevoIdOrden = intval($rowFOS['NuevoIdOrden'] ?? 0);

            if ($nuevoIdOrden <= 0) {
                throw new Exception('No se pudo generar el IdOrden en FactOrdenServicio.');
            }

            // B. Insertar en LabMovimiento con MovTipo = 'B' (Patología Clínica es TipoPunto = 'B')
            $sqlMov = "INSERT INTO dbo.LabMovimiento (MovTipo, IdTipoConcepto, IdPuntoCarga, Fecha, IdUsuario, IdLabEstado)
                       VALUES ('B', 3, 2, GETDATE(), ?, 2);
                       SELECT SCOPE_IDENTITY() AS NuevoIdMov;";

            $stmtM = sqlsrv_query($conn, $sqlMov, [$idUsuario]);
            if ($stmtM === false) {
                $err = sqlsrv_errors();
                throw new Exception('Error al registrar LabMovimiento: ' . ($err[0]['message'] ?? ''));
            }

            sqlsrv_next_result($stmtM);
            $rowM = sqlsrv_fetch_array($stmtM, SQLSRV_FETCH_ASSOC);
            $nuevoIdMovimiento = intval($rowM['NuevoIdMov'] ?? 0);

            if ($nuevoIdMovimiento <= 0) {
                throw new Exception('No se pudo generar el IdMovimiento en LabMovimiento.');
            }

            // C. SP Oficial de SIS-GalenPlus pasando NULL en CorrelativoAnual (espera tipo INT)
            $sqlSP = "{CALL dbo.LabMovimientoLaboratorioAgregar(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)}";
            $paramsSP = [
                $nuevoIdMovimiento,
                $nuevoIdOrden,
                null, // CorrelativoAnual como NULL para evitar el error nvarchar a int
                $idCuentaAtencion,
                0,    // IdComprobantePago
                0,    // idPersonaTomaLab
                0,    // idPersonaRecoge
                0,    // idDiagnostico
                0,    // EsDiagnosticoDefinitivo
                substr($medico, 0, 50),
                $nombrePaciente,
                $idTipoSexo,
                $fechaNacimiento,
                $idUsuario
            ];

            $stmtSP = sqlsrv_query($conn, $sqlSP, $paramsSP);
            if ($stmtSP === false) {
                $err = sqlsrv_errors();
                throw new Exception('Error en SP Galen: ' . ($err[0]['message'] ?? ''));
            }

            // D. Asentar campos de presentación para la web
            $sqlUpdateExtra = "UPDATE dbo.LabMovimientoLaboratorio 
                               SET EstadoEntrega = 'PENDIENTE',
                                   NroCama = ?, 
                                   Diagnostico = ?, 
                                   ProcedenciaServicio = ?
                               WHERE IdMovimiento = ?";
            sqlsrv_query($conn, $sqlUpdateExtra, [$cama, $diagnosticoTxt, $servicioTxt, $nuevoIdMovimiento]);

            // E. Insertar Exámenes CPT exclusivamente en LabMovimientoCPT
            $sqlPrecio = "SELECT TOP 1 ISNULL(PrecioUnitario, 0.00) AS Precio 
                          FROM dbo.FactCatalogoServiciosHosp 
                          WHERE IdProducto = ? AND IdTipoFinanciamiento = ?";

            $sqlCPT = "INSERT INTO dbo.LabMovimientoCPT (idMovimiento, idProductoCPT, cantidad, precio, importe, observaciones) 
                       VALUES (?, ?, 1, ?, ?, NULL)";

            foreach ($examenesCpt as $idCpt) {
                $idCptInt = intval($idCpt);
                $stmtPre = sqlsrv_query($conn, $sqlPrecio, [$idCptInt, $idTipoFinanc]);
                $precioUnitario = 0.00;
                if ($stmtPre && $rowPre = sqlsrv_fetch_array($stmtPre, SQLSRV_FETCH_ASSOC)) {
                    $precioUnitario = floatval($rowPre['Precio']);
                }

                $stmtC = sqlsrv_query($conn, $sqlCPT, [$nuevoIdMovimiento, $idCptInt, $precioUnitario, $precioUnitario]);
                if ($stmtC === false) {
                    $errC = sqlsrv_errors();
                    throw new Exception("Error al asociar examen CPT #{$idCptInt}: " . ($errC[0]['message'] ?? ''));
                }
            }
            
            sqlsrv_commit($conn);

            $response->getBody()->write(json_encode([
                'success'      => true,
                'idMovimiento' => $nuevoIdMovimiento,
                'idOrden'      => $nuevoIdOrden,
                'message'      => "Orden #{$nuevoIdMovimiento} generada exitosamente."
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);

        } catch (Exception $e) {
            sqlsrv_rollback($conn);
            $response->getBody()->write(json_encode([
                'success' => false, 
                'error'   => $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    // 3. Eliminar orden médica en cascada
    public function eliminarOrden(Request $request, Response $response) {
        $conn = $this->container->get('db');
        $body = $request->getParsedBody() ?: json_decode(file_get_contents('php://input'), true);
        $idMovimiento = intval($body['idMovimiento'] ?? 0);

        if ($idMovimiento <= 0) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'ID de movimiento inválido']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Obtener IdOrden vinculado
        $sqlBuscar = "SELECT IdOrden FROM dbo.LabMovimientoLaboratorio WHERE IdMovimiento = ?";
        $stmtB = sqlsrv_query($conn, $sqlBuscar, [$idMovimiento]);
        $rowB = ($stmtB !== false) ? sqlsrv_fetch_array($stmtB, SQLSRV_FETCH_ASSOC) : null;
        $idOrden = !empty($rowB['IdOrden']) ? intval($rowB['IdOrden']) : null;

        sqlsrv_begin_transaction($conn);
        try {
            // 1. Eliminar resultados si existieran
            sqlsrv_query($conn, "DELETE FROM dbo.LabResultadoDetalle WHERE IdMovimiento = ?", [$idMovimiento]);

            // 2. Eliminar pruebas CPT
            sqlsrv_query($conn, "DELETE FROM dbo.LabMovimientoCPT WHERE idMovimiento = ?", [$idMovimiento]);

            // 3. Eliminar cabecera laboratorio
            sqlsrv_query($conn, "DELETE FROM dbo.LabMovimientoLaboratorio WHERE IdMovimiento = ?", [$idMovimiento]);

            // 4. Eliminar cabecera padre LabMovimiento
            sqlsrv_query($conn, "DELETE FROM dbo.LabMovimiento WHERE IdMovimiento = ?", [$idMovimiento]);

            // 5. Eliminar FactOrdenServicio si está vinculada
            if ($idOrden !== null && $idOrden > 0) {
                sqlsrv_query($conn, "DELETE FROM dbo.FactOrdenServicio WHERE IdOrden = ?", [$idOrden]);
            }

            sqlsrv_commit($conn);
            $response->getBody()->write(json_encode(['success' => true, 'message' => "Orden #{$idMovimiento} eliminada."]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);

        } catch (\Exception $e) {
            sqlsrv_rollback($conn);
            $response->getBody()->write(json_encode(['success' => false, 'error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
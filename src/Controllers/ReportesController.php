<?php
// src/Controllers/ReportesController.php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ReportesController {
    protected $container;

    public function __construct($container) {
        $this->container = $container;
    }

    private function formatearRangoFechasHoras($params) {
        // Garantizar tiempo y memoria suficiente para rangos mensuales
        set_time_limit(300);
        ini_set('memory_limit', '1024M');

        $rawFechaIni = trim($params['inicio'] ?? date('Y-m-d'));
        $rawHoraIni  = trim($params['hora_inicio'] ?? '00:00:00');
        $rawFechaFin = trim($params['fin'] ?? date('Y-m-d'));
        $rawHoraFin  = trim($params['hora_fin'] ?? '23:59:59');

        if (strlen($rawHoraIni) === 5) { $rawHoraIni .= ':00'; }
        if (strlen($rawHoraFin) === 5) { $rawHoraFin .= ':59'; }

        $pIni = explode('-', $rawFechaIni);
        $pFin = explode('-', $rawFechaFin);

        $ini = (count($pIni) === 3) 
            ? sprintf('%04d-%02d-%02d %s', intval($pIni[0]), intval($pIni[1]), intval($pIni[2]), $rawHoraIni) 
            : date('Y-m-d 00:00:00');

        $fin = (count($pFin) === 3) 
            ? sprintf('%04d-%02d-%02d %s', intval($pFin[0]), intval($pFin[1]), intval($pFin[2]), $rawHoraFin) 
            : date('Y-m-d 23:59:59');

        return [$ini, $fin];
    }

    // =========================================================================
    // 1. REPORTE: ÓRDENES POR PACIENTES (.XLSX)
    // =========================================================================
    public function reportePacientes(Request $request, Response $response) {
        $conn = $this->container->get('db');
        $params = $request->getQueryParams();
        list($fechaInicio, $fechaFin) = $this->formatearRangoFechasHoras($params);

        $sql = "WITH MovimientosFiltrados AS (
                    SELECT L.IdMovimiento, L.Fecha
                    FROM dbo.LabMovimiento L WITH (NOLOCK)
                    WHERE L.Fecha >= CONVERT(DATETIME, '$fechaInicio', 120) 
                      AND L.Fecha <= CONVERT(DATETIME, '$fechaFin', 120)
                      AND L.IdPuntoCarga = 2
                ),
                ConteoExamenes AS (
                    SELECT idMovimiento, COUNT(*) AS TotalExamenes
                    FROM dbo.LabMovimientoCPT WITH (NOLOCK)
                    WHERE idMovimiento IN (SELECT IdMovimiento FROM MovimientosFiltrados)
                    GROUP BY idMovimiento
                ),
                ConteoCompletados AS (
                    SELECT IdMovimiento, COUNT(DISTINCT idProductoCPT) AS TotalValidados
                    FROM dbo.LabResultadoDetalle WITH (NOLOCK)
                    WHERE IdMovimiento IN (SELECT IdMovimiento FROM MovimientosFiltrados)
                      AND ValorResultado IS NOT NULL 
                      AND RTRIM(LTRIM(ValorResultado)) <> '' 
                      AND RTRIM(LTRIM(ValorResultado)) <> '---'
                    GROUP BY IdMovimiento
                )
                SELECT 
                    MF.IdMovimiento AS Nro_Movimiento,
                    CAST(ISNULL(LML.IdCuentaAtencion, 0) AS VARCHAR(50)) AS Nro_Cuenta,
                    (CONVERT(VARCHAR(10), MF.Fecha, 103) + ' ' + CONVERT(VARCHAR(8), MF.Fecha, 108)) AS Fecha_Registro,
                    ISNULL(LML.Paciente, 'PACIENTE SIGH') AS Paciente,
                    UPPER(ISNULL(LML.ProcedenciaServicio, 'CONSULTA EXTERNA')) AS Servicio_Procedencia,
                    UPPER(ISNULL(LML.ordenaPrueba, 'POR ASIGNAR')) AS Medico_Solicita,
                    UPPER(ISNULL(LML.EstadoEntrega, 'PENDIENTE')) AS Estado,
                    ISNULL(CE.TotalExamenes, 0) AS Cantidad_Examenes,
                    ISNULL(CC.TotalValidados, 0) AS Cantidad_Completados
                FROM MovimientosFiltrados MF
                INNER JOIN dbo.LabMovimientoLaboratorio LML WITH (NOLOCK) ON MF.IdMovimiento = LML.IdMovimiento
                LEFT JOIN ConteoExamenes CE ON MF.IdMovimiento = CE.idMovimiento
                LEFT JOIN ConteoCompletados CC ON MF.IdMovimiento = CC.IdMovimiento
                ORDER BY MF.IdMovimiento DESC";

        $stmt = sqlsrv_query($conn, $sql);
        if ($stmt === false) {
            $err = sqlsrv_errors();
            $response->getBody()->write(json_encode(['status' => 'error', 'message' => $err[0]['message'] ?? 'Error SQL']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }

        $filas = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) { $filas[] = $row; }

        $columnas = [
            'Nro_Movimiento'       => 'N° Movimiento',
            'Nro_Cuenta'           => 'N° Cuenta',
            'Fecha_Registro'       => 'Fecha Registro',
            'Paciente'             => 'Paciente',
            'Servicio_Procedencia' => 'Servicio Procedencia',
            'Medico_Solicita'      => 'Médico Solicitante',
            'Estado'               => 'Estado',
            'Cantidad_Examenes'    => 'Exámenes Solicitados',
            'Cantidad_Completados' => 'Analitos Validados'
        ];

        return $this->generarXlsxPlano(
            $response,
            "Resumen_Ordenes_" . date('Ymd_His') . ".xlsx",
            "REPORTE GENERAL DE ÓRDENES CLÍNICAS",
            "Filtros: $fechaInicio al $fechaFin",
            $columnas,
            $filas
        );
    }

    // =========================================================================
    // 2. REPORTE: PROCEDIMIENTOS CPT (PLANTILLA SIS-GALENPLUS OPTIMIZADA)
    // =========================================================================
    public function reporteProcedimientosCPT(Request $request, Response $response) {
        $conn = $this->container->get('db');
        $params = $request->getQueryParams();
        list($fechaInicio, $fechaFin) = $this->formatearRangoFechasHoras($params);

        // Pre-filtrar las órdenes del rango para evitar escaneo completo de FactOrdenServicio
        $sql = "WITH MovsRango AS (
                    SELECT L.IdMovimiento, L.Fecha
                    FROM dbo.LabMovimiento L WITH (NOLOCK)
                    WHERE L.Fecha >= CONVERT(DATETIME, '$fechaInicio', 120) 
                      AND L.Fecha <= CONVERT(DATETIME, '$fechaFin', 120)
                      AND L.IdPuntoCarga = 2
                )
                SELECT 
                    M.IdMovimiento,
                    (CONVERT(VARCHAR(10), M.Fecha, 103) + ' ' + CONVERT(VARCHAR(5), M.Fecha, 108)) AS FechaHora,
                    ISNULL(NULLIF(RTRIM(P.NroHistoriaClinica), ''), ISNULL(NULLIF(RTRIM(LML.IdCuentaAtencion), ''), '---')) AS HC,
                    ISNULL(NULLIF(RTRIM(LML.Paciente), ''), RTRIM(P.ApellidoPaterno + ' ' + P.ApellidoMaterno + ' ' + P.PrimerNombre)) AS Paciente,
                    ISNULL(NULLIF(RTRIM(P.NroDocumento), ''), '---') AS DNI,
                    UPPER(ISNULL(NULLIF(RTRIM(F.Descripcion), ''), 'SIS')) AS FuenteFinanciamiento,
                    (Cat.Nombre + ' (' + RTRIM(Cat.Codigo) + ')') AS ProcedimientoCPT,
                    CAST(ISNULL(NULLIF(LCPT.importe, 0.00), ISNULL(Hosp.PrecioUnitario, 0.00)) AS DECIMAL(10,2)) AS ImporteCPT
                FROM MovsRango M
                INNER JOIN dbo.LabMovimientoLaboratorio LML WITH (NOLOCK) ON M.IdMovimiento = LML.IdMovimiento
                LEFT JOIN dbo.FactOrdenServicio FOS WITH (NOLOCK) ON LML.IdOrden = FOS.IdOrden
                LEFT JOIN dbo.Pacientes P WITH (NOLOCK) ON FOS.IdPaciente = P.IdPaciente
                LEFT JOIN dbo.FuentesFinanciamiento F WITH (NOLOCK) ON FOS.idFuenteFinanciamiento = F.idFuenteFinanciamiento
                INNER JOIN dbo.LabMovimientoCPT LCPT WITH (NOLOCK) ON M.IdMovimiento = LCPT.idMovimiento
                INNER JOIN dbo.FactCatalogoServicios Cat WITH (NOLOCK) ON LCPT.idProductoCPT = Cat.IdProducto
                LEFT JOIN dbo.FactCatalogoServiciosHosp Hosp WITH (NOLOCK) 
                    ON Hosp.IdProducto = LCPT.idProductoCPT 
                   AND Hosp.IdTipoFinanciamiento = ISNULL(FOS.idTipoFinanciamiento, 2)
                   AND Hosp.Activo = 1
                ORDER BY M.IdMovimiento DESC, Cat.Nombre ASC";

        $stmt = sqlsrv_query($conn, $sql);
        if ($stmt === false) {
            $err = sqlsrv_errors();
            $response->getBody()->write(json_encode(['status' => 'error', 'message' => $err[0]['message'] ?? 'Error SQL']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }

        $movimientos = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $idMov = $row['IdMovimiento'];
            if (!isset($movimientos[$idMov])) {
                $movimientos[$idMov] = [
                    'fecha'     => $row['FechaHora'],
                    'hc'        => $row['HC'],
                    'paciente'  => $row['Paciente'],
                    'dni'       => $row['DNI'],
                    'fuente'    => $row['FuenteFinanciamiento'],
                    'examenes'  => []
                ];
            }
            $movimientos[$idMov]['examenes'][] = [
                'cpt'     => $row['ProcedimientoCPT'],
                'importe' => floatval($row['ImporteCPT'])
            ];
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Productividad_CPT');
        $sheet->setShowGridLines(true);

        $fIniTxt = date('d/m/Y H:i:s', strtotime($fechaInicio));
        $fFinTxt = date('d/m/Y H:i:s', strtotime($fechaFin));

        // Título Principal
        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', 'PRODUCTIVIDAD EN LABORATORIO');
        $sheet->getStyle('A1')->getFont()->setName('Calibri')->setSize(13)->setBold(true);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(25);

        // Subtítulo con Filtros
        $sheet->mergeCells('A2:G2');
        $sheet->setCellValue('A2', "Filtros:   F.Mov: $fIniTxt al $fFinTxt (Sin Recálculo)");
        $sheet->getStyle('A2')->getFont()->setName('Calibri')->setSize(9)->setItalic(true);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(2)->setRowHeight(18);

        $sheet->getRowDimension(3)->setRowHeight(8);

        // Encabezados
        $headers = ['Fecha', 'Paciente', 'DNI', 'Fuente Financiamiento', 'Procedimiento CPT', 'Importe', 'Total'];
        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];

        for ($c = 0; $c < count($headers); $c++) {
            $celda = $cols[$c] . '4';
            $sheet->setCellValue($celda, $headers[$c]);
            $sheet->getStyle($celda)->getFont()->setName('Calibri')->setSize(9)->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle($celda)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1E3A8A');
            $sheet->getStyle($celda)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle($celda)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('0F172A');
        }
        $sheet->getRowDimension(4)->setRowHeight(24);

        // Llenado de filas
        $filaActual = 5;
        foreach ($movimientos as $m) {
            $totalMov = array_sum(array_column($m['examenes'], 'importe'));
            $numExamenes = count($m['examenes']);

            for ($i = 0; $i < $numExamenes; $i++) {
                $ex = $m['examenes'][$i];
                $esPrimero = ($i === 0);
                $esUltimo  = ($i === ($numExamenes - 1));

                $cptTxt = $ex['cpt'];
                if (strlen($cptTxt) > 75) {
                    $cptTxt = substr($cptTxt, 0, 72) . '...';
                }

                if ($esPrimero) {
                    $sheet->setCellValue('A' . $filaActual, $m['fecha']);
                    $sheet->setCellValue('B' . $filaActual, '(' . $m['hc'] . ') ' . $m['paciente']);
                    $sheet->setCellValueExplicit('C' . $filaActual, (string)$m['dni'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $sheet->setCellValue('D' . $filaActual, $m['fuente']);
                    
                    $sheet->getStyle('A' . $filaActual)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('C' . $filaActual)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle('D' . $filaActual)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                $sheet->setCellValue('E' . $filaActual, $cptTxt);
                $sheet->setCellValue('F' . $filaActual, $ex['importe']);
                $sheet->getStyle('F' . $filaActual)->getNumberFormat()->setFormatCode('#,##0.00');

                if ($esUltimo) {
                    $sheet->setCellValue('G' . $filaActual, $totalMov);
                    $sheet->getStyle('G' . $filaActual)->getNumberFormat()->setFormatCode('#,##0.00');
                    $sheet->getStyle('G' . $filaActual)->getFont()->setBold(true);
                }

                $sheet->getStyle("A$filaActual:G$filaActual")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('D1D5DB');
                $sheet->getStyle("A$filaActual:G$filaActual")->getFont()->setName('Calibri')->setSize(9);
                $sheet->getRowDimension($filaActual)->setRowHeight(18);

                $filaActual++;
            }
        }

        // Anchos de columnas balanceados
        $sheet->getColumnDimension('A')->setWidth(18);
        $sheet->getColumnDimension('B')->setWidth(38);
        $sheet->getColumnDimension('C')->setWidth(13);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(58);
        $sheet->getColumnDimension('F')->setWidth(12);
        $sheet->getColumnDimension('G')->setWidth(12);

        $nombreArchivo = "Productividad_GalenPlus_CPT_" . date('Ymd_His') . ".xlsx";

        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $excelContenido = ob_get_clean();

        $response->getBody()->write($excelContenido);
        return $response
            ->withHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $nombreArchivo . '"')
            ->withHeader('Cache-Control', 'max-age=0')
            ->withStatus(200);
    }

    // =========================================================================
    // 3. REPORTE: PRODUCTIVIDAD CON RESULTADOS (.XLSX)
    // =========================================================================
    public function reporteConResultados(Request $request, Response $response) {
        $conn = $this->container->get('db');
        $params = $request->getQueryParams();
        list($fechaInicio, $fechaFin) = $this->formatearRangoFechasHoras($params);

        $dbPg = $this->container->has('db_pg') ? $this->container->get('db_pg') : ($this->container->has('pdo_pg') ? $this->container->get('pdo_pg') : null);
        $mapaUsuarios = [8 => 'TECNICO LABORATORIO', 3135 => 'TECNOLOGO ASISTENCIAL SIGH'];

        if ($dbPg instanceof \PDO) {
            try {
                $q = $dbPg->query("SELECT id, nombre FROM usuarios_sigh");
                if ($q) {
                    while ($u = $q->fetch(\PDO::FETCH_ASSOC)) {
                        $mapaUsuarios[intval($u['id'])] = trim($u['nombre']);
                    }
                }
            } catch (\Exception $e) {}
        }

        $sql = "WITH MovsRango AS (
                    SELECT L.IdMovimiento, L.Fecha
                    FROM dbo.LabMovimiento L WITH (NOLOCK)
                    WHERE L.Fecha >= CONVERT(DATETIME, '$fechaInicio', 120) 
                      AND L.Fecha <= CONVERT(DATETIME, '$fechaFin', 120)
                      AND L.IdPuntoCarga = 2
                )
                SELECT 
                    M.IdMovimiento AS Nro_Movimiento,
                    CAST(ISNULL(LML.IdCuentaAtencion, 0) AS VARCHAR(50)) AS Nro_Cuenta,
                    (CONVERT(VARCHAR(10), M.Fecha, 103) + ' ' + CONVERT(VARCHAR(8), M.Fecha, 108)) AS Fecha_Registro,
                    ISNULL(LML.Paciente, 'PACIENTE SIGH') AS Paciente,
                    UPPER(ISNULL(LML.ProcedenciaServicio, 'CONSULTA EXTERNA')) AS Servicio_Procedencia,
                    UPPER(ISNULL(LML.ordenaPrueba, 'POR ASIGNAR')) AS Medico_Solicita,
                    LRD.NombrePrueba AS Analito_Prueba,
                    LRD.ValorResultado AS Resultado,
                    ISNULL(LRD.ValorReferencial, '---') AS Referencial,
                    ISNULL(LRD.Observaciones, '') AS Observaciones,
                    (CONVERT(VARCHAR(10), LRD.FechaRegistro, 103) + ' ' + CONVERT(VARCHAR(8), LRD.FechaRegistro, 108)) AS Fecha_Validacion,
                    LRD.IdUsuario AS IdUsuarioValida
                FROM MovsRango M
                INNER JOIN dbo.LabMovimientoLaboratorio LML WITH (NOLOCK) ON M.IdMovimiento = LML.IdMovimiento
                INNER JOIN dbo.LabResultadoDetalle LRD WITH (NOLOCK) ON M.IdMovimiento = LRD.IdMovimiento
                WHERE LRD.ValorResultado IS NOT NULL 
                  AND RTRIM(LTRIM(LRD.ValorResultado)) <> ''
                  AND RTRIM(LTRIM(LRD.ValorResultado)) <> '---'
                ORDER BY M.IdMovimiento DESC, LRD.NombrePrueba ASC";

        $stmt = sqlsrv_query($conn, $sql);
        if ($stmt === false) {
            $err = sqlsrv_errors();
            $response->getBody()->write(json_encode(['status' => 'error', 'message' => $err[0]['message'] ?? 'Error SQL']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }

        $filas = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $idUser = intval($row['IdUsuarioValida'] ?? 0);
            $row['Profesional_Valida'] = $mapaUsuarios[$idUser] ?? ('USUARIO #' . $idUser);
            unset($row['IdUsuarioValida']);
            $filas[] = $row;
        }

        $columnas = [
            'Nro_Movimiento'       => 'N° Mov',
            'Nro_Cuenta'           => 'Cuenta',
            'Fecha_Registro'       => 'Fecha Registro',
            'Paciente'             => 'Paciente',
            'Servicio_Procedencia' => 'Procedencia',
            'Medico_Solicita'      => 'Médico Solicitante',
            'Analito_Prueba'       => 'Analito / Prueba',
            'Resultado'            => 'Resultado',
            'Referencial'          => 'Valor Referencial',
            'Observaciones'        => 'Observaciones',
            'Fecha_Validacion'     => 'Fecha Validación',
            'Profesional_Valida'   => 'Profesional que Validó'
        ];

        return $this->generarXlsxPlano(
            $response,
            "Productividad_Resultados_" . date('Ymd_His') . ".xlsx",
            "REPORTE DE PRODUCTIVIDAD CON RESULTADOS VALIDADOS",
            "Filtros: $fechaInicio al $fechaFin",
            $columnas,
            $filas
        );
    }

    // =========================================================================
    // HELPER: EXCEL .XLSX PLANO
    // =========================================================================
    private function generarXlsxPlano(Response $response, string $nombreArchivo, string $titulo, string $subtitulo, array $columnas, array $filas) {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Reporte');
        $sheet->setShowGridLines(true);

        $keys = array_keys($columnas);
        $headers = array_values($columnas);
        $totalCols = count($headers);

        $colFinalLetra = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($totalCols);

        $sheet->mergeCells("A1:{$colFinalLetra}1");
        $sheet->setCellValue('A1', $titulo);
        $sheet->getStyle('A1')->getFont()->setName('Calibri')->setSize(13)->setBold(true);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells("A2:{$colFinalLetra}2");
        $sheet->setCellValue('A2', $subtitulo);
        $sheet->getStyle('A2')->getFont()->setName('Calibri')->setSize(9)->setItalic(true);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        for ($i = 0; $i < $totalCols; $i++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue("{$colLetter}4", $headers[$i]);
            $sheet->getStyle("{$colLetter}4")->getFont()->setName('Calibri')->setSize(10)->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle("{$colLetter}4")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0F766E');
            $sheet->getStyle("{$colLetter}4")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        }
        $sheet->getRowDimension(4)->setRowHeight(22);

        $filaNum = 5;
        foreach ($filas as $f) {
            for ($i = 0; $i < $totalCols; $i++) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
                $key = $keys[$i];
                $val = trim((string)($f[$key] ?? ''));
                $sheet->setCellValue("{$colLetter}{$filaNum}", $val);
            }
            $sheet->getStyle("A{$filaNum}:{$colFinalLetra}{$filaNum}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E2E8F0');
            $filaNum++;
        }

        for ($i = 1; $i <= $totalCols; $i++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $contenido = ob_get_clean();

        $response->getBody()->write($contenido);
        return $response
            ->withHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $nombreArchivo . '"')
            ->withHeader('Cache-Control', 'max-age=0')
            ->withStatus(200);
    }

    // =========================================================================
    // 4. REPORTE: CONSOLIDADO DE GASTOS Y PRODUCTIVIDAD CPT (MATRIZ POR SERVICIO)
    // =========================================================================
    public function reporteConsolidadoGastos(Request $request, Response $response) {
        $conn = $this->container->get('db');
        $params = $request->getQueryParams();
        list($fechaInicio, $fechaFin) = $this->formatearRangoFechasHoras($params);

        $sql = "SET DATEFORMAT ymd;
                WITH MovimientosBase AS (
                    SELECT 
                        fcs.Codigo AS CodigoCPT,
                        fcs.Nombre AS NombrePrueba,
                        cpt.cantidad,
                        cpt.importe,
                        CASE 
                            WHEN ISNULL(ff.IdTipoFinanciamiento, fos.idTipoFinanciamiento) = 1 
                                 OR UPPER(ISNULL(ff.Descripcion, '')) LIKE '%PARTICULAR%'
                                THEN 'PARTICULAR'
                            WHEN ISNULL(a.IdTipoServicio, s.IdTipoServicio) = 2 
                                THEN 'EMERGENCIA'
                            WHEN ISNULL(a.IdTipoServicio, s.IdTipoServicio) = 3 
                                THEN 'HOSPITALIZACION'
                            ELSE 'CONSULTORIO_EXTERNO'
                        END AS Ambito
                    FROM dbo.LabMovimiento AS L WITH (NOLOCK)
                    INNER JOIN dbo.LabMovimientoLaboratorio AS LML WITH (NOLOCK) 
                        ON L.IdMovimiento = LML.IdMovimiento
                    INNER JOIN dbo.LabMovimientoCPT AS cpt WITH (NOLOCK) 
                        ON L.IdMovimiento = cpt.idMovimiento
                    INNER JOIN dbo.FactCatalogoServicios AS fcs WITH (NOLOCK) 
                        ON cpt.idProductoCPT = fcs.IdProducto
                    LEFT JOIN dbo.Atenciones AS a WITH (NOLOCK) 
                        ON LML.IdCuentaAtencion = a.IdCuentaAtencion
                    LEFT JOIN dbo.FactOrdenServicio AS fos WITH (NOLOCK) 
                        ON LML.IdOrden = fos.IdOrden
                    LEFT JOIN dbo.Servicios AS s WITH (NOLOCK) 
                        ON s.IdServicio = COALESCE(fos.IdServicioPaciente, a.IdServicioIngreso)
                    LEFT JOIN dbo.FuentesFinanciamiento AS ff WITH (NOLOCK) 
                        ON a.IdFuenteFinanciamiento = ff.idFuenteFinanciamiento
                    WHERE L.Fecha >= CONVERT(DATETIME, '$fechaInicio', 120) 
                      AND L.Fecha <= CONVERT(DATETIME, '$fechaFin', 120)
                      AND L.IdPuntoCarga = 2
                      AND ISNULL(L.IdLabEstado, 0) != 9
                ),
                ResumenAgrupado AS (
                    SELECT 
                        CodigoCPT,
                        NombrePrueba,
                        ISNULL(SUM(CASE WHEN Ambito = 'PARTICULAR' THEN cantidad ELSE 0 END), 0) AS Cant_Particular,
                        ISNULL(SUM(CASE WHEN Ambito = 'PARTICULAR' THEN importe ELSE 0.00 END), 0.00) AS Monto_Particular,
                        ISNULL(SUM(CASE WHEN Ambito = 'CONSULTORIO_EXTERNO' THEN cantidad ELSE 0 END), 0) AS Cant_CE,
                        ISNULL(SUM(CASE WHEN Ambito = 'CONSULTORIO_EXTERNO' THEN importe ELSE 0.00 END), 0.00) AS Monto_CE,
                        ISNULL(SUM(CASE WHEN Ambito = 'EMERGENCIA' THEN cantidad ELSE 0 END), 0) AS Cant_Emergencia,
                        ISNULL(SUM(CASE WHEN Ambito = 'EMERGENCIA' THEN importe ELSE 0.00 END), 0.00) AS Monto_Emergencia,
                        ISNULL(SUM(CASE WHEN Ambito = 'HOSPITALIZACION' THEN cantidad ELSE 0 END), 0) AS Cant_Hosp,
                        ISNULL(SUM(CASE WHEN Ambito = 'HOSPITALIZACION' THEN importe ELSE 0.00 END), 0.00) AS Monto_Hosp,
                        SUM(cantidad) AS Cant_Total,
                        SUM(importe)  AS Monto_Total
                    FROM MovimientosBase
                    GROUP BY CodigoCPT, NombrePrueba
                )
                SELECT 
                    CAST(ROW_NUMBER() OVER(ORDER BY CodigoCPT ASC) AS VARCHAR(10)) AS Nro,
                    CodigoCPT,
                    NombrePrueba,
                    Cant_Particular,
                    Monto_Particular,
                    Cant_CE,
                    Monto_CE,
                    Cant_Emergencia,
                    Monto_Emergencia,
                    Cant_Hosp,
                    Monto_Hosp,
                    Cant_Total,
                    Monto_Total,
                    0 AS EsFilaTotal
                FROM ResumenAgrupado
                UNION ALL
                SELECT 
                    '' AS Nro,
                    '' AS CodigoCPT,
                    'TOTAL' AS NombrePrueba,
                    SUM(Cant_Particular),
                    SUM(Monto_Particular),
                    SUM(Cant_CE),
                    SUM(Monto_CE),
                    SUM(Cant_Emergencia),
                    SUM(Monto_Emergencia),
                    SUM(Cant_Hosp),
                    SUM(Monto_Hosp),
                    SUM(Cant_Total),
                    SUM(Monto_Total),
                    1 AS EsFilaTotal
                FROM ResumenAgrupado
                ORDER BY EsFilaTotal ASC, CodigoCPT ASC";

        $stmt = sqlsrv_query($conn, $sql);
        if ($stmt === false) {
            $err = sqlsrv_errors();
            $response->getBody()->write(json_encode(['status' => 'error', 'message' => $err[0]['message'] ?? 'Error SQL']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }

        $filas = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $filas[] = $row;
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Productividad');
        $sheet->setShowGridLines(true);

        $fIniTxt = date('d/m/Y H:i:s', strtotime($fechaInicio));
        $fFinTxt = date('d/m/Y H:i:s', strtotime($fechaFin));

        // Título Principal
        $sheet->mergeCells('B1:N1');
        $sheet->setCellValue('B1', 'PRODUCTIVIDAD DE LABORATORIO');
        $sheet->getStyle('B1')->getFont()->setName('Arial')->setSize(12)->setBold(true);
        $sheet->getStyle('B1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(24);

        // Subtítulo con Rango
        $sheet->mergeCells('B2:N2');
        $sheet->setCellValue('B2', "Fecha Inicio $fIniTxt  -  Fecha Fin: $fFinTxt");
        $sheet->getStyle('B2')->getFont()->setName('Arial')->setSize(9)->setBold(true);
        $sheet->getStyle('B2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(2)->setRowHeight(18);

        // Cabecera Nivel 1 (Fila 4)
        $sheet->mergeCells('B4:B5');
        $sheet->setCellValue('B4', 'Nº');
        $sheet->mergeCells('C4:C5');
        $sheet->setCellValue('C4', 'CÓDIGO');
        $sheet->mergeCells('D4:D5');
        $sheet->setCellValue('D4', 'PRUEBA DE LABORATORIO');

        $sheet->mergeCells('E4:F4');
        $sheet->setCellValue('E4', 'PARTICULAR');
        $sheet->mergeCells('G4:H4');
        $sheet->setCellValue('G4', 'CONSULTORIO EXTERNO');
        $sheet->mergeCells('I4:J4');
        $sheet->setCellValue('I4', 'EMERGENCIA');
        $sheet->mergeCells('K4:L4');
        $sheet->setCellValue('K4', 'HOSPITALIZACIÓN');
        $sheet->mergeCells('M4:N4');
        $sheet->setCellValue('M4', 'TOTAL');

        // Cabecera Nivel 2 (Fila 5)
        $subHeaders = ['Cantidad', 'S/.', 'Cantidad', 'S/.', 'Cantidad', 'S/.', 'Cantidad', 'S/.', 'Cantidad', 'S/.'];
        $colsSub = ['E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N'];
        for ($s = 0; $s < count($subHeaders); $s++) {
            $sheet->setCellValue($colsSub[$s] . '5', $subHeaders[$s]);
        }

        // Estilos para cabeceras
        $sheet->getStyle('B4:N5')->getFont()->setName('Arial')->setSize(8)->setBold(true);
        $sheet->getStyle('B4:N5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('B4:N5')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('000000');
        $sheet->getRowDimension(4)->setRowHeight(18);
        $sheet->getRowDimension(5)->setRowHeight(18);

        // Llenado de Filas
        $filaActual = 6;
        foreach ($filas as $f) {
            $esTotal = (intval($f['EsFilaTotal']) === 1);

            $sheet->setCellValue('B' . $filaActual, $f['Nro']);
            $sheet->setCellValueExplicit('C' . $filaActual, (string)$f['CodigoCPT'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('D' . $filaActual, $f['NombrePrueba']);

            $sheet->setCellValue('E' . $filaActual, intval($f['Cant_Particular']));
            $sheet->setCellValue('F' . $filaActual, floatval($f['Monto_Particular']));

            $sheet->setCellValue('G' . $filaActual, intval($f['Cant_CE']));
            $sheet->setCellValue('H' . $filaActual, floatval($f['Monto_CE']));

            $sheet->setCellValue('I' . $filaActual, intval($f['Cant_Emergencia']));
            $sheet->setCellValue('J' . $filaActual, floatval($f['Monto_Emergencia']));

            $sheet->setCellValue('K' . $filaActual, intval($f['Cant_Hosp']));
            $sheet->setCellValue('L' . $filaActual, floatval($f['Monto_Hosp']));

            $sheet->setCellValue('M' . $filaActual, intval($f['Cant_Total']));
            $sheet->setCellValue('N' . $filaActual, floatval($f['Monto_Total']));

            // Formatos de celdas
            $sheet->getStyle('B' . $filaActual)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('C' . $filaActual)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('E' . $filaActual)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('G' . $filaActual)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('I' . $filaActual)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('K' . $filaActual)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('M' . $filaActual)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $sheet->getStyle('F' . $filaActual)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('H' . $filaActual)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('J' . $filaActual)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('L' . $filaActual)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('N' . $filaActual)->getNumberFormat()->setFormatCode('#,##0.00');

            $sheet->getStyle("B$filaActual:N$filaActual")->getFont()->setName('Arial')->setSize(8);
            $sheet->getStyle("B$filaActual:N$filaActual")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('D1D5DB');

            if ($esTotal) {
                $sheet->getStyle("B$filaActual:N$filaActual")->getFont()->setBold(true);
                $sheet->getStyle("B$filaActual:N$filaActual")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_DOUBLE)->getColor()->setRGB('000000');
                $sheet->getStyle("B$filaActual:N$filaActual")->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('000000');
            }

            $sheet->getRowDimension($filaActual)->setRowHeight(16);
            $filaActual++;
        }

        // Anchos de Columna
        $sheet->getColumnDimension('B')->setWidth(5);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(55);
        $sheet->getColumnDimension('E')->setWidth(10);
        $sheet->getColumnDimension('F')->setWidth(12);
        $sheet->getColumnDimension('G')->setWidth(10);
        $sheet->getColumnDimension('H')->setWidth(12);
        $sheet->getColumnDimension('I')->setWidth(10);
        $sheet->getColumnDimension('J')->setWidth(12);
        $sheet->getColumnDimension('K')->setWidth(10);
        $sheet->getColumnDimension('L')->setWidth(12);
        $sheet->getColumnDimension('M')->setWidth(11);
        $sheet->getColumnDimension('N')->setWidth(13);

        $nombreArchivo = "Consolidado_Gastos_Servicios_" . date('Ymd_His') . ".xlsx";

        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $excelContenido = ob_get_clean();

        $response->getBody()->write($excelContenido);
        return $response
            ->withHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $nombreArchivo . '"')
            ->withHeader('Cache-Control', 'max-age=0')
            ->withStatus(200);
    }

    // =========================================================================
    // 5. REPORTE: RESULTADOS HISTÓRICOS INTEGRADOS (WEB + SIS-GALENPLUS)
    // =========================================================================
    public function reporteResultadosSisgalen(Request $request, Response $response) {
        $conn = $this->container->get('db');
        $params = $request->getQueryParams();
        list($fechaInicio, $fechaFin) = $this->formatearRangoFechasHoras($params);

        $sql = "SET DATEFORMAT ymd;
                SELECT 
                    lm.IdMovimiento,
                    lml.IdOrden,
                    (CONVERT(VARCHAR(10), lm.Fecha, 103) + ' ' + CONVERT(VARCHAR(5), lm.Fecha, 108)) AS FechaHora,
                    CAST(ISNULL(lml.IdCuentaAtencion, 0) AS VARCHAR(50)) AS Cuenta,
                    ISNULL(lml.Paciente, 'PACIENTE SIGH') AS Paciente,
                    ISNULL(lml.NroCama, '---') AS Cama,
                    UPPER(ISNULL(s.Nombre, 'CONSULTA EXTERNA')) AS Procedencia,
                    fcs.Codigo AS CodigoCPT,
                    fcs.Nombre AS ExamenCPT,
                    CASE 
                        WHEN lrd.NombrePrueba IS NOT NULL 
                            THEN lrd.NombrePrueba COLLATE DATABASE_DEFAULT
                        ELSE fcs.Nombre COLLATE DATABASE_DEFAULT
                    END AS Analito,
                    CASE 
                        WHEN lrd.ValorResultado IS NOT NULL AND RTRIM(LTRIM(lrd.ValorResultado)) <> '' 
                            THEN RTRIM(LTRIM(lrd.ValorResultado)) COLLATE DATABASE_DEFAULT
                        WHEN r_item.ValorResultado IS NOT NULL AND RTRIM(LTRIM(r_item.ValorResultado)) <> ''
                            THEN RTRIM(LTRIM(r_item.ValorResultado)) COLLATE DATABASE_DEFAULT
                        WHEN r_item.ValorTexto IS NOT NULL AND RTRIM(LTRIM(r_item.ValorTexto)) <> ''
                            THEN RTRIM(LTRIM(r_item.ValorTexto)) COLLATE DATABASE_DEFAULT
                        WHEN lr.resultadoAnalisis IS NOT NULL 
                            THEN LTRIM(RTRIM(
                                    REPLACE(
                                        REPLACE(
                                            REPLACE(CAST(lr.resultadoAnalisis AS VARCHAR(500)), '\\\\', ' '),
                                            '//', ' '
                                        ), 
                                    '\\', ' ')
                                 )) COLLATE DATABASE_DEFAULT
                        ELSE 'PENDIENTE' COLLATE DATABASE_DEFAULT
                    END AS Resultado,
                    COALESCE(
                        lrd.ValorReferencial COLLATE DATABASE_DEFAULT, 
                        r_item.ValorReferencial COLLATE DATABASE_DEFAULT, 
                        'N/A' COLLATE DATABASE_DEFAULT
                    ) AS ValorReferencial,
                    CASE 
                        WHEN lrd.Observaciones IS NOT NULL AND RTRIM(LTRIM(lrd.Observaciones)) <> ''
                            THEN RTRIM(LTRIM(lrd.Observaciones)) COLLATE DATABASE_DEFAULT
                        WHEN r_item.ObservacionesClinicas IS NOT NULL AND RTRIM(LTRIM(CAST(r_item.ObservacionesClinicas AS VARCHAR(500)))) <> ''
                            THEN RTRIM(LTRIM(CAST(r_item.ObservacionesClinicas AS VARCHAR(500)))) COLLATE DATABASE_DEFAULT
                        WHEN lr.observacionResultado IS NOT NULL AND RTRIM(LTRIM(CAST(lr.observacionResultado AS VARCHAR(500)))) <> ''
                            THEN RTRIM(LTRIM(CAST(lr.observacionResultado AS VARCHAR(500)))) COLLATE DATABASE_DEFAULT
                        ELSE '---' COLLATE DATABASE_DEFAULT
                    END AS Observaciones,
                    CASE 
                        WHEN lrd.ValorResultado IS NOT NULL THEN 'WEB (VALIDADO)'
                        WHEN r_item.ValorResultado IS NOT NULL OR r_item.ValorTexto IS NOT NULL THEN 'SISGALEN (ITEMS/HEMOGRAMA)'
                        WHEN lr.resultadoAnalisis IS NOT NULL THEN 'SISGALEN (GENERAL)'
                        ELSE 'PENDIENTE'
                    END AS OrigenDato
                FROM dbo.LabMovimiento AS lm WITH (NOLOCK)
                INNER JOIN dbo.LabMovimientoLaboratorio AS lml WITH (NOLOCK) 
                    ON lm.IdMovimiento = lml.IdMovimiento
                INNER JOIN dbo.LabMovimientoCPT AS cpt WITH (NOLOCK) 
                    ON lm.IdMovimiento = cpt.idMovimiento
                INNER JOIN dbo.FactCatalogoServicios AS fcs WITH (NOLOCK) 
                    ON cpt.idProductoCPT = fcs.IdProducto
                LEFT JOIN dbo.Atenciones AS a WITH (NOLOCK) 
                    ON lml.IdCuentaAtencion = a.IdCuentaAtencion
                LEFT JOIN dbo.FactOrdenServicio AS fos WITH (NOLOCK) 
                    ON lml.IdOrden = fos.IdOrden
                LEFT JOIN dbo.Servicios AS s WITH (NOLOCK) 
                    ON s.IdServicio = COALESCE(fos.IdServicioPaciente, a.IdServicioIngreso)
                LEFT JOIN dbo.LabResultadoDetalle AS lrd WITH (NOLOCK) 
                    ON lm.IdMovimiento = lrd.IdMovimiento 
                   AND cpt.idProductoCPT = lrd.IdProductoCPT
                LEFT JOIN dbo.LabResultadoPorItems AS r_item WITH (NOLOCK) 
                    ON lml.IdOrden = r_item.idOrden 
                   AND cpt.idProductoCPT = r_item.idProductoCpt
                LEFT JOIN dbo.LabResultado AS lr WITH (NOLOCK) 
                    ON lml.IdOrden = lr.idOrden 
                   AND lr.idAnalisis COLLATE DATABASE_DEFAULT = fcs.Codigo COLLATE DATABASE_DEFAULT
                WHERE lm.Fecha >= CONVERT(DATETIME, '$fechaInicio', 120) 
                  AND lm.Fecha <= CONVERT(DATETIME, '$fechaFin', 120)
                  AND lm.IdPuntoCarga = 2
                  AND ISNULL(lm.IdLabEstado, 0) != 9
                ORDER BY lm.Fecha DESC, lml.IdOrden DESC";

        $stmt = sqlsrv_query($conn, $sql);
        if ($stmt === false) {
            $err = sqlsrv_errors();
            $response->getBody()->write(json_encode(['status' => 'error', 'message' => $err[0]['message'] ?? 'Error SQL']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }

        $filas = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $filas[] = $row;
        }

        $columnas = [
            'IdMovimiento'     => 'N° Mov',
            'IdOrden'          => 'N° Orden',
            'FechaHora'        => 'Fecha / Hora',
            'Cuenta'           => 'Cuenta',
            'Paciente'         => 'Paciente',
            'Cama'             => 'Cama',
            'Procedencia'      => 'Servicio Procedencia',
            'CodigoCPT'        => 'Código CPT',
            'ExamenCPT'        => 'Procedimiento General',
            'Analito'          => 'Analito / Subprueba',
            'Resultado'        => 'Resultado Obtenido',
            'ValorReferencial' => 'Valores de Referencia',
            'Observaciones'    => 'Observaciones',
            'OrigenDato'       => 'Origen del Dato'
        ];

        return $this->generarXlsxPlano(
            $response,
            "Resultados_SisGalen_Historico_" . date('Ymd_His') . ".xlsx",
            "REPORTE HISTÓRICO DE RESULTADOS (WEB Y SIS-GALENPLUS)",
            "Filtros: $fechaInicio al $fechaFin",
            $columnas,
            $filas
        );
    }

}
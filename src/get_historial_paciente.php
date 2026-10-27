<?php
require_once __DIR__ . '/db_sqlserver.php';

$dni = $_GET['dni'] ?? '';

if (empty($dni)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'DNI requerido']);
    exit;
}

// NUEVA CONSULTA ULTRA OPTIMIZADA (PROBADA EN SSMS)
$sql = "SELECT 
            CAST(L.Fecha AS DATE) as Fecha_Examen,
            L.Fecha AS Hora_Completa,
            Cat.Nombre AS Descripcion_Examen,
            L.IdMovimiento AS Nro_Movimiento,
            UPPER(ISNULL(S.Nombre, 'CONSULTA EXTERNA')) AS Servicio,
            LML.Paciente AS NombrePaciente,
            LML.FechaNacimiento,
            DATEDIFF(YEAR, LML.FechaNacimiento, GETDATE()) - 
                CASE 
                    WHEN MONTH(LML.FechaNacimiento) > MONTH(GETDATE()) OR 
                         (MONTH(LML.FechaNacimiento) = MONTH(GETDATE()) AND DAY(LML.FechaNacimiento) > DAY(GETDATE())) 
                    THEN 1 
                    ELSE 0 
                END AS EdadCalculada,
            P.NroHistoriaClinica AS HC
        FROM dbo.Pacientes P
        INNER JOIN dbo.LabMovimientoLaboratorio LML 
            ON LML.Paciente COLLATE DATABASE_DEFAULT LIKE RTRIM(P.NombreBusqueda) COLLATE DATABASE_DEFAULT + '%'
        INNER JOIN dbo.LabMovimiento L 
            ON L.IdMovimiento = LML.IdMovimiento
        INNER JOIN dbo.LabMovimientoCPT LCPT 
            ON L.IdMovimiento = LCPT.idMovimiento
        INNER JOIN dbo.FactCatalogoServicios Cat 
            ON LCPT.idProductoCPT = Cat.IdProducto
        LEFT JOIN dbo.FactOrdenServicio FOS 
            ON LML.IdOrden = FOS.IdOrden
        LEFT JOIN dbo.Servicios S 
            ON FOS.IdServicioPaciente = S.IdServicio
        WHERE P.NroDocumento = ?
        ORDER BY L.Fecha DESC, Cat.Nombre ASC";

$params = array($dni);
$stmt = sqlsrv_query($conn_sql, $sql, $params);

$response = [
    'paciente' => '---',
    'hc' => '---',
    'edad' => '---',
    'dni' => $dni,
    'historial' => []
];

if ($stmt !== false) {
    $primerRegistro = true;
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        if ($primerRegistro) {
            $response['paciente'] = $row['NombrePaciente'];
            $response['hc'] = $row['HC'] ?? '---';
            $response['edad'] = $row['EdadCalculada'] !== null ? $row['EdadCalculada'] . ' AÑOS' : '---';
            $primerRegistro = false;
        }

        $fechaFormateada = ($row['Hora_Completa'] instanceof DateTime) 
            ? $row['Hora_Completa']->format('d/m/Y H:i') 
            : $row['Fecha_Examen'];

        $response['historial'][] = [
            'movimiento' => $row['Nro_Movimiento'],
            'fecha' => $fechaFormateada,
            'examen' => $row['Descripcion_Examen'],
            'servicio' => $row['Servicio']
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($response);
exit;
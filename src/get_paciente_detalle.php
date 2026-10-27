<?php
require_once __DIR__ . '/db_sqlserver.php';

$busqueda = $_GET['q'] ?? '';

if (empty($busqueda)) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT 
            CAST(L.Fecha AS DATE) as Fecha_Examen,
            Cat.Nombre AS Descripcion_Examen,
            L.IdMovimiento AS Nro_Movimiento,
            UPPER(ISNULL(S.Nombre, 'CONSULTA EXTERNA')) AS Servicio
        FROM dbo.LabMovimiento AS L
        INNER JOIN dbo.LabMovimientoLaboratorio AS LML ON L.IdMovimiento = LML.IdMovimiento
        INNER JOIN dbo.LabMovimientoCPT AS LCPT ON L.IdMovimiento = LCPT.idMovimiento
        INNER JOIN dbo.FactCatalogoServicios AS Cat ON LCPT.idProductoCPT = Cat.IdProducto
        LEFT JOIN dbo.FactOrdenServicio FOS ON LML.IdOrden = FOS.IdOrden
        LEFT JOIN dbo.Servicios S ON FOS.IdServicioPaciente = S.IdServicio
        WHERE LML.Paciente LIKE ? 
        ORDER BY Fecha_Examen DESC, Descripcion_Examen ASC";

$params = array("%$busqueda%");
$stmt = sqlsrv_query($conn_sql, $sql, $params);

$examenesPorFecha = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $fecha = $row['Fecha_Examen']->format('d/m/Y');
    $examenesPorFecha[$fecha][] = $row;
}

header('Content-Type: application/json');
echo json_encode($examenesPorFecha);
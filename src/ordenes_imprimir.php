<?php
// =========================================================================
// 🧪 CONSUMIBLE DE DATA CLÍNICA UBICADO EN ZONA PÚBLICA (GA-MA ST)
// =========================================================================
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

// Ajustamos el require_once para subir un nivel y entrar a src de forma interna
require_once __DIR__ . '/../src/db_sqlserver.php'; 

$idMovimiento = isset($_GET['id']) ? trim($_GET['id']) : '';

if (empty($idMovimiento)) {
    echo json_encode(['error' => true, 'message' => 'Falta el ID de movimiento maestro.']);
    exit;
}

try {
    if (!isset($conn_sql) || !$conn_sql) {
        throw new Exception("Variable \$conn_sql es nula o no esta activa en el servidor local.");
    }

    // 🚀 TU QUERY EMBLEMA REFORMADO DE CONTRALORÍA CLÍNICA gggg
    // Acoplado 100% a la estructura de la tabla relacional por ítems previa
$sql = "SELECT 
            lm.IdMovimiento,
            lm.IdOrden,
            fac.IdProducto,
            fac.Nombre as Examen,
            ISNULL(lm.NroCama, '---') AS NroCama,
            ISNULL(lm.Diagnostico, '---') AS Diagnostico,
            a.IdCuentaAtencion,
            f.Descripcion AS Seguro, 
            UPPER(p.ApellidoPaterno + ' ' + p.ApellidoMaterno + ' ' + p.PrimerNombre) AS NombrePaciente,
            p.NroDocumento AS PacienteDni,
            p.NroHistoriaClinica AS PacienteHc,
            DATEDIFF(YEAR, p.FechaNacimiento, GETDATE()) AS PacienteEdad,
            UPPER(s.Nombre) AS Procedencia
        FROM LabMovimientoLaboratorio lm
        INNER JOIN dbo.Atenciones a ON lm.IdCuentaAtencion = a.IdCuentaAtencion
        INNER JOIN dbo.Pacientes p ON a.IdPaciente = p.IdPaciente
        INNER JOIN [dbo].[FuentesFinanciamiento] f ON a.IdFuenteFinanciamiento = f.idFuenteFinanciamiento
        INNER JOIN [dbo].[Servicios] s ON a.IdServicioIngreso = s.IdServicio
        -- Cambiado al verdadero búnker de la orden:
        INNER JOIN LabMovimientoCPT cpt ON lm.IdMovimiento = cpt.IdMovimiento
        INNER JOIN FactCatalogoServicios fac ON cpt.IdProductoCPT = fac.IdProducto
        WHERE lm.IdMovimiento = ?;";
    $stmt = sqlsrv_query($conn_sql, $sql, array($idMovimiento));

    if ($stmt === false) {
        $errors = sqlsrv_errors();
        $errorMsg = "";
        foreach($errors as $error) {
            $errorMsg .= $error['message'] . " // ";
        }
        throw new Exception($errorMsg);
    }

    $resultados = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $resultados[] = $row;
    }

    if (empty($resultados)) {
        echo json_encode([['error' => false, 'empty' => true, 'Examen' => 'EXAMEN DE LABORATORIO']]);
    } else {
        echo json_encode($resultados); 
    }

} catch (Exception $e) {
    echo json_encode([
        'error' => true,
        'message' => 'Error critico en base de datos SIGH',
        'details' => $e->getMessage()
    ]);
}
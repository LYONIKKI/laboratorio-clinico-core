<?php
// /var/www/html/laboratorio/src/add/get_paciente_cabecera.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db_sqlserver.php'; 

if (!isset($_GET['id_cuenta']) || empty($_GET['id_cuenta'])) {
    echo json_encode(['success' => false, 'message' => 'Falta el parámetro id_cuenta.']);
    exit;
}

$idCuentaAtencion = trim($_GET['id_cuenta']);

if (!isset($conn_sql) || !$conn_sql) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión interna.']);
    exit;
}

try {
    // MODIFICADO: Traemos la FechaNacimiento cruda para calcular la edad exacta en PHP
    $sql = "SELECT TOP 1
                a.IdCuentaAtencion,
                p.NroHistoriaclinica as Historia_Clinica,
                p.NroDocumento as DNI,
                UPPER(p.ApellidoPaterno) as Apellido_Paterno,
                UPPER(p.ApellidoMaterno) as Apellido_Materno,
                UPPER(p.PrimerNombre) as Primer_Nombre,
                UPPER(ISNULL(p.SegundoNombre, '')) as Segundo_Nombre,
                p.FechaNacimiento, 
                CONVERT(VARCHAR, p.FechaNacimiento, 103) as Fecha_de_Nacimiento,
                CASE 
                    WHEN p.IdTipoSexo = 1 THEN 'Masculino'
                    WHEN p.IdTipoSexo = 2 THEN 'Femenino'
                    ELSE 'No Especificado'
                END AS Genero,
                lm.IdMovimiento,
                UPPER(s.Nombre) AS Procedencia,
                f.Descripcion AS Seguro,
                UPPER(ISNULL(e.ApellidoPaterno + ' ' + e.ApellidoMaterno + ' ' + e.Nombres, ' ')) AS Medico_solicita,
                ISNULL(lm.NroCama, '---') AS NroCama,
                ISNULL(lm.Diagnostico, '---') AS Diagnostico
            FROM dbo.Atenciones a
            INNER JOIN dbo.Pacientes p ON a.IdPaciente = p.IdPaciente
            INNER JOIN [dbo].[FuentesFinanciamiento] f ON a.IdFuenteFinanciamiento = f.idFuenteFinanciamiento
            INNER JOIN [dbo].[Servicios] s ON a.IdServicioIngreso = s.IdServicio
            INNER JOIN [dbo].[TiposFinanciamiento] tf ON f.IdTipoFinanciamiento = tf.IdTipoFinanciamiento
            LEFT JOIN LabMovimientoLaboratorio lm ON a.IdCuentaAtencion = lm.IdCuentaAtencion
            LEFT JOIN [dbo].[Medicos] m ON a.IdMedicoIngreso = m.IdMedico
            LEFT JOIN [dbo].[Empleados] e ON m.IdEmpleado = e.IdEmpleado
            WHERE a.IdCuentaAtencion = ?";

    $params = array($idCuentaAtencion);
    $stmt = sqlsrv_query($conn_sql, $sql, $params);

    if ($stmt === false) {
        throw new Exception('Error ejecutando la consulta SQL.');
    }
    
    $data = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if ($data) {
        // --- LOGICA DE EDAD DETALLADA (Años, Meses o Días) ---
        $textoEdad = "0 días";
        if (!empty($data['FechaNacimiento'])) {
            $fechaNac = $data['FechaNacimiento']; // Es un objeto DateTime que retorna sqlsrv
            $fechaHoy = new DateTime();
            $diferencia = $fechaHoy->diff($fechaNac);

            if ($diferencia->y > 0) {
                $textoEdad = $diferencia->y . ($diferencia->y == 1 ? " año" : " años");
            } elseif ($diferencia->m > 0) {
                $textoEdad = $diferencia->m . ($diferencia->m == 1 ? " mes" : " meses");
            } else {
                $textoEdad = $diferencia->d . ($diferencia->d == 1 ? " día" : " días");
            }
        }

        // Inyectamos la edad formateada al array de salida
        $data['Edad_Texto'] = $textoEdad;

        // Limpiamos datos que ya no necesitamos mandar por JSON
        unset($data['FechaNacimiento']);

        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No se encontró la Cuenta de Atención.'
        ]);
    }

    sqlsrv_free_stmt($stmt);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage()
    ]);
}
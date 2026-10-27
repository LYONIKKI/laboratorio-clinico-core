<?php
// src/get_pruebas_atencion.php

$app->get('/api/pruebas-atencion/{idCuentaAtencion}', function ($request, $response, $args) use ($conn_sql) {
    require_once __DIR__ . '/db_sqlserver.php'; 
    
    // Obtenemos el ID de la cuenta de atención enviado desde el frontend
    $idCuentaAtencion = $args['idCuentaAtencion'];

    try {
        // Tu consulta corregida y optimizada apuntando al seguro real mediante el puente ff
        $sql = "SELECT 
                    a.IdCuentaAtencion,
                    lmc.idMovimiento,
                    fcs.Codigo AS Codigo_Producto,
                    fcs.Nombre AS Nombre_Producto,
                    lmc.Cantidad AS Cantidad,
                    fch.PrecioUnitario,
                    Subtotal = (lmc.Cantidad * fch.PrecioUnitario)
                FROM dbo.Atenciones a
                INNER JOIN [dbo].[FuentesFinanciamiento] ff ON a.IdFuenteFinanciamiento = ff.idFuenteFinanciamiento
                INNER JOIN LabMovimientoLaboratorio lm ON a.IdCuentaAtencion = lm.IdCuentaAtencion
                INNER JOIN LabMovimientoCPT lmc ON lm.IdMovimiento = lmc.idMovimiento
                INNER JOIN FactCatalogoServiciosHosp fch ON lmc.idProductoCPT = fch.IdProducto 
                                                        AND ff.IdTipoFinanciamiento = fch.IdTipoFinanciamiento
                INNER JOIN FactCatalogoServicios fcs ON fch.IdProducto = fcs.IdProducto
                WHERE a.IdCuentaAtencion = ?
                ORDER BY lmc.idMovimiento DESC";

        $params = array($idCuentaAtencion);
        $stmt = sqlsrv_query($conn_sql, $sql, $params);

        if ($stmt === false) {
            throw new Exception(print_r(sqlsrv_errors(), true));
        }

        $resultados = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            // Conversión manual de flotantes/strings si es necesario para el JSON
            $resultados[] = [
                'IdCuentaAtencion' => $row['IdCuentaAtencion'],
                'idMovimiento'     => $row['idMovimiento'],
                'Codigo_Producto'  => trim($row['Codigo_Producto']),
                'Nombre_Producto'  => trim($row['Nombre_Producto']),
                'Cantidad'         => (int)$row['Cantidad'],
                'PrecioUnitario'   => (float)$row['PrecioUnitario'],
                'Subtotal'         => (float)$row['Subtotal']
            ];
        }

        sqlsrv_free_stmt($stmt);

        $payload = json_encode(['success' => true, 'data' => $resultados]);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');

    } catch (Exception $e) {
        $payload = json_encode(['success' => false, 'message' => $e->getMessage()]);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});
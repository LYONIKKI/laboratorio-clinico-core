<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PanelController {
    protected $container;

    public function __construct($container) {
        $this->container = $container;
    }

    // RENDERIZAR VISTA (Exactamente igual que en InventarioController de banco_sangre)
    public function vistaPanel(Request $request, Response $response) {
        // Busca en public/views/ o en src/views/ según donde tengas la vista
        $rutaVista = dirname(__DIR__, 2) . '/public/views/panel.php';
        
        if (!file_exists($rutaVista)) {
            // Respaldo por si tu carpeta views aún sigue en src/
            $rutaVista = dirname(__DIR__) . '/views/panel.php';
        }

        if (!file_exists($rutaVista)) {
            $response->getBody()->write("Error Crítico: No se encontró el archivo físico de la vista en: " . $rutaVista);
            return $response->withStatus(500);
        }

        ob_start();
        include $rutaVista;
        $html = ob_get_clean();

        $response->getBody()->write($html);
        return $response;
    }

    // API: OBTENER MÉTRICAS DEL DASHBOARD
    public function obtenerMetricasGenerales(Request $request, Response $response) {
        // En banco_sangre tu conexión a SQL Server se llama 'db'
        // Verificamos si en tu container está como 'db' o 'db_sql'
        $conn = $this->container->has('db') ? $this->container->get('db') : $this->container->get('db_sql');

        $data = [
            'widgets' => ['solicitudes' => 0, 'procesados' => 0, 'espera' => 0],
            'top_examenes' => [],
            'servicios' => [],
            'acumulado_mensual' => []
        ];

        // 1. Widgets (Últimos 3 días)
        $sqlWidgets = "SELECT 
                            COUNT(DISTINCT L.IdMovimiento) AS Total_Solicitudes,
                            COUNT(DISTINCT CASE WHEN UPPER(ISNULL(LML.EstadoEntrega, 'PENDIENTE')) = 'ENTREGADO' THEN L.IdMovimiento END) AS Total_Procesados,
                            COUNT(DISTINCT CASE WHEN UPPER(ISNULL(LML.EstadoEntrega, 'PENDIENTE')) = 'PENDIENTE' THEN L.IdMovimiento END) AS Total_En_Espera
                        FROM dbo.LabMovimiento L
                        INNER JOIN dbo.LabMovimientoLaboratorio LML ON L.IdMovimiento = LML.IdMovimiento
                        WHERE L.Fecha >= DATEADD(day, -3, GETDATE())
                          AND L.IdPuntoCarga = 2";

        $stmtWidgets = sqlsrv_query($conn, $sqlWidgets);
        if ($stmtWidgets && $row = sqlsrv_fetch_array($stmtWidgets, SQLSRV_FETCH_ASSOC)) {
            $data['widgets']['solicitudes'] = (int)($row['Total_Solicitudes'] ?? 0);
            $data['widgets']['procesados']  = (int)($row['Total_Procesados'] ?? 0);
            $data['widgets']['espera']      = (int)($row['Total_En_Espera'] ?? 0);
        }

        // 2. Top 5 Exámenes
        $sqlTop = "SELECT TOP 5 Cat.Nombre AS Examen, COUNT(LCPT.idProductoCPT) AS Cantidad
                   FROM dbo.LabMovimiento L
                   INNER JOIN dbo.LabMovimientoCPT LCPT ON L.IdMovimiento = LCPT.idMovimiento
                   INNER JOIN dbo.FactCatalogoServicios Cat ON LCPT.idProductoCPT = Cat.IdProducto
                   WHERE L.Fecha >= DATEADD(day, -3, GETDATE())
                     AND L.IdPuntoCarga = 2
                   GROUP BY Cat.Nombre 
                   ORDER BY Cantidad DESC";
        $stmtTop = sqlsrv_query($conn, $sqlTop);
        while ($stmtTop && $row = sqlsrv_fetch_array($stmtTop, SQLSRV_FETCH_ASSOC)) {
            $data['top_examenes'][] = $row;
        }

        // 3. Demanda por Servicio
        $sqlServ = "SELECT UPPER(ISNULL(S.Nombre, 'CONSULTA EXTERNA / OTROS')) AS Servicio, COUNT(DISTINCT L.IdMovimiento) AS Cantidad
                    FROM dbo.LabMovimiento L
                    INNER JOIN dbo.LabMovimientoLaboratorio LML ON L.IdMovimiento = LML.IdMovimiento
                    LEFT JOIN dbo.FactOrdenServicio FOS ON LML.IdOrden = FOS.IdOrden
                    LEFT JOIN dbo.Servicios S ON FOS.IdServicioPaciente = S.IdServicio
                    WHERE L.Fecha >= DATEADD(day, -3, GETDATE())
                      AND L.IdPuntoCarga = 2
                    GROUP BY S.Nombre 
                    ORDER BY Cantidad DESC";
        $stmtServ = sqlsrv_query($conn, $sqlServ);
        while ($stmtServ && $row = sqlsrv_fetch_array($stmtServ, SQLSRV_FETCH_ASSOC)) {
            $data['servicios'][] = $row;
        }

        // 4. Tendencia Mensual
        $mesActual = date('m');
        $anioActual = date('Y');

        $sqlMes = "SELECT 
                       FORMAT(L.Fecha, 'dd/MM') AS dia, 
                       COUNT(DISTINCT L.IdMovimiento) AS total
                   FROM dbo.LabMovimiento L
                   WHERE MONTH(L.Fecha) = ? 
                     AND YEAR(L.Fecha) = ?
                   GROUP BY FORMAT(L.Fecha, 'dd/MM'), CAST(L.Fecha AS DATE)
                   ORDER BY CAST(L.Fecha AS DATE) ASC";

        $stmtMes = sqlsrv_query($conn, $sqlMes, [$mesActual, $anioActual]);
        if ($stmtMes !== false) {
            while ($row = sqlsrv_fetch_array($stmtMes, SQLSRV_FETCH_ASSOC)) {
                $data['acumulado_mensual'][] = [
                    'Mes' => trim($row['dia']),
                    'Cantidad' => (int)$row['total']
                ];
            }
        }

        $payload = json_encode($data);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
}
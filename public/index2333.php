<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use DI\ContainerBuilder;

require __DIR__ . '/../vendor/autoload.php';

// Controladores
require_once __DIR__ . '/../src/Controllers/PanelController.php';

// 1. Instanciar Contenedor de Inyección de Dependencias
$containerBuilder = new ContainerBuilder();
$container = $containerBuilder->build();

// 2. Registrar Bases de Datos
$setupDB = require __DIR__ . '/../src/Config/db.php';
$setupDB($container);

// 3. Registrar Controladores en el Contenedor
$container->set('PanelController', fn($c) => new PanelController($c));

// 4. Crear la aplicación Slim usando el Contenedor
$app = AppFactory::createFromContainer($container);
$app->setBasePath('/laboratorio/public');

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

// ================= RUTAS DEL PANEL =================
$app->get('/', function (Request $request, Response $response) {
    return $response->withHeader('Location', '/laboratorio/public/panel')->withStatus(302);
});

$app->get('/panel', 'PanelController:vistaPanel');
$app->get('/api/dashboard/metricas', 'PanelController:obtenerMetricasGenerales');


// --- RUTA: PACIENTES ---
$app->get('/pacientes', function (Request $request, Response $response) {
    $uri = '/pacientes';
    ob_start();
    include __DIR__ . '/../views/layout/header.php';
    include __DIR__ . '/../views/layout/sidebar.php';
    include __DIR__ . '/../views/pacientes.php';
    include __DIR__ . '/../views/layout/footer.php';
    $html = ob_get_clean();
    $response->getBody()->write($html);
    return $response;
});

/**
 * API para obtener atenciones (SQL Server)
 * Úsala en tu JS para llenar las tablas
 */
$app->get('/api/get-atenciones', function (Request $request, Response $response) {
    require __DIR__ . '/../src/db_sqlserver.php';
    
    $sql = "SELECT L.IdMovimiento AS Nro_Movimiento, LML.Paciente AS Nombre_Paciente, 
            Cat.Nombre AS Descripcion_Examen, UPPER(ISNULL(S.Nombre, 'CONSULTA EXTERNA')) AS Servicio_Procedencia,
            UPPER(ISNULL(LML.OrdenaPrueba, 'POR ASIGNAR')) AS Personal_Responsable
            FROM dbo.LabMovimiento AS L
            INNER JOIN dbo.LabMovimientoLaboratorio AS LML ON L.IdMovimiento = LML.IdMovimiento
            INNER JOIN dbo.LabMovimientoCPT AS LCPT ON L.IdMovimiento = LCPT.idMovimiento
            INNER JOIN dbo.FactCatalogoServicios AS Cat ON LCPT.idProductoCPT = Cat.IdProducto
            INNER JOIN dbo.FactPuntosCarga AS PC ON L.IdPuntoCarga = PC.IdPuntoCarga
            LEFT JOIN dbo.FactOrdenServicio FOS ON LML.IdOrden = FOS.IdOrden
            LEFT JOIN dbo.Servicios S ON FOS.IdServicioPaciente = S.IdServicio
            WHERE CAST(L.Fecha AS DATE) = CAST(GETDATE() AS DATE) 
            AND PC.IdPuntoCarga = 2
            ORDER BY L.Fecha DESC";

    $stmt = sqlsrv_query($conn_sql, $sql);
    $data = [];
    if ($stmt !== false) {
        while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $data[] = $row;
        }
    }

    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
});

// Redirección por defecto si entran a la raíz de la carpeta public
$app->get('/', function (Request $request, Response $response) {
    return $response->withHeader('Location', '/laboratorio/public/panel')->withStatus(302);
});

$app->get('/api/get-atenciones-hoy', function (Request $request, Response $response) {
    require __DIR__ . '/../src/db_sqlserver.php';
    
    // Consulta agrupada para evitar duplicados en la tabla principal
    $sql = "SELECT 
                MAX(L.Fecha) AS Hora, 
                L.IdMovimiento, 
                LML.Paciente, 
                UPPER(ISNULL(S.Nombre, 'CONSULTA EXTERNA')) AS Servicio,
                UPPER(ISNULL(LML.OrdenaPrueba, 'POR ASIGNAR')) AS Medico
            FROM dbo.LabMovimiento AS L
            INNER JOIN dbo.LabMovimientoLaboratorio AS LML ON L.IdMovimiento = LML.IdMovimiento
            LEFT JOIN dbo.FactOrdenServicio FOS ON LML.IdOrden = FOS.IdOrden
            LEFT JOIN dbo.Servicios S ON FOS.IdServicioPaciente = S.IdServicio
            WHERE CAST(L.Fecha AS DATE) = CAST(GETDATE() AS DATE)
            AND L.IdPuntoCarga = 2
            GROUP BY L.IdMovimiento, LML.Paciente, S.Nombre, LML.OrdenaPrueba
            ORDER BY Hora DESC";

    $stmt = sqlsrv_query($conn_sql, $sql);
    $data = [];
    while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        // Formatear la hora
        $row['Hora_Format'] = $row['Hora']->format('H:i');
        $data[] = $row;
    }

    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
});
$app->get('/api/get-paciente-detalle', function (Request $request, Response $response) {
    // Definimos la ruta al archivo físico en src
    $archivoPath = __DIR__ . '/../src/get_paciente_detalle.php';
    
    if (file_exists($archivoPath)) {
        // Al usar require, el archivo get_paciente_detalle.php se ejecuta.
        // Como ese archivo ya tiene el 'echo json_encode', la data saldrá directamente.
        require $archivoPath;
    } else {
        $response->getBody()->write(json_encode(['error' => 'Archivo de servicio no encontrado']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }

    return $response->withHeader('Content-Type', 'application/json');
});
// ===================================================================================
// /=====================//////SOLICITIDES==============
// ================================================================================
$app->get('/solicitudes', function ($request, $response) {
    $uri = '/solicitudes'; // Esto es para que el Sidebar sepa qué botón poner en 'active'
    
    ob_start();
    include __DIR__ . '/../views/layout/header.php';
    include __DIR__ . '/../views/layout/sidebar.php';
    include __DIR__ . '/../views/solicitudes.php'; // El archivo que acabas de crear
    include __DIR__ . '/../views/layout/footer.php';
    $html = ob_get_clean();
    
    $response->getBody()->write($html);
    return $response;
});

// --- API PARA OBTENER LAS SOLICITUDES (JSON) ---
$app->get('/api/get-solicitudes-hoy', function ($request, $response) {
    // Aquí llamaremos al archivo de lógica que haremos a continuación
    require __DIR__ . '/../src/get_solicitudes_hoy.php';
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/api/get-examenes-solicitud', function ($request, $response) {
    require __DIR__ . '/../src/get_examenes_solicitud.php';
    return $response->withHeader('Content-Type', 'application/json');
});
// ============================CAMBIAR ESTADO================
$app->post('/cambiar-estado-entrega', function ($request, $response) {
    require __DIR__ . '/../src/cambiar_estado_entrega.php';
    return $response;
});

// =================================FUNCIONES PARA LA PARTE DE EDITAR RESULTADOS=======================
// En tu public/index.php de Slim gggg
$app->get('/api/get-plantilla-examen', function ($request, $response) {
    // Así tu script de src/get_plantilla_examen.php podrá capturar el $_GET['idProductoCPT']
    require_once __DIR__ . '/../src/get_plantilla_examen.php';
    return $response;
});

// 2. Ruta para procesar el guardado o actualización en la tabla RPI
$app->post('/api/guardar-resultado-manual', function ($request, $response) {
    require_once __DIR__ . '/../src/guardar_resultado_manual.php';
    // El script de arriba corta con exit escupiendo su JSON, pero dejamos esto por seguridad
    return $response; 
});
// ENDPOINT DE SEGURIDAD RELACIONAL EN TU INDEX gggg


// =====================================================
// validar  resultados
$app->post('/api/validar-entrega', function ($request, $response) {
    $params = $request->getParsedBody();
    $idOrden = $params['idOrden'];

    // 🔗 CONEXIÓN AL BÚNKER DE SQL SERVER
    // Suponiendo que tu tabla se llama 'lab_orden_detalle' o similar
    $sql = "UPDATE lab_orden_detalle 
            SET estado_entrega = 'VALIDADO', 
                fecha_entrega = GETDATE() 
            WHERE id_orden = :idOrden";

    try {
        $db = $this->db; // Tu instancia de base de datos
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':idOrden', $idOrden);
        $stmt->execute();

        return $response->withJson([
            'status' => 'success',
            'message' => 'Examen validado en el búnker'
        ]);
    } catch (Exception $e) {
        return $response->withJson(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
});
// ==================================================================================
// ===============================================================================
// ==========================================================================

// --- RUTA PARA MOSTRAR LA VISTA DE SEGUIMIENTO DE MUESTRAS ---
$app->get('/muestras', function ($request, $response) {
    $uri = '/muestras';

    ob_start();
    include __DIR__ . '/../views/layout/header.php';
    include __DIR__ . '/../views/layout/sidebar.php';
    include __DIR__ . '/../views/seguimiento.php'; 
    include __DIR__ . '/../views/layout/footer.php';
    $html = ob_get_clean();

    $response->getBody()->write($html);
    return $response;
});

// --- API QUE CONSUMIRÁ EL JS DE MUESTRAS ---
$app->get('/api/get-historial-paciente', function ($request, $response) {
    require __DIR__ . '/../src/get_historial_paciente.php';
    return $response->withHeader('Content-Type', 'application/json');
});

// ==========================================================================
// ========================================================================
// ==========================================================

// --- RUTA PARA MOSTRAR LA VISTA DE RESULTADOS DE LABORATORIO ---
$app->get('/resultados', function ($request, $response) {
    $uri = '/resultados';

    ob_start();
    include __DIR__ . '/../views/layout/header.php';
    include __DIR__ . '/../views/layout/sidebar.php';
    include __DIR__ . '/../views/resultados.php'; // <-- Apunta directo a views/resultados.php
    include __DIR__ . '/../views/layout/footer.php';
    $html = ob_get_clean();

    $response->getBody()->write($html);
    return $response;
});

// --- API QUE CONSUMIRÁ EL MODAL DE RESULTADOS ---
$app->get('/api/get-resultados-orden', function($request, $response) {
    require __DIR__ . '/../src/get_resultados_orden.php';
    return $response->withHeader('Content-Type', 'application/json');
});

// --- RUTA PARA EL MÓDULO DE INVENTARIO DE INSUMOS ---
$app->get('/insumos', function ($request, $response) {
    $uri = '/insumos'; // Sincroniza el menú amarillo con tu sidebar.php gggg
    
    ob_start();
    include __DIR__ . '/../views/layout/header.php';
    include __DIR__ . '/../views/layout/sidebar.php';
    include __DIR__ . '/../views/insumos.php'; // Tu nuevo archivo activo
    include __DIR__ . '/../views/layout/footer.php';
    $html = ob_get_clean();
    
    $response->getBody()->write($html);
    return $response;
});

// --- API CRUD: LISTAR INSUMOS DESDE POSTGRES ---
$app->get('/api/insumos/listar', function ($request, $response) {
    require_once __DIR__ . '/../src/db_postgres.php';
    
    // Consulta limpia con cálculo de días de alerta en caliente gggg
    $sql = "SELECT *, 
                   (fecha_vencimiento - CURRENT_DATE) AS dias_para_vencer
            FROM public.insumos_laboratorio 
            ORDER BY fecha_vencimiento ASC";
            
    $stmt = $conn_pg->query($sql);
    $data = $stmt->fetchAll();
    
    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
});
// --- API CRUD: REGISTRAR NUEVO INSUMO EN POSTGRES ---
$app->post('/api/insumos/guardar', function ($request, $response) {
    require_once __DIR__ . '/../src/db_postgres.php';
    
    // Capturamos los datos enviados desde el JavaScript
    $body = $request->getParsedBody();
    
    $codigo       = strtoupper(trim($body['codigo'] ?? ''));
    $lote         = strtoupper(trim($body['lote'] ?? ''));
    $nombre       = strtoupper(trim($body['nombre'] ?? ''));
    $presentacion = strtoupper(trim($body['presentacion'] ?? ''));
    $stock        = intval($body['stock'] ?? 0);
    $minimo       = intval($body['minimo'] ?? 10);
    $vencimiento  = $body['vencimiento'] ?? '';

    // Validación rápida de campos obligatorios
    if (empty($codigo) || empty($lote) || empty($nombre) || empty($vencimiento)) {
        $response->getBody()->write(json_encode(['error' => 'Todos los campos son obligatorios.']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    try {
        $sql = "INSERT INTO public.insumos_laboratorio 
                    (codigo_interno, nombre_insumo, lote_codigo, presentacion, stock_actual, stock_minimo, fecha_vencimiento, estado) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'ACTIVO')";
                
        $stmt = $conn_pg->prepare($sql);
        $stmt->execute([$codigo, $nombre, $lote, $presentacion, $stock, $minimo, $vencimiento]);

        $response->getBody()->write(json_encode(['success' => true, 'message' => 'Insumo registrado correctamente en PostgreSQL']));
        return $response->withHeader('Content-Type', 'application/json');
        
    } catch (\PDOException $e) {
        // Capturamos errores de llave duplicada (por ejemplo, si el código interno ya existe)
        $msg = $e->getCode() == '23505' ? 'El código interno ya se encuentra registrado.' : $e->getMessage();
        
        $response->getBody()->write(json_encode(['error' => $msg]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

$app->post('/api/insumos/actualizar', function ($request, $response) {
    require_once __DIR__ . '/../src/db_postgres.php';
    
    // Al ser POST tradicional, Slim lee los parámetros de inmediato gggg
    $body = $request->getParsedBody();
    
    $id           = intval($body['id'] ?? 0);
    $codigo       = strtoupper(trim($body['codigo'] ?? ''));
    $lote         = strtoupper(trim($body['lote'] ?? ''));
    $nombre       = strtoupper(trim($body['nombre'] ?? ''));
    $presentacion = strtoupper(trim($body['presentacion'] ?? ''));
    $stock        = intval($body['stock'] ?? 0);
    $minimo       = intval($body['minimo'] ?? 10);
    $vencimiento  = $body['vencimiento'] ?? '';
    $estado       = strtoupper(trim($body['estado'] ?? 'ACTIVO'));

    if ($id <= 0 || empty($codigo) || empty($nombre)) {
        $response->getBody()->write(json_encode(['error' => 'Datos insuficientes para la edición. Nro ID: ' . $id]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    try {
        $sql = "UPDATE public.insumos_laboratorio 
                SET codigo_interno = ?, nombre_insumo = ?, lote_codigo = ?, 
                    presentacion = ?, stock_actual = ?, stock_minimo = ?, 
                    fecha_vencimiento = ?, estado = ?
                WHERE id = ?";
                
        $stmt = $conn_pg->prepare($sql);
        $stmt->execute([$codigo, $nombre, $lote, $presentacion, $stock, $minimo, $vencimiento, $estado, $id]);

        $response->getBody()->write(json_encode(['success' => true, 'message' => 'Insumo actualizado en PostgreSQL']));
        return $response->withHeader('Content-Type', 'application/json');
        
    } catch (\PDOException $e) {
        $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

// --- API CRUD: ELIMINAR INSUMO DE POSTGRESQL ---
$app->delete('/api/insumos/eliminar/{id}', function ($request, $response, $args) {
    require_once __DIR__ . '/../src/db_postgres.php';
    
    $id = intval($args['id'] ?? 0);

    try {
        $sql = "DELETE FROM public.insumos_laboratorio WHERE id = ?";
        $stmt = $conn_pg->prepare($sql);
        $stmt->execute([$id]);

        $response->getBody()->write(json_encode(['success' => true, 'message' => 'Insumo eliminado.']));
        return $response->withHeader('Content-Type', 'application/json');
        
    } catch (\PDOException $e) {
        $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

// ================================IMPRIMIR==============================


$app->get('/imprimir-resultado/{idMovimiento}', function ($request, $response, array $args) {
    require_once __DIR__ . '/../src/imprimir.php';
    $idMovimiento = intval($args['idMovimiento']);
    return generarPdfLaboratorio($idMovimiento, $response);
});

// ==========================================
// ENDPOINT: BUSCAR SERVICIOS / PROCEDENCIAS
// ==========================================
// NOTA EL 'use ($conn_sql)' AQUÍ:
$app->get('/api/buscar-servicios', function ($request, $response) use ($conn_sql) {
    require_once __DIR__ . '/../src/db_sqlserver.php'; 
    
    $queryParams = $request->getQueryParams();
    $search = isset($queryParams['q']) ? trim($queryParams['q']) : '';

    try {
        $sql = "SELECT IdServicio, Nombre, Codigo FROM Servicios WHERE Nombre LIKE ? OR Codigo LIKE ?";
        $likeSearch = "%" . $search . "%";
        $params = array($likeSearch, $likeSearch);

        // Ahora $conn_sql ya no será null
        $stmt = sqlsrv_query($conn_sql, $sql, $params);

        if ($stmt === false) {
            throw new Exception(print_r(sqlsrv_errors(), true));
        }

        $resultados = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $resultados[] = $row;
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

// ==========================================
// ENDPOINT: BUSCAR DIAGNÓSTICOS (CIE-10)
// ==========================================
// NOTA EL 'use ($conn_sql)' AQUÍ TAMBIÉN:
$app->get('/api/buscar-diagnosticos', function ($request, $response) use ($conn_sql) {
    require_once __DIR__ . '/../src/db_sqlserver.php';
    
    $queryParams = $request->getQueryParams();
    $search = isset($queryParams['q']) ? trim($queryParams['q']) : '';

    try {
        $sql = "SELECT TOP 30 IdDiagnostico, CodigoCIE2004, Descripcion FROM Diagnosticos WHERE Descripcion LIKE ? OR CodigoCIE2004 LIKE ?";
        $likeSearch = "%" . $search . "%";
        $params = array($likeSearch, $likeSearch);

        $stmt = sqlsrv_query($conn_sql, $sql, $params);

        if ($stmt === false) {
            throw new Exception(print_r(sqlsrv_errors(), true));
        }

        $resultados = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $resultados[] = $row;
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
// ==========================================
// ENDPOINT: BUSCAR TARIFAS / FINANCIAMIENTOS
// ==========================================
$app->get('/api/buscar-tarifas', function ($request, $response) use ($conn_sql) {
    // Cargamos el archivo de conexión por si acaso, asegurando el scope
    require_once __DIR__ . '/../src/db_sqlserver.php'; 
    
    $queryParams = $request->getQueryParams();
    $search = isset($queryParams['q']) ? trim($queryParams['q']) : '';

    try {
        // Cambiamos 'Estado = 1' por 'SeIngresPrecios = 1'
        $sql = "SELECT IdTipoFinanciamiento, Descripcion 
                FROM TiposFinanciamiento 
                WHERE Descripcion LIKE ? AND SeIngresPrecios = 1"; 
        
        $likeSearch = "%" . $search . "%";
        $params = array($likeSearch);

        $stmt = sqlsrv_query($conn_sql, $sql, $params);

        if ($stmt === false) {
            throw new Exception(print_r(sqlsrv_errors(), true));
        }

        $resultados = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $resultados[] = $row;
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

// =======================================================================
// ENDPOINT: BUSCAR EXÁMENES CPT CON PRECIO UNITARIO SEGÚN LA TARIFA ACTIVA
// =======================================================================
$app->get('/api/buscar-examenes-cpt', function ($request, $response) use ($conn_sql) {
    require_once __DIR__ . '/../src/db_sqlserver.php';
    
    $queryParams = $request->getQueryParams();
    $search   = isset($queryParams['q']) ? trim($queryParams['q']) : '';
    // Capturamos el ID de la Tarifa/Financiamiento de la cabecera (enviado desde JS)
    $idTarifa = isset($queryParams['idTarifa']) ? intval($queryParams['idTarifa']) : 0;

    try {
        // Tu lógica exacta: Catálogo + Precios de Hospitalización/Módulos filtrados por el Seguro
        $sql = "SELECT TOP 30
                    fac.IdProducto AS IdServicio,
                    fac.Codigo,
                    fac.Nombre AS Examen,
                    ISNULL(fach.PrecioUnitario, 0.00) AS PrecioUnitario
                FROM dbo.FactCatalogoServicios fac
                INNER JOIN dbo.FactCatalogoServiciosHosp fach ON fac.IdProducto = fach.IdProducto
                WHERE (fac.Nombre LIKE ? OR fac.Codigo LIKE ?)";

        // Si se envió un seguro válido, filtramos por su ID de tipo de financiamiento para no duplicar filas
        if ($idTarifa > 0) {
            $sql .= " AND fach.IdTipoFinanciamiento = ?";
            $likeSearch = "%" . $search . "%";
            $params = array($likeSearch, $likeSearch, $idTarifa);
        } else {
            $likeSearch = "%" . $search . "%";
            $params = array($likeSearch, $likeSearch);
        }

        $sql .= " ORDER BY fac.Nombre ASC";

        $stmt = sqlsrv_query($conn_sql, $sql, $params);

        if ($stmt === false) {
            throw new Exception(print_r(sqlsrv_errors(), true));
        }

        $resultados = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $row['PrecioUnitario'] = number_format((float)$row['PrecioUnitario'], 2, '.', '');
            $resultados[] = $row;
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

$app->run();
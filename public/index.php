<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use DI\ContainerBuilder;

// Reporte de errores visible para depuración
error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Helpers/helpers.php';

// Controladores
require_once __DIR__ . '/../src/Controllers/PanelController.php';
require_once __DIR__ . '/../src/Controllers/SolicitudesController.php';
require_once __DIR__ . '/../src/Controllers/ClinicaController.php';
require_once __DIR__ . '/../src/Controllers/ResultadosController.php';
require_once __DIR__ . '/../src/Controllers/SeguimientoController.php';
require_once __DIR__ . '/../src/Controllers/NuevaOrdenController.php';
require_once __DIR__ . '/../src/Controllers/UsuarioController.php';
require_once __DIR__ . '/../src/Controllers/ReportesController.php';
// Generador de ACL
$acl = require __DIR__ . '/../src/Config/auth.php';

// Roles oficiales definidos en tu login.php
$rolesAdmin = ['admin', 'administrador','admin_lab'];
$rolesLab   = ['admin', 'administrador', 'admin_lab', 'tecnico_lab', 'analista_lab', 'personal_banco', 'medico_lab'];

// 1. Instanciar Contenedor DI
$containerBuilder = new ContainerBuilder();
$container = $containerBuilder->build();

// 2. Configuración de Base de Datos
$setupDB = require __DIR__ . '/../src/Config/db.php';
$setupDB($container);

// 3. Registrar Controladores
$container->set('PanelController', fn($c) => new PanelController($c));
$container->set('SolicitudesController', fn($c) => new SolicitudesController($c));
$container->set('ClinicaController', fn($c) => new ClinicaController($c));
$container->set('ResultadosController', fn($c) => new ResultadosController($c));
$container->set('SeguimientoController', fn($c) => new SeguimientoController($c));
$container->set('NuevaOrdenController', fn($c) => new NuevaOrdenController($c));
$container->set('UsuarioController', fn($c) => new UsuarioController($c));
$container->set('ReportesController', fn($c) => new ReportesController($c));
// 4. Crear Aplicación Slim
$app = AppFactory::createFromContainer($container);
$app->setBasePath('/laboratorio/public');

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

// ================= RUTAS DEL MÓDULO =================

// Redirección raíz única
$app->get('/', function (Request $request, Response $response) {
    return $response->withHeader('Location', '/laboratorio/public/panel')->withStatus(302);
});

// Panel Principal
$app->get('/panel', function (Request $request, Response $response) {
    ob_start();
    include __DIR__ . '/views/panel.php';
    $html = ob_get_clean();
    $response->getBody()->write($html);
    return $response;
})->add($acl($rolesLab));

// API del Panel
$app->get('/api/dashboard/metricas', 'PanelController:obtenerMetricasGenerales')->add($acl($rolesLab));

// Solicitudes (Una sola ruta para la vista + APIs del controlador)
$app->get('/solicitudes', 'SolicitudesController:vistaSolicitudes')->add($acl($rolesLab));
$app->get('/api/solicitudes/listar', 'SolicitudesController:listarSolicitudes')->add($acl($rolesLab));
$app->get('/api/solicitudes/detalle', 'SolicitudesController:detalleExamenesOrden')->add($acl($rolesLab));
$app->get('/api/get-plantilla-examen', 'SolicitudesController:obtenerPlantillaExamen')->add($acl($rolesLab));
$app->post('/api/solicitudes/cambiar-estado', 'SolicitudesController:cambiarEstadoEntrega')->add($acl($rolesLab));
$app->post('/api/solicitudes/guardar-resultado', 'SolicitudesController:guardarResultadoManual')->add($acl($rolesLab));

// Búsqueda de catálogo CPT en ClinicaController
$app->get('/api/clinica/buscar-cpt', 'ClinicaController:buscarCatalogoCPT')->add($acl($rolesLab));

// Módulo exclusivo de creación de orden
$app->get('/api/nueva-orden/buscar-cuenta', 'NuevaOrdenController:buscarPorCuenta')->add($acl($rolesLab));
$app->post('/api/nueva-orden/crear', 'NuevaOrdenController:registrarOrden')->add($acl($rolesLab));
$app->post('/api/nueva-orden/eliminar', 'NuevaOrdenController:eliminarOrden')->add($acl($rolesLab));
// Pacientes (Aún pendiente de migrar a su propio PacientesController)
$app->get('/pacientes', function (Request $request, Response $response) {
    ob_start();
    include __DIR__ . '/views/pacientes.php';
    $html = ob_get_clean();
    $response->getBody()->write($html);
    return $response;
})->add($acl($rolesLab));

$app->get('/muestras', 'SeguimientoController:vistaSeguimiento')->add($acl($rolesLab));
$app->get('/api/seguimiento/buscar', 'SeguimientoController:buscarOrdenesMedico')->add($acl($rolesLab));
$app->get('/api/seguimiento/detalle', 'SeguimientoController:detalleResultadosLectura')->add($acl($rolesLab));

$app->get('/resultados', 'ResultadosController:vistaResultados')->add($acl($rolesLab));
$app->get('/api/resultados/buscar', 'ResultadosController:buscarOrdenesPaciente')->add($acl($rolesLab));
$app->get('/api/resultados/detalle', 'ResultadosController:obtenerResultadosDetallados')->add($acl($rolesLab));

// Insumos
$app->get('/insumos', function (Request $request, Response $response) {
    ob_start();
    include __DIR__ . '/views/insumos.php';
    $html = ob_get_clean();
    $response->getBody()->write($html);
    return $response;
})->add($acl($rolesLab));

// ================= RUTAS CLÍNICAS COMPARTIDAS =================
$app->get('/api/clinica/buscar-diagnostico', 'ClinicaController:buscarDiagnostico')->add($acl($rolesLab));
$app->get('/api/clinica/buscar-medico', 'ClinicaController:buscarMedico')->add($acl($rolesLab));
$app->get('/api/clinica/buscar-servicio', 'ClinicaController:buscarServicio')->add($acl($rolesLab));
$app->post('/api/clinica/actualizar-cabecera', 'ClinicaController:actualizarCabeceraOrden')->add($acl($rolesLab));

// =========================================================================
// RUTAS: MÓDULO DE USUARIOS (POSTGRESQL ACL)
// =========================================================================
$app->get('/usuarios', 'UsuarioController:vistaUsuarios')->add($acl($rolesAdmin));
$app->get('/api/usuarios/listar', 'UsuarioController:listarUsuarios')->add($acl($rolesAdmin));
$app->post('/api/usuarios/crear', 'UsuarioController:crearUsuario')->add($acl($rolesAdmin));
$app->post('/api/usuarios/actualizar', 'UsuarioController:actualizarUsuario')->add($acl($rolesAdmin));
$app->post('/api/usuarios/cambiar-estado', 'UsuarioController:conmutarEstado')->add($acl($rolesAdmin));
$app->post('/api/usuarios/eliminar', 'UsuarioController:eliminarUsuario')->add($acl($rolesAdmin));
// Ejecución única de Slim

// Rutas exclusivas para cada tipo de reporte
$app->get('/api/reportes/pacientes', 'ReportesController:reportePacientes')->add($acl($rolesLab));
$app->get('/api/reportes/con-resultados', 'ReportesController:reporteConResultados')->add($acl($rolesLab));
$app->get('/api/reportes/procedimientos', 'ReportesController:reporteProcedimientosCPT')->add($acl($rolesLab));
$app->get('/api/reportes/consolidado-gastos', [ReportesController::class, 'reporteConsolidadoGastos']);
$app->get('/api/reportes/resultados-sisgalen', [ReportesController::class, 'reporteResultadosSisgalen']);

$app->run();
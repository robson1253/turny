<?php
// --- CÓDIGO DE DEPURAÇÃO (PODE REMOVER EM PRODUÇÃO) ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// A PRIMEIRA COISA A FAZER: Inicia ou resume uma sessão em todas as páginas.
session_start();

/**
 * -------------------------------------------------------------------------
 * PONTO DE ENTRADA ÚNICO DA APLICAÇÃO (ROTEADOR)
 * -------------------------------------------------------------------------
 */


// 1. Carrega todos os controllers necessários (apenas uma vez).
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Http/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Http/Controllers/EmpresaController.php';
require_once __DIR__ . '/../app/Http/Controllers/UserController.php';
require_once __DIR__ . '/../app/Http/Controllers/SettingsController.php';
require_once __DIR__ . '/../app/Http/Controllers/OperadorController.php';
require_once __DIR__ . '/../app/Http/Controllers/ApiController.php';
require_once __DIR__ . '/../app/Http/Controllers/PainelEmpresaController.php';
require_once __DIR__ . '/../app/Http/Controllers/StoreController.php';
require_once __DIR__ . '/../app/Http/Controllers/RegisterController.php';
require_once __DIR__ . '/../app/Http/Controllers/PainelOperadorController.php';
require_once __DIR__ . '/../app/Http/Controllers/ErpSystemController.php';
require_once __DIR__ . '/../app/Http/Controllers/PainelAdminEmpresaController.php';


// 2. Captura a URL (sem query string) e o método da requisição.
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];


// 3. Instancia todos os controllers.
$authController = new AuthController();
$empresaController = new EmpresaController();
$userController = new UserController();
$settingsController = new SettingsController();
$operadorController = new OperadorController();
$apiController = new ApiController();
$painelEmpresaController = new PainelEmpresaController();
$storeController = new StoreController();
$registerController = new RegisterController();
$painelOperadorController = new PainelOperadorController();
$erpSystemController = new ErpSystemController();
$painelAdminEmpresaController = new PainelAdminEmpresaController();


// 4. Lógica do Roteador: decide o que fazer com base na URL.
switch ($requestUri) {

    // --- Rotas Públicas ---
    case '/':
        if (isset($_SESSION['user_id'])) {
            if ($_SESSION['user_role'] === 'admin') { header('Location: /dashboard'); } 
            elseif ($_SESSION['user_role'] === 'administrador') { header('Location: /painel/empresa-admin'); } 
            else { header('Location: /painel/empresa'); }
            exit();
        } elseif (isset($_SESSION['operator_id'])) {
            header('Location: /painel/operador');
            exit();
        } else {
            require_once __DIR__ . '/../app/Views/landing_page.php';
        }
        break;
    case '/login':
        if ($requestMethod === 'POST') { $authController->processLogin(); } 
        else { $authController->showLoginForm(); }
        break;
    case '/registro/operador':
        if ($requestMethod === 'POST') { $registerController->registerOperator(); } 
        else { $registerController->showOperatorForm(); }
        break;

    // --- Rotas Protegidas (Autenticadas) ---
    case '/logout':
        session_unset(); session_destroy(); header('Location: /login'); exit();
        break;

    // --- Rotas do Painel Master do Admin ---
    case '/dashboard':
        require_once __DIR__ . '/../app/Views/dashboard.php';
        break;
    case '/admin/empresas':
        $empresaController->index();
        break;
    case '/admin/empresas/criar':
        if ($requestMethod === 'POST') { $empresaController->store(); } 
        else { $empresaController->showCreateForm(); }
        break;
    case '/admin/empresas/editar':
        $empresaController->edit();
        break;
    case '/admin/empresas/atualizar':
        if ($requestMethod === 'POST') { $empresaController->update(); }
        break;
    case '/admin/empresas/toggle-status':
        $empresaController->toggleStatus();
        break;
    case '/admin/utilizadores':
        $userController->index();
        break;
    case '/admin/utilizadores/editar':
        $userController->edit();
        break;
    case '/admin/utilizadores/atualizar':
        if ($requestMethod === 'POST') { $userController->update(); }
        break;
    case '/admin/utilizadores/toggle-status':
        $userController->toggleStatus();
        break;
    case '/admin/settings':
        $settingsController->index();
        break;
    case '/admin/settings/update':
        if ($requestMethod === 'POST') { $settingsController->update(); }
        break;
    case '/admin/operadores':
        $operadorController->index();
        break;
    case '/admin/operadores/criar':
        if ($requestMethod === 'POST') { $operadorController->store(); } 
        else { $operadorController->showCreateForm(); }
        break;
    case '/admin/operadores/editar':
        $operadorController->edit();
        break;
    case '/admin/operadores/atualizar':
        if ($requestMethod === 'POST') { $operadorController->update(); }
        break;
    case '/admin/operadores/toggle-status':
        $operadorController->toggleStatus();
        break;
    case '/admin/operadores/verificar':
        $operadorController->showVerificationForm();
        break;
    case '/admin/operadores/processar-verificacao':
        if ($requestMethod === 'POST') { $operadorController->processVerification(); }
        break;
    case '/admin/stores':
        $storeController->index();
        break;
    case '/admin/stores/criar':
        if ($requestMethod === 'POST') { $storeController->store(); } 
        else { $storeController->showCreateForm(); }
        break;
    case '/admin/stores/editar':
        $storeController->edit();
        break;
    case '/admin/stores/atualizar':
        if ($requestMethod === 'POST') { $storeController->update(); }
        break;
    case '/admin/stores/toggle-status':
        $storeController->toggleStatus();
        break;
    case '/admin/erps':
        $erpSystemController->index();
        break;
    case '/admin/erps/criar':
        if ($requestMethod === 'POST') { $erpSystemController->store(); } 
        else { $erpSystemController->showCreateForm(); }
        break;
    case '/admin/erps/editar':
        $erpSystemController->edit();
        break;
    case '/admin/erps/atualizar':
        if ($requestMethod === 'POST') { $erpSystemController->update(); }
        break;
    case '/admin/erps/apagar':
        $erpSystemController->destroy();
        break;

    // --- Rotas dos Painéis de Empresa ---
    case '/painel/empresa':
        $painelEmpresaController->showDashboard();
        break;
    case '/painel/empresa-admin':
        $painelAdminEmpresaController->index();
        break;
    case '/painel/vagas':
        $painelEmpresaController->indexVagas();
        break;
    case '/painel/vagas/dias':
        $painelEmpresaController->showDaysForStore();
        break;
    case '/painel/vagas/dia':
        $painelEmpresaController->showShiftsByDay();
        break;
    case '/painel/vagas/criar':
        if ($requestMethod === 'POST') { $painelEmpresaController->storeVaga(); }
        else { $painelEmpresaController->showCreateVagaForm(); }
        break;
    case '/painel/vagas/criar-lote':
        if ($requestMethod === 'POST') { $painelEmpresaController->storeBatchShifts(); }
        break;
    case '/painel/vagas/editar':
        $painelEmpresaController->editVaga();
        break;
    case '/painel/vagas/atualizar':
        if ($requestMethod === 'POST') { $painelEmpresaController->updateVaga(); }
        break;
    case '/painel/vagas/cancelar':
        $painelEmpresaController->cancelVaga();
        break;
    case '/painel/vagas/candidatos':
        $painelEmpresaController->showApplicants();
        break;
    case '/painel/vagas/candidatos/status':
        $painelEmpresaController->updateApplicationStatus();
        break;
    case '/painel/vagas/concluir':
        if ($requestMethod === 'POST') {
            $painelEmpresaController->processShiftCompletion();
        }
        break;
    case '/painel/vagas/templates':
        $painelEmpresaController->showShiftTemplates();
        break;
    case '/painel/vagas/templates/criar':
        if ($requestMethod === 'POST') {
            $painelEmpresaController->storeShiftTemplate();
        }
        break;
	case '/painel/vagas/criar-lote-semanal':
        if ($requestMethod === 'POST') {
            $painelEmpresaController->storeWeeklyPlan();
        }
        break;
			
    case '/painel/vagas/templates/apagar':
        $painelEmpresaController->deleteShiftTemplate();
        break;
		case '/painel/vagas/planear':
        $painelEmpresaController->showPlanner();
        break;
    case '/painel/treinamentos':
        $painelEmpresaController->listTrainingRequests();
        break;
    case '/painel/treinamentos/processar':
        $painelEmpresaController->processTrainingRequest();
        break;
    
    // --- Rotas do Painel do Operador ---
    case '/painel/operador':
        $painelOperadorController->index();
        break;
    case '/painel/operador/qualificacoes':
        $painelOperadorController->showQualificationsPage();
        break;
    case '/painel/operador/qualificacoes/agendar':
        if ($requestMethod === 'POST') {
            $painelOperadorController->scheduleTraining();
        }
        break;
    case '/painel/operador/vagas/aceitar':
        $painelOperadorController->acceptShift();
        break;
    case '/painel/operador/meus-turnos':
        $painelOperadorController->showMyShifts();
        break;
    case '/painel/operador/meus-turnos/cancelar':
        $painelOperadorController->cancelApplication();
        break;
    case '/painel/operador/meus-turnos/transferir':
        if ($requestMethod === 'POST') { $painelOperadorController->initiateTransfer(); }
        break;
    case '/painel/operador/avaliar':
        if ($requestMethod === 'POST') {
            $painelOperadorController->rateCompany();
        } else {
            $painelOperadorController->showRateCompanyForm();
        }
        break;
    case '/painel/operador/ofertas':
        $painelOperadorController->showTransferOffers();
        break;
    case '/painel/operador/ofertas/responder':
        $painelOperadorController->respondToTransfer();
        break;
    case '/painel/operador/perfil':
        $painelOperadorController->showProfile();
        break;

    // --- Rotas de API (para o JavaScript) ---
    case '/api/cep-lookup':
        $apiController->lookupCep();
        break;
    case '/api/company-shifts':
        $apiController->getShifts();
        break;
    case '/api/training-slots':
        $apiController->getAvailableTrainingSlots();
        break;
    case '/api/stores-by-erp':
        $apiController->getStoresByErp();
        break;

    // --- Rota Padrão (404) ---
    default:
        http_response_code(404);
        echo 'Erro 404 - Página não encontrada.';
        break;
}
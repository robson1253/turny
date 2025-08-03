<?php

// A PRIMEIRA COISA A FAZER: Inicia ou resume uma sessão.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * =================================================================
 * CONFIGURAÇÃO GLOBAL DA APLICAÇÃO
 * =================================================================
 */

// 1. Define o ambiente da aplicação ('development' ou 'production')
// Em produção, mude esta linha para 'production'
define('APP_ENV', 'development');

// 2. Carrega o autoloader do Composer e as nossas funções auxiliares
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Utils/helpers.php';

// 3. Configura o manipulador de erros global
set_exception_handler(function($exception) {
    // Grava sempre o erro detalhado no arquivo de log
    $logMessage = "[" . date("Y-m-d H:i:s") . "] " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine() . "\n";
    error_log($logMessage, 3, __DIR__ . '/../logs/errors.log');

    // Comportamento diferente para produção e desenvolvimento
    if (APP_ENV === 'production') {
        http_response_code(500);
        // Mostra a página de erro amigável
        require __DIR__ . '/../app/Views/errors/500.php';
    } else {
        // Em desenvolvimento, mostra o erro detalhado na tela
        http_response_code(500);
        echo "<pre style='background: #fce4e4; color: #c62828; padding: 20px; border-radius: 5px; border: 1px solid #c62828; font-family: monospace;'>";
        echo "<strong>Fatal Error:</strong><br><br>";
        echo "<strong>Message:</strong> " . htmlspecialchars($exception->getMessage()) . "<br><br>";
        echo "<strong>File:</strong> " . $exception->getFile() . "<br>";
        echo "<strong>Line:</strong> " . $exception->getLine() . "<br>";
        echo "<strong>Stack Trace:</strong><br>" . htmlspecialchars($exception->getTraceAsString());
        echo "</pre>";
    }
    exit();
});

/**
 * =================================================================
 * PONTO DE ENTRADA ÚNICO DA APLICAÇÃO (ROTEADOR)
 * =================================================================
 */

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

// A lógica do roteador continua exatamente a mesma
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
        $controller = new App\Http\Controllers\AuthController();
        if ($requestMethod === 'POST') { $controller->processLogin(); } 
        else { $controller->showLoginForm(); }
        break;
    case '/registro/operador':
        $controller = new App\Http\Controllers\RegisterController();
        if ($requestMethod === 'POST') { $controller->registerOperator(); } 
        else { $controller->showOperatorForm(); }
        break;

    // ... (O resto do seu switch case continua aqui, exatamente como estava) ...
    // ... (Nenhuma alteração necessária do /logout em diante) ...
    
    // --- Rotas Protegidas (Autenticadas) ---
    case '/logout':
        session_unset(); session_destroy(); header('Location: /login'); exit();
        break;

    // --- Rotas do Painel Master do Admin ---
    case '/dashboard':
        require_once __DIR__ . '/../app/Views/dashboard.php';
        break;
    case '/admin/empresas':
        (new App\Http\Controllers\EmpresaController())->index();
        break;
    case '/admin/empresas/criar':
        $controller = new App\Http\Controllers\EmpresaController();
        if ($requestMethod === 'POST') { $controller->store(); } 
        else { $controller->showCreateForm(); }
        break;
    case '/admin/empresas/editar':
        (new App\Http\Controllers\EmpresaController())->edit();
        break;
    case '/admin/empresas/atualizar':
        if ($requestMethod === 'POST') { (new App\Http\Controllers\EmpresaController())->update(); }
        break;
    case '/admin/empresas/toggle-status':
        (new App\Http\Controllers\EmpresaController())->toggleStatus();
        break;
    case '/admin/utilizadores':
        (new App\Http\Controllers\UserController())->index();
        break;
    case '/admin/utilizadores/editar':
        (new App\Http\Controllers\UserController())->edit();
        break;
    case '/admin/utilizadores/atualizar':
        if ($requestMethod === 'POST') { (new App\Http\Controllers\UserController())->update(); }
        break;
    case '/admin/utilizadores/toggle-status':
        (new App\Http\Controllers\UserController())->toggleStatus();
        break;
    case '/admin/settings':
        (new App\Http\Controllers\SettingsController())->index();
        break;
    case '/admin/settings/update':
        if ($requestMethod === 'POST') { (new App\Http\Controllers\SettingsController())->update(); }
        break;
    case '/admin/operadores':
        (new App\Http\Controllers\OperadorController())->index();
        break;
    case '/admin/operadores/criar':
        $controller = new App\Http\Controllers\OperadorController();
        if ($requestMethod === 'POST') { $controller->store(); } 
        else { $controller->showCreateForm(); }
        break;
    case '/admin/operadores/editar':
        (new App\Http\Controllers\OperadorController())->edit();
        break;
    case '/admin/operadores/atualizar':
        if ($requestMethod === 'POST') { (new App\Http\Controllers\OperadorController())->update(); }
        break;
    case '/admin/operadores/toggle-status':
        (new App\Http\Controllers\OperadorController())->toggleStatus();
        break;
    case '/admin/operadores/verificar':
        (new App\Http\Controllers\OperadorController())->showVerificationForm();
        break;
    case '/admin/operadores/processar-verificacao':
        if ($requestMethod === 'POST') { (new App\Http\Controllers\OperadorController())->processVerification(); }
        break;
    case '/admin/stores':
        (new App\Http\Controllers\StoreController())->index();
        break;
    case '/admin/stores/criar':
        $controller = new App\Http\Controllers\StoreController();
        if ($requestMethod === 'POST') { $controller->store(); } 
        else { $controller->showCreateForm(); }
        break;
    case '/admin/stores/editar':
        (new App\Http\Controllers\StoreController())->edit();
        break;
    case '/admin/stores/atualizar':
        if ($requestMethod === 'POST') { (new App\Http\Controllers\StoreController())->update(); }
        break;
    case '/admin/stores/toggle-status':
        (new App\Http\Controllers\StoreController())->toggleStatus();
        break;
    case '/admin/erps':
        (new App\Http\Controllers\ErpSystemController())->index();
        break;
    case '/admin/erps/criar':
        $controller = new App\Http\Controllers\ErpSystemController();
        if ($requestMethod === 'POST') { $controller->store(); } 
        else { $controller->showCreateForm(); }
        break;
    case '/admin/erps/editar':
        (new App\Http\Controllers\ErpSystemController())->edit();
        break;
    case '/admin/erps/atualizar':
        if ($requestMethod === 'POST') { (new App\Http\Controllers\ErpSystemController())->update(); }
        break;
    case '/admin/erps/apagar':
        (new App\Http\Controllers\ErpSystemController())->destroy();
        break;
	case '/admin/funcoes':
    (new App\Http\Controllers\JobFunctionController())->index();
		break;
	case '/admin/funcoes/criar':
    $controller = new App\Http\Controllers\JobFunctionController();
    if ($requestMethod === 'POST') { $controller->store(); } 
    else { $controller->showCreateForm(); }
		break;
	case '/admin/funcoes/editar':
    (new App\Http\Controllers\JobFunctionController())->edit();
		break;
	case '/admin/funcoes/atualizar':
    if ($requestMethod === 'POST') { (new App\Http\Controllers\JobFunctionController())->update(); }
		break;
	case '/admin/funcoes/apagar':
    if ($requestMethod === 'POST') { (new App\Http\Controllers\JobFunctionController())->destroy(); }
		break;
    // --- Rotas dos Painéis de Empresa ---
    case '/painel/empresa':
        (new App\Http\Controllers\PainelEmpresaController())->showDashboard();
        break;
    case '/painel/empresa-admin':
        (new App\Http\Controllers\PainelAdminEmpresaController())->index();
        break;
    case '/painel/vagas':
        (new App\Http\Controllers\PainelEmpresaController())->indexVagas();
        break;
    case '/painel/vagas/dias':
        (new App\Http\Controllers\PainelEmpresaController())->showDaysForStore();
        break;
    case '/painel/vagas/dia':
        (new App\Http\Controllers\PainelEmpresaController())->showShiftsByDay();
        break;
    case '/painel/vagas/criar':
        $controller = new App\Http\Controllers\PainelEmpresaController();
        if ($requestMethod === 'POST') { $controller->storeVaga(); } 
        else { $controller->showCreateVagaForm(); }
        break;
    case '/painel/vagas/criar-lote':
        if ($requestMethod === 'POST') { (new App\Http\Controllers\PainelEmpresaController())->storeBatchShifts(); }
        break;
    case '/painel/vagas/editar':
        (new App\Http\Controllers\PainelEmpresaController())->editVaga();
        break;
    case '/painel/vagas/atualizar':
        if ($requestMethod === 'POST') { (new App\Http\Controllers\PainelEmpresaController())->updateVaga(); }
        break;
    case '/painel/vagas/cancelar':
        (new App\Http\Controllers\PainelEmpresaController())->cancelVaga();
        break;
    case '/painel/vagas/candidatos':
        (new App\Http\Controllers\PainelEmpresaController())->showApplicants();
        break;
    case '/painel/vagas/candidatos/status':
        (new App\Http\Controllers\PainelEmpresaController())->updateApplicationStatus();
        break;
    case '/painel/vagas/concluir':
        if ($requestMethod === 'POST') { (new App\Http\Controllers\PainelEmpresaController())->processShiftCompletion(); }
        break;
    case '/painel/vagas/templates':
        (new App\Http\Controllers\PainelEmpresaController())->showShiftTemplates();
        break;
    case '/painel/vagas/templates/criar':
        if ($requestMethod === 'POST') { (new App\Http\Controllers\PainelEmpresaController())->storeShiftTemplate(); }
        break;
    case '/painel/vagas/criar-lote-semanal':
        if ($requestMethod === 'POST') { (new App\Http\Controllers\PainelEmpresaController())->storeWeeklyPlan(); }
        break;
    case '/painel/vagas/templates/apagar':
        (new App\Http\Controllers\PainelEmpresaController())->deleteShiftTemplate();
        break;
    case '/painel/vagas/planear':
        (new App\Http\Controllers\PainelEmpresaController())->showPlanner();
        break;
    case '/painel/treinamentos':
        (new App\Http\Controllers\PainelEmpresaController())->listTrainingRequests();
        break;
    case '/painel/treinamentos/processar':
        (new App\Http\Controllers\PainelEmpresaController())->processTrainingRequest();
        break;
    
    // --- Rotas do Painel do Operador ---
    case '/painel/operador':
        (new App\Http\Controllers\PainelOperadorController())->index();
        break;
    case '/painel/operador/qualificacoes':
        (new App\Http\Controllers\PainelOperadorController())->showQualificationsPage();
        break;
    case '/painel/operador/qualificacoes/agendar':
        if ($requestMethod === 'POST') { (new App\Http\Controllers\PainelOperadorController())->scheduleTraining(); }
        break;
    case '/painel/operador/vagas/aceitar':
        (new App\Http\Controllers\PainelOperadorController())->acceptShift();
        break;
    case '/painel/operador/meus-turnos':
        (new App\Http\Controllers\PainelOperadorController())->showMyShifts();
        break;
    case '/painel/operador/meus-turnos/cancelar':
        (new App\Http\Controllers\PainelOperadorController())->cancelApplication();
        break;
    case '/painel/operador/meus-turnos/transferir':
        if ($requestMethod === 'POST') { (new App\Http\Controllers\PainelOperadorController())->initiateTransfer(); }
        break;
    case '/painel/operador/avaliar':
        $controller = new App\Http\Controllers\PainelOperadorController();
        if ($requestMethod === 'POST') { $controller->rateCompany(); } 
        else { $controller->showRateCompanyForm(); }
        break;
    case '/painel/operador/ofertas':
        (new App\Http\Controllers\PainelOperadorController())->showTransferOffers();
        break;
    case '/painel/operador/ofertas/responder':
        (new App\Http\Controllers\PainelOperadorController())->respondToTransfer();
        break;
    case '/painel/operador/perfil':
        (new App\Http\Controllers\PainelOperadorController())->showProfile();
        break;

    // --- Rotas de API (para o JavaScript) ---
    case '/api/cep-lookup':
        (new App\Http\Controllers\ApiController())->lookupCep();
        break;
    case '/api/company-shifts':
        (new App\Http\Controllers\ApiController())->getShifts();
        break;
    case '/api/training-slots':
        (new App\Http\Controllers\ApiController())->getAvailableTrainingSlots();
        break;
    case '/api/stores-by-erp':
        (new App\Http\Controllers\ApiController())->getStoresByErp();
        break;

    // --- Rota Padrão (404) ---
    default:
        http_response_code(404);
        echo 'Erro 404 - Página não encontrada.';
        break;
}
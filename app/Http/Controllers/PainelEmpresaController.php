<?php

namespace App\Http\Controllers;

use App\Database\Connection;
use App\Utils\Email;
use DateTime;
use DateTimeZone;
use IntlDateFormatter;
use PDO;
use PDOException;
use Exception;

/**
 * Controlador principal do painel da empresa.
 * Inclui funções de gestão de vagas, planeamento semanal, treinamentos e candidaturas.
 */
class PainelEmpresaController extends BaseController
{
    /**
     * Verifica se o usuário tem acesso à funcionalidade.
     * @param array $roles Perfis permitidos para a ação.
     * @throws Exception
     */
    protected function checkAccess (array $roles = ['gerente', 'administrador', 'recepcionista'])
    {
        if (!isset($_SESSION['user_id']) || empty($_SESSION['company_id']) || !in_array($_SESSION['user_role'], $roles)) {
            throw new Exception('Acesso negado ou sessão inválida.', 403);
        }
    }

    public function showDashboard()
    {
        $this->checkAccess();
        try {
            $pdo = Connection::getPdo();
            $companyId = $_SESSION['company_id'];
            $stmtVagasAbertas = $pdo->prepare("SELECT COUNT(*) FROM shifts WHERE company_id = ? AND status = 'aberta'");
            $stmtVagasAbertas->execute([$companyId]);
            $stmtLojas = $pdo->prepare("SELECT COUNT(*) FROM stores WHERE company_id = ? AND status = 1");
            $stmtLojas->execute([$companyId]);
            $stmtVagasOcupadas = $pdo->prepare("SELECT COUNT(*) FROM shifts WHERE company_id = ? AND status = 'preenchida'");
            $stmtVagasOcupadas->execute([$companyId]);
            $stats = ['vagas_abertas' => $stmtVagasAbertas->fetchColumn(), 'lojas_ativas' => $stmtLojas->fetchColumn(), 'vagas_ocupadas' => $stmtVagasOcupadas->fetchColumn()];
            $stmtStores = $pdo->prepare("SELECT id, name FROM stores WHERE company_id = ? AND status = 1 ORDER BY name ASC");
            $stmtStores->execute([$companyId]);
            $stores = $stmtStores->fetchAll();
            $stmtSettings = $pdo->query("SELECT setting_key, setting_value FROM settings");
            $settings = $stmtSettings->fetchAll(PDO::FETCH_KEY_PAIR);
            $stmtRequests = $pdo->prepare("SELECT COUNT(t.id) FROM training_requests t JOIN stores s ON t.store_id = s.id WHERE s.company_id = ? AND t.status IN ('solicitado', 'agendado')");
            $stmtRequests->execute([$companyId]);
            $pendingTrainingRequests = $stmtRequests->fetchColumn();
            $this->view('empresa/dashboard', compact('stats', 'stores', 'settings', 'pendingTrainingRequests'));
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public function indexVagas()
    {
        $this->checkAccess();
        try {
            $pdo = Connection::getPdo();
            $stmt = $pdo->prepare("SELECT DISTINCT s.id, s.name FROM stores s JOIN shifts sh ON s.id = sh.store_id WHERE s.company_id = ? AND s.status = 1 ORDER BY s.name ASC");
            $stmt->execute([$_SESSION['company_id']]);
            $storesWithShifts = $stmt->fetchAll();
            $this->view('empresa/vagas/selecionar_loja', compact('storesWithShifts'));
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public function showDaysForStore()
    {
        $this->checkAccess();
        $storeId = $_GET['store_id'] ?? null;
        if (!$storeId) throw new Exception('ID da loja não fornecido.');
        try {
            $pdo = Connection::getPdo();
            $stmtStore = $pdo->prepare("SELECT id, name FROM stores WHERE id = ? AND company_id = ?");
            $stmtStore->execute([$storeId, $_SESSION['company_id']]);
            $store = $stmtStore->fetch();
            if (!$store) throw new Exception('Loja não encontrada ou acesso não permitido.');
            $stmtDates = $pdo->prepare("SELECT DISTINCT shift_date FROM shifts WHERE store_id = ? ORDER BY shift_date ASC");
            $stmtDates->execute([$storeId]);
            $dates = $stmtDates->fetchAll(PDO::FETCH_COLUMN, 0);
            $this->view('empresa/vagas/selecionar_dia', compact('store', 'dates'));
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public function showShiftsByDay()
    {
        $this->checkAccess();
        $storeId = $_GET['store_id'] ?? null;
        $date = $_GET['date'] ?? null;
        if (!$storeId || !$date) throw new Exception('ID da loja e data são obrigatórios.');
        try {
            $pdo = Connection::getPdo();
            $stmtStore = $pdo->prepare("SELECT s.name as store_name FROM stores s WHERE s.id = ? AND s.company_id = ?");
            $stmtStore->execute([$storeId, $_SESSION['company_id']]);
            $storeInfo = $stmtStore->fetch();
            if (!$storeInfo) throw new Exception('Loja não encontrada ou acesso não permitido.');
            $stmtShifts = $pdo->prepare("SELECT s.*, jf.name as title, (SELECT COUNT(*) FROM shift_applications WHERE shift_id = s.id AND status IN ('aprovado', 'check_in', 'concluido', 'no_show')) as relevant_operator_count FROM shifts s LEFT JOIN job_functions jf ON s.job_function_id = jf.id WHERE s.store_id = ? AND s.shift_date = ? GROUP BY s.id ORDER BY s.start_time ASC");
            $stmtShifts->execute([$storeId, $date]);
            $shifts = $stmtShifts->fetchAll();
            $formatter = new IntlDateFormatter('pt_BR', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'America/Sao_Paulo', null, "EEEE, d 'de' MMMM 'de' yyyy");
            $formattedDate = ucfirst($formatter->format(new DateTime($date)));
            $this->view('empresa/vagas/listar_por_dia', compact('storeInfo', 'formattedDate', 'storeId', 'date', 'shifts'));
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public function showApplicants()
    {
        $this->checkAccess();
        $shiftId = $_GET['shift_id'] ?? null;
        if (!$shiftId) throw new Exception('ID da vaga não fornecido.');
        try {
            $pdo = Connection::getPdo();
            $stmtShift = $pdo->prepare("SELECT s.*, jf.name as title FROM shifts s LEFT JOIN job_functions jf ON s.job_function_id = jf.id WHERE s.id = ? AND s.company_id = ?");
            $stmtShift->execute([$shiftId, $_SESSION['company_id']]);
            $shift = $stmtShift->fetch();
            if (!$shift) throw new Exception('Vaga não encontrada ou acesso não permitido.');
            $stmtApplicants = $pdo->prepare("SELECT o.id, o.name, o.pontuacao, o.path_selfie, sa.status as application_status, sa.id as application_id FROM shift_applications sa JOIN operators o ON sa.operator_id = o.id WHERE sa.shift_id = ? AND sa.status IN ('aprovado', 'cancelado_operador', 'check_in', 'concluido', 'no_show') ORDER BY sa.status ASC, sa.applied_at ASC");
            $stmtApplicants->execute([$shiftId]);
            $allApplicants = $stmtApplicants->fetchAll();
            $this->view('empresa/vagas/candidatos', compact('shift', 'allApplicants'));
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public function showCreateVagaForm()
    {
        $this->checkAccess(['gerente']);
        try {
            $pdo = Connection::getPdo();
            $stmtStores = $pdo->prepare("SELECT id, name FROM stores WHERE company_id = ? AND status = 1 ORDER BY name ASC");
            $stmtStores->execute([$_SESSION['company_id']]);
            $stores = $stmtStores->fetchAll();
            $jobFunctions = $pdo->query("SELECT id, name FROM job_functions WHERE status = 1 ORDER BY name ASC")->fetchAll();
            $this->view('empresa/vagas/criar', compact('stores', 'jobFunctions'));
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public function storeVaga()
    {
        $this->checkAccess(['gerente']);
        verify_csrf_token();
        $store_id = $_POST['store_id'] ?? null;
        $job_function_id = $_POST['job_function_id'] ?? null;
        $shift_date = $_POST['shift_date'] ?? '';
        $start_time = $_POST['start_time'] ?? '';
        $end_time = $_POST['end_time'] ?? '';
        $num_positions = $_POST['num_positions'] ?? 0;
        $is_holiday = isset($_POST['is_holiday']) ? 1 : 0;
        if (empty($store_id) || empty($job_function_id) || empty($shift_date) || empty($start_time) || empty($end_time) || $num_positions <= 0) {
            throw new Exception('Todos os campos obrigatórios devem ser preenchidos.');
        }
        try {
            $pdo = Connection::getPdo();
            $stmtFunction = $pdo->prepare("SELECT hourly_rate FROM job_functions WHERE id = ?");
            $stmtFunction->execute([$job_function_id]);
            $hourlyRate = (float) $stmtFunction->fetchColumn();
            if (!$hourlyRate) throw new Exception('Função de trabalho inválida ou não encontrada.');
            $startTimeObj = new DateTime($start_time);
            $endTimeObj = new DateTime($end_time);
            $interval = $startTimeObj->diff($endTimeObj);
            $durationInHours = $interval->h + ($interval->i / 60);
            $valorBase = $durationInHours * $hourlyRate;
            $stmtSettings = $pdo->query("SELECT setting_key, setting_value FROM settings");
            $settings = $stmtSettings->fetchAll(PDO::FETCH_KEY_PAIR);
            $taxa_servico = (float)($settings['taxa_servico_fixa'] ?? 5.00);
            $bonus_feriado = (float)($settings['bonus_feriado'] ?? 25.00);
            $operator_payment = $valorBase;
            if ($is_holiday) $operator_payment += $bonus_feriado;
            $company_cost = $operator_payment + $taxa_servico;
            $sql = "INSERT INTO shifts (company_id, store_id, created_by_user_id, job_function_id, shift_date, start_time, end_time, operator_payment, company_cost, num_positions, is_holiday) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$_SESSION['company_id'], $store_id, $_SESSION['user_id'], $job_function_id, $shift_date, $start_time, $end_time, $operator_payment, $company_cost, $num_positions, $is_holiday]);
            flash('Vaga criada com sucesso!');
            header('Location: /painel/empresa');
            exit();
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public function editVaga()
    {
        $this->checkAccess(['gerente']);
        $shiftId = $_GET['id'] ?? null;
        if (!$shiftId) throw new Exception('ID da vaga não fornecido.');
        try {
            $pdo = Connection::getPdo();
            $stmt = $pdo->prepare("SELECT * FROM shifts WHERE id = ? AND company_id = ?");
            $stmt->execute([$shiftId, $_SESSION['company_id']]);
            $shift = $stmt->fetch();
            if (!$shift) throw new Exception('Vaga não encontrada ou acesso não permitido.');
            $stmtStores = $pdo->prepare("SELECT id, name FROM stores WHERE company_id = ? AND status = 1 ORDER BY name ASC");
            $stmtStores->execute([$_SESSION['company_id']]);
            $stores = $stmtStores->fetchAll();
            $jobFunctions = $pdo->query("SELECT id, name FROM job_functions WHERE status = 1 ORDER BY name ASC")->fetchAll();
            $this->view('empresa/vagas/editar', compact('shift', 'stores', 'jobFunctions'));
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public function updateVaga()
    {
        $this->checkAccess(['gerente']);
        verify_csrf_token();
        $shiftId = $_POST['id'] ?? null;
        $store_id = $_POST['store_id'] ?? null;
        $job_function_id = $_POST['job_function_id'] ?? null;
        $shift_date = $_POST['shift_date'] ?? '';
        $start_time = $_POST['start_time'] ?? '';
        $end_time = $_POST['end_time'] ?? '';
        $num_positions = $_POST['num_positions'] ?? 0;
        $is_holiday = isset($_POST['is_holiday']) ? 1 : 0;
        if (!$shiftId || empty($store_id) || empty($job_function_id) || empty($shift_date) || empty($start_time) || empty($end_time) || $num_positions <= 0) {
            throw new Exception('Todos os campos obrigatórios devem ser preenchidos.');
        }
        try {
            $pdo = Connection::getPdo();
            $startTimeObj = new DateTime($start_time);
            $endTimeObj = new DateTime($end_time);
            $interval = $startTimeObj->diff($endTimeObj);
            $durationInHours = $interval->h + ($interval->i / 60);
            $stmtFunction = $pdo->prepare("SELECT hourly_rate FROM job_functions WHERE id = ?");
            $stmtFunction->execute([$job_function_id]);
            $hourlyRate = (float) $stmtFunction->fetchColumn();
            if (!$hourlyRate) throw new Exception('Função de trabalho inválida.');
            $valorBase = $durationInHours * $hourlyRate;
            $settings = $pdo->query("SELECT setting_key, setting_value FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
            $taxa_servico = (float)($settings['taxa_servico_fixa'] ?? 5.00);
            $bonus_feriado = (float)($settings['bonus_feriado'] ?? 25.00);
            $operator_payment = $valorBase;
            if ($is_holiday) $operator_payment += $bonus_feriado;
            $company_cost = $operator_payment + $taxa_servico;
            $sql = "UPDATE shifts SET store_id = ?, job_function_id = ?, shift_date = ?, start_time = ?, end_time = ?, num_positions = ?, is_holiday = ?, operator_payment = ?, company_cost = ? WHERE id = ? AND company_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$store_id, $job_function_id, $shift_date, $start_time, $end_time, $num_positions, $is_holiday, $operator_payment, $company_cost, $shiftId, $_SESSION['company_id']]);
            flash('Vaga atualizada com sucesso!');
            header('Location: /painel/vagas');
            exit();
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public function cancelVaga()
    {
        $this->checkAccess(['gerente']);
        verify_csrf_token();
        $shiftId = $_POST['id'] ?? null;
        if (!$shiftId) throw new Exception('ID da vaga não fornecido.');
        $pdo = Connection::getPdo();
        try {
            $stmtShiftDetails = $pdo->prepare("SELECT store_id, shift_date FROM shifts WHERE id = ? AND company_id = ?");
            $stmtShiftDetails->execute([$shiftId, $_SESSION['company_id']]);
            $shiftDetails = $stmtShiftDetails->fetch();
            if (!$shiftDetails) throw new Exception('Vaga não encontrada ou acesso não permitido.');
            $redirectUrl = "/painel/vagas/dia?store_id={$shiftDetails['store_id']}&date={$shiftDetails['shift_date']}";
            $pdo->beginTransaction();
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM shift_applications WHERE shift_id = ? AND status = 'aprovado'");
            $stmtCheck->execute([$shiftId]);
            if ($stmtCheck->fetchColumn() > 0) {
                $pdo->rollBack();
                flash('Esta vaga não pode ser cancelada porque já existem operadores aprovados.', 'error');
                header("Location: {$redirectUrl}");
                exit();
            }
            $stmtReject = $pdo->prepare("UPDATE shift_applications SET status = 'rejeitado' WHERE shift_id = ? AND status = 'pendente'");
            $stmtReject->execute([$shiftId]);
            $stmtUpdate = $pdo->prepare("UPDATE shifts SET status = 'cancelada' WHERE id = ? AND company_id = ?");
            $stmtUpdate->execute([$shiftId, $_SESSION['company_id']]);
            $pdo->commit();
            flash('Vaga cancelada com sucesso!');
            header("Location: {$redirectUrl}");
            exit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    public function updateApplicationStatus()
    {
        $this->checkAccess();
        verify_csrf_token();
        $applicationId = $_POST['application_id'] ?? null;
        $action = $_POST['action'] ?? '';
        if (!$applicationId || !in_array($action, ['check_in', 'no_show'])) {
            throw new Exception('Ação inválida ou ID da candidatura em falta.');
        }
        try {
            $pdo = Connection::getPdo();
            $pdo->beginTransaction();
            $stmtApp = $pdo->prepare("SELECT sa.id, sa.operator_id, sa.shift_id FROM shift_applications sa JOIN shifts s ON sa.shift_id = s.id WHERE sa.id = ? AND s.company_id = ?");
            $stmtApp->execute([$applicationId, $_SESSION['company_id']]);
            $application = $stmtApp->fetch();
            if (!$application) throw new Exception('Candidatura não encontrada ou acesso não permitido.');
            $newStatus = ($action === 'check_in') ? 'check_in' : 'no_show';
            if ($newStatus === 'no_show') {
                $suspensionEndDate = (new DateTime('now', new DateTimeZone('America/Sao_Paulo')))->modify('+15 days')->format('Y-m-d');
                $stmtSuspend = $pdo->prepare("UPDATE operators SET suspended_until = ?, suspension_reason = ? WHERE id = ?");
                $stmtSuspend->execute([$suspensionEndDate, "Falta não justificada no turno #{$application['shift_id']}", $application['operator_id']]);
            }
            $stmtUpdate = $pdo->prepare("UPDATE shift_applications SET status = ? WHERE id = ?");
            $stmtUpdate->execute([$newStatus, $applicationId]);
            $pdo->commit();
            flash('Status do operador atualizado com sucesso!');
            header('Location: /painel/vagas/candidatos?shift_id=' . $application['shift_id']);
            exit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    public function processShiftCompletion()
    {
        $this->checkAccess();
        verify_csrf_token();
        $applicationId = $_POST['application_id'] ?? null;
        $rating = $_POST['rating'] ?? null;
        $cashDiscrepancy = $_POST['cash_discrepancy'] ?? 0;
        $comment = $_POST['comment'] ?? '';
        if (!$applicationId || !$rating) throw new Exception('Dados da avaliação em falta.');
        try {
            $pdo = Connection::getPdo();
            $pdo->beginTransaction();
            $stmtApp = $pdo->prepare("SELECT sa.id, sa.operator_id, sa.shift_id, s.operator_payment FROM shift_applications sa JOIN shifts s ON sa.shift_id = s.id WHERE sa.id = ? AND s.company_id = ?");
            $stmtApp->execute([$applicationId, $_SESSION['company_id']]);
            $application = $stmtApp->fetch();
            if (!$application) throw new Exception('Candidatura não encontrada ou acesso não permitido.');
            $operatorId = $application['operator_id'];
            $shiftId = $application['shift_id'];
            $stmtRating = $pdo->prepare("INSERT INTO shift_ratings (shift_id, application_id, rated_by_user_id, rated_operator_id, rating_for_operator, comment_for_operator) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtRating->execute([$shiftId, $applicationId, $_SESSION['user_id'], $operatorId, $rating, $comment]);
            $settings = $pdo->query("SELECT setting_key, setting_value FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
            $taxaFixa = (float)($settings['taxa_servico_fixa'] ?? 5.00);
            $discrepancyValue = (float)str_replace(',', '.', $cashDiscrepancy);
            $finalOperatorPayment = (float)$application['operator_payment'] - $discrepancyValue;
            $finalCompanyCost = $finalOperatorPayment + $taxaFixa;
            $stmtUpdateApp = $pdo->prepare("UPDATE shift_applications SET status = 'concluido', cash_discrepancy = ?, final_operator_payment = ?, final_company_cost = ? WHERE id = ?");
            $stmtUpdateApp->execute([$discrepancyValue, $finalOperatorPayment, $finalCompanyCost, $applicationId]);
            $stmtAvg = $pdo->prepare("SELECT AVG(rating_for_operator) FROM shift_ratings WHERE rated_operator_id = ?");
            $stmtAvg->execute([$operatorId]);
            if ($newAverageRating = $stmtAvg->fetchColumn()) {
                $stmtOpUpdate = $pdo->prepare("UPDATE operators SET pontuacao = ? WHERE id = ?");
                $stmtOpUpdate->execute([$newAverageRating, $operatorId]);
            }
            $pdo->commit();
            flash('Turno finalizado e operador avaliado com sucesso!');
            header('Location: /painel/vagas/candidatos?shift_id=' . $shiftId);
            exit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    public function listTrainingRequests()
    {
        $this->checkAccess(['gerente']);
        try {
            $pdo = Connection::getPdo();
            $stmt = $pdo->prepare("SELECT tr.id as training_id, tr.status as training_status, tr.request_date as requested_at, tr.scheduled_date as training_date, tr.scheduled_slot as training_slot, o.name as operator_name, o.phone as operator_phone, o.email as operator_email, s.name as store_name, erp.name as erp_name FROM training_requests tr JOIN operators o ON tr.operator_id = o.id JOIN stores s ON tr.store_id = s.id JOIN erp_systems erp ON tr.erp_system_id = erp.id WHERE s.company_id = ? ORDER BY tr.request_date DESC");
            $stmt->execute([$_SESSION['company_id']]);
            $requests = $stmt->fetchAll();
            $this->view('empresa/treinamentos/listar', compact('requests'));
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public function processTrainingRequest()
    {
        $this->checkAccess(['gerente']);
        verify_csrf_token();
        $trainingId = $_POST['id'] ?? null;
        $action = $_POST['action'] ?? '';
        if (!$trainingId || !in_array($action, ['approve', 'reject'])) throw new Exception('Ação inválida ou ID do treinamento em falta.');
        try {
            $pdo = Connection::getPdo();
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT t.id, t.operator_id, t.store_id, o.name as operator_name, o.email as operator_email, s.erp_system_id FROM training_requests t JOIN stores s ON t.store_id = s.id JOIN operators o ON t.operator_id = o.id WHERE t.id = ? AND s.company_id = ? AND t.status IN ('solicitado', 'agendado')");
            $stmt->execute([$trainingId, $_SESSION['company_id']]);
            $training = $stmt->fetch();
            if (!$training) throw new Exception('Solicitação de treinamento não encontrada ou já processada.');
            if ($action === 'approve') {
                $stmtQualify = $pdo->prepare("INSERT INTO operator_qualifications (operator_id, erp_system_id, approved_by_user_id, store_id) VALUES (?, ?, ?, ?)");
                $stmtQualify->execute([$training['operator_id'], $training['erp_system_id'], $_SESSION['user_id'], $training['store_id']]);
                $stmtActivate = $pdo->prepare("UPDATE operators SET status = 'ativo' WHERE id = ?");
                $stmtActivate->execute([$training['operator_id']]);
                $stmtTraining = $pdo->prepare("UPDATE training_requests SET status = 'concluido_aprovado' WHERE id = ?");
                $stmtTraining->execute([$trainingId]);
                Email::sendEmail($training['operator_email'], $training['operator_name'], "Parabéns! Você foi aprovado no treinamento!", "<p>O seu treinamento foi aprovado. Você já está qualificado e pode começar a se candidatar às vagas.</p>");
            } else {
                $stmtTraining = $pdo->prepare("UPDATE training_requests SET status = 'concluido_reprovado' WHERE id = ?");
                $stmtTraining->execute([$trainingId]);
                Email::sendEmail($training['operator_email'], $training['operator_name'], "Resultado do seu treinamento", "<p>Infelizmente, desta vez não foi possível aprovar a sua qualificação.</p>");
            }
            $pdo->commit();
            flash('Solicitação de treinamento processada com sucesso!');
            header('Location: /painel/treinamentos');
            exit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    public function showShiftTemplates()
    {
        $this->checkAccess(['gerente']);
        try {
            $pdo = Connection::getPdo();
            $stmtStores = $pdo->prepare("SELECT id, name FROM stores WHERE company_id = ? AND status = 1");
            $stmtStores->execute([$_SESSION['company_id']]);
            $stores = $stmtStores->fetchAll();
            $jobFunctions = $pdo->query("SELECT id, name FROM job_functions WHERE status = 1 ORDER BY name ASC")->fetchAll();
            $stmtTemplates = $pdo->prepare("SELECT st.*, s.name as store_name, jf.name as function_name FROM shift_templates st JOIN stores s ON st.store_id = s.id LEFT JOIN job_functions jf ON st.job_function_id = jf.id WHERE s.company_id = ? ORDER BY s.name, st.start_time");
            $stmtTemplates->execute([$_SESSION['company_id']]);
            $templates = $stmtTemplates->fetchAll();
            $this->view('empresa/vagas/gerir_templates', compact('stores', 'templates', 'jobFunctions'));
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public function storeShiftTemplate()
    {
        $this->checkAccess(['gerente']);
        verify_csrf_token();
        $store_id = $_POST['store_id'] ?? null;
        $job_function_id = $_POST['job_function_id'] ?? null;
        $start_time = $_POST['start_time'] ?? '';
        $end_time = $_POST['end_time'] ?? '';
        if (!$store_id || !$job_function_id || !$start_time || !$end_time) throw new Exception('Todos os campos são obrigatórios.');
        try {
            $pdo = Connection::getPdo();
            $stmt = $pdo->prepare("INSERT INTO shift_templates (store_id, job_function_id, start_time, end_time) VALUES (?, ?, ?, ?)");
            $stmt->execute([$store_id, $job_function_id, $start_time, $end_time]);
            flash('Template de turno criado com sucesso!');
            header('Location: /painel/vagas/templates');
            exit();
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public function deleteShiftTemplate()
    {
        $this->checkAccess(['gerente']);
        verify_csrf_token();
        $template_id = $_POST['id'] ?? null;
        if (!$template_id) throw new Exception('ID do template não fornecido.');
        try {
            $pdo = Connection::getPdo();
            $stmt = $pdo->prepare("SELECT st.id FROM shift_templates st JOIN stores s ON st.store_id = s.id WHERE st.id = ? AND s.company_id = ?");
            $stmt->execute([$template_id, $_SESSION['company_id']]);
            if (!$stmt->fetch()) throw new Exception('Template não encontrado ou acesso não permitido.');
            $stmtDelete = $pdo->prepare("DELETE FROM shift_templates WHERE id = ?");
            $stmtDelete->execute([$template_id]);
            flash('Template de turno apagado com sucesso!');
            header('Location: /painel/vagas/templates');
            exit();
        } catch (PDOException $e) {
            flash('Não foi possível apagar o template. Verifique se ele não está em uso.', 'error');
            header('Location: /painel/vagas/templates');
            exit();
        }
    }
    
    public function showPlanner()
    {
        $this->checkAccess(['gerente']);
        try {
            $pdo = Connection::getPdo();
            $stmtStores = $pdo->prepare("SELECT id, name FROM stores WHERE company_id = ? AND status = 1");
            $stmtStores->execute([$_SESSION['company_id']]);
            $stores = $stmtStores->fetchAll();
            $stmtTemplates = $pdo->prepare("SELECT st.*, s.name as store_name, jf.name as function_name FROM shift_templates st JOIN stores s ON st.store_id = s.id LEFT JOIN job_functions jf ON st.job_function_id = jf.id WHERE s.company_id = ? AND st.is_active = 1 ORDER BY s.name, st.start_time");
            $stmtTemplates->execute([$_SESSION['company_id']]);
            $templates = $stmtTemplates->fetchAll();
            $this->view('empresa/vagas/planear', compact('stores', 'templates'));
        } catch (PDOException $e) {
            throw $e;
        }
    }

/**
     * Guarda um planeamento semanal de vagas.
     * VERSÃO FINAL CORRIGIDA: Contém a lógica de extração de IDs e inserção no banco de dados corrigida.
     */
    public function storeWeeklyPlan()
    {
        $this->checkAccess(['gerente']);
        verify_csrf_token();

        $store_id = $_POST['store_id'] ?? null;
        $planJson = $_POST['weekly_plan_data'] ?? null;
        $plan = json_decode($planJson, true);

        if (empty($store_id) || empty($plan)) {
            flash('Nenhuma vaga a publicar. Por favor, adicione turnos aos dias antes de submeter.', 'error');
            header('Location: /painel/vagas/planear');
            exit();
        }

        try {
            $pdo = Connection::getPdo();
            $pdo->beginTransaction();

            // 1. Busca as configurações globais (taxas, bónus)
            $stmtSettings = $pdo->query("SELECT setting_key, setting_value FROM settings");
            $settings = $stmtSettings->fetchAll(PDO::FETCH_KEY_PAIR);
            $taxa_servico = (float)($settings['taxa_servico_fixa'] ?? 5.00);
            $bonus_feriado = (float)($settings['bonus_feriado'] ?? 25.00);

            // 2. Extrai todos os IDs de template do plano de forma robusta
            $templateIds = [];
            foreach ($plan as $date => $shiftsOnDate) {
                foreach ($shiftsOnDate as $shiftInfo) {
                    $templateIds[] = $shiftInfo['templateId'];
                }
            }
            $templateIds = array_unique($templateIds);

            if (empty($templateIds)) {
                $pdo->rollBack();
                flash('Nenhuma vaga a publicar.', 'error');
                header('Location: /painel/vagas/planear');
                exit();
            }

            // --- CORREÇÃO PRINCIPAL AQUI ---
            // Re-indexa o array para garantir chaves sequenciais (0, 1, 2...) e resolver o erro do PDO.
            $templateIds = array_values($templateIds);
            
            // 3. Busca os detalhes de todos os templates que serão usados
            $placeholders = implode(',', array_fill(0, count($templateIds), '?'));
            $stmtTemplates = $pdo->prepare("
                SELECT t.*, jf.hourly_rate 
                FROM shift_templates t 
                LEFT JOIN job_functions jf ON t.job_function_id = jf.id 
                WHERE t.id IN ($placeholders)
            ");
            $stmtTemplates->execute($templateIds);
            $templatesData = $stmtTemplates->fetchAll(PDO::FETCH_ASSOC);

            $templates = [];
            foreach ($templatesData as $template) {
                $templates[$template['id']] = $template;
            }

            // 4. Itera sobre o plano e insere cada vaga no banco de dados
            foreach ($plan as $date => $shiftsOnDate) {
                foreach ($shiftsOnDate as $shiftInfo) {
                    $templateId = $shiftInfo['templateId'];
                    $num_positions = $shiftInfo['numPositions'];

                    if ((int)$num_positions <= 0 || !isset($templates[$templateId])) {
                        continue;
                    }

                    $template = $templates[$templateId];
                    
                    $startTimeObj = new DateTime($template['start_time']);
                    $endTimeObj = new DateTime($template['end_time']);
                    $interval = $startTimeObj->diff($endTimeObj);
                    $durationInHours = $interval->h + ($interval->i / 60);
                    $valorBase = $durationInHours * (float)($template['hourly_rate'] ?? 0);

                    $operator_payment = $valorBase;
                    $company_cost = $operator_payment + $taxa_servico;

                    $sql = "INSERT INTO shifts (company_id, store_id, created_by_user_id, job_function_id, shift_date, start_time, end_time, operator_payment, company_cost, num_positions, is_holiday) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $_SESSION['company_id'], $store_id, $_SESSION['user_id'],
                        $template['job_function_id'], $date, $template['start_time'], $template['end_time'],
                        $operator_payment, $company_cost, $num_positions, 0
                    ]);
                }
            }

            $pdo->commit();
            flash('Planeamento semanal publicado com sucesso!');
            header('Location: /painel/vagas/planear');
            exit();

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}

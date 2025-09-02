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
use Throwable;

class PainelEmpresaController extends BaseController
{
    /**
     * Verifica se o usuário tem acesso à funcionalidade.
     */
    protected function checkAccess(array $roles = ['gerente', 'administrador', 'recepcionista'])
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
            $companyId = $_SESSION['company_id'];
            $stmtShift = $pdo->prepare("SELECT s.*, jf.name as title FROM shifts s LEFT JOIN job_functions jf ON s.job_function_id = jf.id WHERE s.id = ? AND s.company_id = ?");
            $stmtShift->execute([$shiftId, $companyId]);
            $shift = $stmtShift->fetch();
            if (!$shift) throw new Exception('Vaga não encontrada ou acesso não permitido.');
            $stmtApplicants = $pdo->prepare("SELECT o.id, o.name, o.pontuacao, o.path_selfie, sa.status as application_status, sa.id as application_id, sa.check_in_time FROM shift_applications sa JOIN operators o ON sa.operator_id = o.id WHERE sa.shift_id = ? ORDER BY sa.status ASC, sa.applied_at ASC");
            $stmtApplicants->execute([$shiftId]);
            $allApplicants = $stmtApplicants->fetchAll();
            $stmtBlocked = $pdo->prepare("SELECT operator_id FROM company_operator_blocks WHERE company_id = ?");
            $stmtBlocked->execute([$companyId]);
            $blockedOperatorIds = $stmtBlocked->fetchAll(PDO::FETCH_COLUMN, 0);
            $this->view('empresa/vagas/candidatos', compact('shift', 'allApplicants', 'blockedOperatorIds'));
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
            $jobFunctions = $pdo->query("SELECT id, name, hourly_rate FROM job_functions WHERE status = 1 ORDER BY name ASC")->fetchAll();
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
            
            // CORREÇÃO: Lógica de cálculo de duração correta
            $startTimeObj = new DateTime($shift_date . ' ' . $start_time);
            $endTimeObj = new DateTime($shift_date . ' ' . $end_time);
            if ($endTimeObj <= $startTimeObj) {
                $endTimeObj->modify('+1 day');
            }
            $interval = $startTimeObj->diff($endTimeObj);
            $durationInHours = ($interval->days * 24) + $interval->h + ($interval->i / 60);
            
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
        } catch (Exception $e) {
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
            $jobFunctions = $pdo->query("SELECT id, name, hourly_rate FROM job_functions WHERE status = 1 ORDER BY name ASC")->fetchAll();
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
            
            // CORREÇÃO: Lógica de cálculo de duração correta
            $startTimeObj = new DateTime($shift_date . ' ' . $start_time);
            $endTimeObj = new DateTime($shift_date . ' ' . $end_time);
            if ($endTimeObj <= $startTimeObj) {
                $endTimeObj->modify('+1 day');
            }
            $interval = $startTimeObj->diff($endTimeObj);
            $durationInHours = ($interval->days * 24) + $interval->h + ($interval->i / 60);

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
        } catch (Exception $e) {
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
        $pdo = Connection::getPdo();
        try {
            $pdo->beginTransaction();
            $stmtApp = $pdo->prepare("
                SELECT sa.id, sa.operator_id, sa.shift_id, s.shift_date 
                FROM shift_applications sa 
                JOIN shifts s ON sa.shift_id = s.id 
                WHERE sa.id = ? AND s.company_id = ?
            ");
            $stmtApp->execute([$applicationId, $_SESSION['company_id']]);
            $application = $stmtApp->fetch();
            if (!$application) throw new Exception('Candidatura não encontrada ou acesso não permitido.');
            $newStatus = ($action === 'check_in') ? 'check_in' : 'no_show';
            if ($newStatus === 'check_in') {
                $checkInTimeStr = $_POST['check_in_time'] ?? null;
                if (!$checkInTimeStr) {
                    throw new Exception('O horário de check-in é obrigatório.');
                }
                $checkinDateTime = new DateTime($application['shift_date'] . ' ' . $checkInTimeStr, new DateTimeZone('America/Sao_Paulo'));
                $stmtUpdate = $pdo->prepare("UPDATE shift_applications SET status = ?, check_in_time = ? WHERE id = ?");
                $stmtUpdate->execute([$newStatus, $checkinDateTime->format('Y-m-d H:i:s'), $applicationId]);
            } else {
                $stmtUpdate = $pdo->prepare("UPDATE shift_applications SET status = ? WHERE id = ?");
                $stmtUpdate->execute([$newStatus, $applicationId]);
                $suspensionEndDate = (new DateTime('now', new DateTimeZone('America/Sao_Paulo')))->modify('+15 days')->format('Y-m-d');
                $stmtSuspend = $pdo->prepare("UPDATE operators SET suspended_until = ?, suspension_reason = ? WHERE id = ?");
                $stmtSuspend->execute([$suspensionEndDate, "Falta não justificada no turno #{$application['shift_id']}", $application['operator_id']]);
            }
            $pdo->commit();
            flash('Status do operador atualizado com sucesso!');
            header('Location: /painel/vagas/candidatos?shift_id=' . $application['shift_id']);
            exit();
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

public function processShiftCompletion()
{
    $this->checkAccess();
    verify_csrf_token();

    $applicationId   = $_POST['application_id'] ?? null;
    $rating          = $_POST['rating'] ?? null;
    $cashDiscrepancy = $_POST['cash_discrepancy'] ?? '0';
    $comment         = $_POST['comment'] ?? '';
    $checkOutTimeStr = $_POST['check_out_time'] ?? null;

    if (!$applicationId || !$rating || !$checkOutTimeStr) {
        throw new Exception('Dados da avaliação ou horário de saída em falta.');
    }

    $pdo = Connection::getPdo();

    try {
        $pdo->beginTransaction();

        // Busca dados da aplicação
        $stmtApp = $pdo->prepare("
            SELECT sa.id, sa.operator_id, sa.shift_id, sa.check_in_time, s.shift_date, jf.hourly_rate
            FROM shift_applications sa
            JOIN shifts s ON sa.shift_id = s.id
            LEFT JOIN job_functions jf ON s.job_function_id = jf.id
            WHERE sa.id = ? AND s.company_id = ?
        ");
        $stmtApp->execute([$applicationId, $_SESSION['company_id']]);
        $application = $stmtApp->fetch();

        if (!$application) {
            throw new Exception('Candidatura não encontrada ou acesso não permitido.');
        }
        if (empty($application['check_in_time'])) {
            throw new Exception('Não é possível finalizar um turno sem antes fazer o check-in do operador.');
        }

        // Cria objetos de data/hora
        $shiftDate    = $application['shift_date'];
        $checkinTime  = new DateTime($application['check_in_time'], new DateTimeZone('America/Sao_Paulo'));
        $checkoutTime = new DateTime($shiftDate . ' ' . $checkOutTimeStr, new DateTimeZone('America/Sao_Paulo'));

        // Se a saída for igual ou anterior ao check-in, considera que passou da meia-noite
        if ($checkoutTime <= $checkinTime) {
            $checkoutTime->modify('+1 day');
        }

        // Atualiza hora de saída no banco
        $stmtSetTimes = $pdo->prepare("UPDATE shift_applications SET check_out_time = ? WHERE id = ?");
        $stmtSetTimes->execute([$checkoutTime->format('Y-m-d H:i:s'), $applicationId]);

        // Cálculo de horas trabalhadas
        $interval = $checkinTime->diff($checkoutTime);
        $durationInHours = round(($interval->days * 24) + $interval->h + ($interval->i / 60), 2);

        // Garante valores válidos
        if ($durationInHours < 0) {
            $durationInHours = 0;
        }

        // 🔒 Pega limite configurado no banco (default 7h se não existir)
        $settings   = $pdo->query("SELECT setting_key, setting_value FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
        $limiteHoras = (float)($settings['limite_horas_turno'] ?? 7);

        if ($durationInHours > $limiteHoras) {
            $durationInHours = $limiteHoras;
        }

        // Cálculo do pagamento
        $hourlyRate = (float) ($application['hourly_rate'] ?? 10.00);
        $valorBase  = $durationInHours * $hourlyRate;

        $taxaFixa   = (float)($settings['taxa_servico_fixa'] ?? 5.00);

        $discrepancyValue      = (float) str_replace([','], ['.'], $cashDiscrepancy);
        $finalOperatorPayment  = $valorBase - $discrepancyValue;
        $finalCompanyCost      = $finalOperatorPayment + $taxaFixa;

        // Atualiza carteira do operador
        $stmtWallet = $pdo->prepare("SELECT id FROM operator_wallets WHERE operator_id = ? FOR UPDATE");
        $stmtWallet->execute([$application['operator_id']]);
        $wallet = $stmtWallet->fetch();

        $walletId = $wallet ? $wallet['id'] : null;
        if (!$walletId) {
            $pdo->prepare("INSERT INTO operator_wallets (operator_id) VALUES (?)")->execute([$application['operator_id']]);
            $walletId = $pdo->lastInsertId();
        }

        if ($finalOperatorPayment > 0) {
            $stmtCredit = $pdo->prepare("UPDATE operator_wallets SET balance = balance + ? WHERE id = ?");
            $stmtCredit->execute([$finalOperatorPayment, $walletId]);
        }

        $description = "Crédito do turno #" . $application['shift_id'];
        $originRef   = "app_id:" . $applicationId;
        $stmtTx = $pdo->prepare("
            INSERT INTO wallet_transactions (wallet_id, type, amount, description, origin_ref) 
            VALUES (?, 'credit', ?, ?, ?)
        ");
        $stmtTx->execute([$walletId, $finalOperatorPayment, $description, $originRef]);

        // Avaliação do operador
        $stmtCheckRating = $pdo->prepare("SELECT id FROM shift_ratings WHERE application_id = ?");
        $stmtCheckRating->execute([$applicationId]);
        $existingRating = $stmtCheckRating->fetch();

        if ($existingRating) {
            $stmtRating = $pdo->prepare("
                UPDATE shift_ratings 
                SET rated_by_user_id = ?, rated_operator_id = ?, rating_for_operator = ?, comment_for_operator = ? 
                WHERE id = ?
            ");
            $stmtRating->execute([$_SESSION['user_id'], $application['operator_id'], $rating, $comment, $existingRating['id']]);
        } else {
            $stmtRating = $pdo->prepare("
                INSERT INTO shift_ratings 
                (shift_id, application_id, rated_by_user_id, rated_operator_id, rating_for_operator, comment_for_operator) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmtRating->execute([$application['shift_id'], $applicationId, $_SESSION['user_id'], $application['operator_id'], $rating, $comment]);
        }

        // Atualiza aplicação
        $stmtUpdateApp = $pdo->prepare("
            UPDATE shift_applications 
            SET status = 'concluido', cash_discrepancy = ?, final_operator_payment = ?, final_company_cost = ? 
            WHERE id = ?
        ");
        $stmtUpdateApp->execute([$discrepancyValue, $finalOperatorPayment, $finalCompanyCost, $applicationId]);

        // Recalcula média de avaliações do operador
        $stmtAvg = $pdo->prepare("SELECT AVG(rating_for_operator) FROM shift_ratings WHERE rated_operator_id = ?");
        $stmtAvg->execute([$application['operator_id']]);
        if ($newAverageRating = $stmtAvg->fetchColumn()) {
            $stmtOpUpdate = $pdo->prepare("UPDATE operators SET pontuacao = ? WHERE id = ?");
            $stmtOpUpdate->execute([$newAverageRating, $application['operator_id']]);
        }

        $pdo->commit();

        flash('Turno finalizado e operador avaliado com sucesso! O valor foi creditado na carteira.', 'success');
        header('Location: /painel/vagas/candidatos?shift_id=' . $application['shift_id']);
        exit();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
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

    public function showQualifiedOperators()
    {
        $this->checkAccess();
        try {
            $pdo = Connection::getPdo();
            $companyId = $_SESSION['company_id'];
            $stmt = $pdo->prepare("
            SELECT DISTINCT o.id, o.name, o.email, o.phone, o.pontuacao, o.status, o.path_selfie_thumb
            FROM operators o
            JOIN operator_qualifications oq ON o.id = oq.operator_id
            WHERE oq.erp_system_id IN (
                SELECT DISTINCT s.erp_system_id
                FROM stores s
                WHERE s.company_id = ?
            )
            AND o.status = 'ativo'
            ORDER BY o.name ASC
        ");
            $stmt->execute([$companyId]);
            $operators = $stmt->fetchAll();
            $stmtBlocked = $pdo->prepare("SELECT operator_id FROM company_operator_blocks WHERE company_id = ?");
            $stmtBlocked->execute([$companyId]);
            $blockedOperatorIds = $stmtBlocked->fetchAll(PDO::FETCH_COLUMN, 0);
            $this->view('empresa/operadores/listar', compact('operators', 'blockedOperatorIds'));
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
            $stmtSettings = $pdo->query("SELECT setting_key, setting_value FROM settings");
            $settings = $stmtSettings->fetchAll(PDO::FETCH_KEY_PAIR);
            $taxa_servico = (float)($settings['taxa_servico_fixa'] ?? 5.00);
            $bonus_feriado = (float)($settings['bonus_feriado'] ?? 25.00);
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
            $templateIds = array_values($templateIds);
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
                    if ($endTimeObj <= $startTimeObj) {
                        $endTimeObj->modify('+1 day');
                    }
                    $interval = $startTimeObj->diff($endTimeObj);
                    $durationInHours = ($interval->days * 24) + $interval->h + ($interval->i / 60);
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
    
    public function showReceivePaymentForm()
    {
        $this->checkAccess(['gerente', 'administrador', 'recepcionista']);
        $this->view('empresa/pagamentos/receber');
    }
	
	public function showCompanyReceipt()
{
    $this->checkAccess();
    $applicationId = $_GET['app_id'] ?? null;
    if (!$applicationId) {
        throw new Exception('ID da aplicação não fornecido.');
    }

    try {
        $pdo = Connection::getPdo();
        $companyId = $_SESSION['company_id'];

        // Query para buscar todos os detalhes do turno concluído
        $stmt = $pdo->prepare("
            SELECT 
                s.shift_date, s.start_time, s.end_time,
                sa.check_in_time, sa.check_out_time, sa.cash_discrepancy, sa.final_operator_payment, sa.final_company_cost,
                o.name as operator_name, o.cpf as operator_cpf,
                st.name as store_name,
                jf.name as job_function_name, jf.hourly_rate
            FROM shift_applications sa
            JOIN shifts s ON sa.shift_id = s.id
            JOIN operators o ON sa.operator_id = o.id
            JOIN stores st ON s.store_id = st.id
            LEFT JOIN job_functions jf ON s.job_function_id = jf.id
            WHERE sa.id = ? AND s.company_id = ? AND sa.status = 'concluido'
        ");
        $stmt->execute([$applicationId, $companyId]);
        $receiptData = $stmt->fetch();

        if (!$receiptData) {
            throw new Exception('Comprovante não encontrado ou acesso não permitido.');
        }

        $this->view('empresa/vagas/comprovante', ['receipt' => $receiptData]);

    } catch (Throwable $e) {
        throw $e;
    }
}

}
    

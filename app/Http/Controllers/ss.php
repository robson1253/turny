<?php

namespace App\Http\Controllers;

use App\Database\Connection;
use App\Utils\Email;
use PDO;
use PDOException;
use DateTime;
use DateTimeZone;
use Exception;

class PainelOperadorController extends BaseController
{
    /**
     * Helper para verificar se o operador está logado.
     */
    private function checkAccess()
    {
        if (!isset($_SESSION['operator_id'])) {
            header('Location: /login');
            exit();
        }
    }

    /**
     * Mostra o dashboard principal do operador (mural de vagas).
     */
    public function index()
    {
        $this->checkAccess();
        try {
            $pdo = Connection::getPdo();
            $operatorId = $_SESSION['operator_id'];
            
            // --- INÍCIO DA CORREÇÃO NA QUERY SQL ---
            // A verificação de qualificação de função (s.job_function_id IN ...) foi removida temporariamente
            // para garantir que os operadores existentes continuem a ver as vagas de Operador de Caixa.
            // O LEFT JOIN foi mantido para que o título da vaga seja o nome da função.
            $stmt = $pdo->prepare("
                SELECT 
                    s.id, COALESCE(jf.name, s.title) as title, s.shift_date, s.start_time, s.end_time, s.operator_payment as value,
                    c.nome_fantasia as company_name,
                    st.name as store_name, st.cidade, st.estado
                FROM shifts s
                JOIN companies c ON s.company_id = c.id
                JOIN stores st ON s.store_id = st.id
                LEFT JOIN job_functions jf ON s.job_function_id = jf.id
                WHERE s.status = 'aberta' AND c.status = 1 AND st.status = 1
                  AND st.erp_system_id IN (SELECT oq.erp_system_id FROM operator_qualifications oq WHERE oq.operator_id = :operator_id)
                  AND s.id NOT IN (SELECT sa.shift_id FROM shift_applications sa WHERE sa.operator_id = :operator_id)
                ORDER BY s.shift_date ASC, s.start_time ASC
            ");
            // --- FIM DA CORREÇÃO NA QUERY SQL ---

            $stmt->execute(['operator_id' => $operatorId]);
            $vagasAbertas = $stmt->fetchAll();

            $stmtOffers = $pdo->prepare("SELECT COUNT(*) FROM shift_transfers WHERE to_operator_id = ? AND status = 'pendente'");
            $stmtOffers->execute([$operatorId]);
            $pendingOffers = $stmtOffers->fetchColumn();

            $this->view('operador/dashboard', compact('vagasAbertas', 'pendingOffers'));

        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * Mostra a Central de Qualificações do operador.
     */
    public function showQualificationsPage()
    {
        $this->checkAccess();
        try {
            $pdo = Connection::getPdo();
            $operatorId = $_SESSION['operator_id'];
            
            $stmtQualified = $pdo->prepare("SELECT es.id, es.name FROM erp_systems es JOIN operator_qualifications oq ON es.id = oq.erp_system_id WHERE oq.operator_id = ?");
            $stmtQualified->execute([$operatorId]);
            $myErpQualifications = $stmtQualified->fetchAll();

            $stmtJobQualified = $pdo->prepare("SELECT jf.id, jf.name FROM job_functions jf JOIN operator_job_qualifications ojq ON jf.id = ojq.job_function_id WHERE ojq.operator_id = ?");
            $stmtJobQualified->execute([$operatorId]);
            $myJobQualifications = $stmtJobQualified->fetchAll();

            $stmtRequested = $pdo->prepare("SELECT erp_system_id FROM training_requests WHERE operator_id = ? AND status IN ('solicitado', 'agendado')");
            $stmtRequested->execute([$operatorId]);
            $requestedErpIds = $stmtRequested->fetchAll(PDO::FETCH_COLUMN, 0);
            
            $stmtAllErps = $pdo->query("SELECT * FROM erp_systems ORDER BY name ASC");
            $allErpSystems = $stmtAllErps->fetchAll();
            
            $stmtOffers = $pdo->prepare("SELECT COUNT(*) FROM shift_transfers WHERE to_operator_id = ? AND status = 'pendente'");
            $stmtOffers->execute([$operatorId]);
            $pendingOffers = $stmtOffers->fetchColumn();

            $this->view('operador/qualificacoes', compact('myErpQualifications', 'myJobQualifications', 'requestedErpIds', 'allErpSystems', 'pendingOffers'));
        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * Regista o agendamento de um treinamento para um operador.
     */
    public function scheduleTraining()
    {
        $this->checkAccess();
        verify_csrf_token();
        
        $erpId = $_POST['erp_system_id'] ?? null;
        $storeId = $_POST['store_id'] ?? null;
        $date = $_POST['training_date'] ?? null;
        $slot = $_POST['training_slot'] ?? null;
        $operatorId = $_SESSION['operator_id'];

        if (!$erpId || !$storeId || !$date || !$slot || !in_array($slot, ['manha', 'tarde'])) {
            throw new Exception('Dados do agendamento inválidos ou em falta.');
        }

        try {
            $pdo = Connection::getPdo();
            // ... (lógica de verificação de conflitos, como a de 24h de antecedência) ...
            
            $stmtInsert = $pdo->prepare("INSERT INTO training_requests (operator_id, erp_system_id, store_id, scheduled_date, scheduled_slot, status) VALUES (?, ?, ?, ?, ?, 'agendado')");
            $stmtInsert->execute([$operatorId, $erpId, $storeId, $date, $slot]);
            
            flash('Treinamento agendado com sucesso!');
            header('Location: /painel/operador/qualificacoes');
            exit();

        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) { // Erro de entrada duplicada
                flash('Já existe um treinamento agendado para esta loja neste horário.', 'error');
                header('Location: /painel/operador/qualificacoes');
                exit();
            }
            throw $e;
        }
    }
    
    /**
     * Processa a aceitação de uma vaga por um operador qualificado.
     */
    public function acceptShift()
    {
        $this->checkAccess();
        verify_csrf_token();
        $shiftId = $_POST['id'] ?? null;
        $operatorId = $_SESSION['operator_id'];
        if (!$shiftId) throw new Exception('ID da vaga não fornecido.');
        
        try {
            $pdo = Connection::getPdo();
            $pdo->beginTransaction();
            // ... (lógica de verificação de conflitos e qualificações) ...
            
            $stmtInsert = $pdo->prepare("INSERT INTO shift_applications (shift_id, operator_id, status) VALUES (?, ?, 'aprovado')");
            $stmtInsert->execute([$shiftId, $operatorId]);
            
            // ... (lógica para atualizar status da vaga para 'preenchida' se necessário) ...
            
            $pdo->commit();
            flash('Vaga aceite com sucesso! Pode vê-la em "Meus Turnos".');
            header('Location: /painel/operador');
            exit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Mostra a lista de turnos do operador.
     */
    public function showMyShifts()
    {
        $this->checkAccess();
        try {
            $pdo = Connection::getPdo();
            $operatorId = $_SESSION['operator_id'];
            $stmt = $pdo->prepare("
                SELECT 
                    s.id, COALESCE(jf.name, s.title) as title, s.shift_date, s.start_time, s.end_time, s.operator_payment,
                    sa.id as application_id, sa.status as application_status, sa.transferred_in,
                    sa.cash_discrepancy, sa.final_operator_payment,
                    st.name as store_name, st.cidade, st.estado,
                    c.nome_fantasia as company_name
                FROM shifts s
                JOIN shift_applications sa ON s.id = sa.shift_id
                JOIN stores st ON s.store_id = st.id
                JOIN companies c ON s.company_id = c.id
                LEFT JOIN job_functions jf ON s.job_function_id = jf.id
                WHERE sa.operator_id = ? AND sa.status IN ('aprovado', 'concluido', 'check_in', 'no_show', 'cancelado_operador')
                ORDER BY s.shift_date DESC, s.start_time ASC
            ");
            $stmt->execute([$operatorId]);
            $myShifts = $stmt->fetchAll();

            $stmtRated = $pdo->prepare("SELECT application_id FROM shift_ratings WHERE rated_operator_id = ? AND rating_for_company IS NOT NULL");
            $stmtRated->execute([$operatorId]);
            $ratedApplicationIds = $stmtRated->fetchAll(PDO::FETCH_COLUMN, 0);
            
            $stmtOffers = $pdo->prepare("SELECT COUNT(*) FROM shift_transfers WHERE to_operator_id = ? AND status = 'pendente'");
            $stmtOffers->execute([$operatorId]);
            $pendingOffers = $stmtOffers->fetchColumn();
            
            $this->view('operador/meus_turnos', compact('myShifts', 'pendingOffers', 'ratedApplicationIds'));
        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * Processa o cancelamento de uma candidatura pelo operador.
     */
    public function cancelApplication()
    {
        $this->checkAccess();
        verify_csrf_token();
        $applicationId = $_POST['application_id'] ?? null;
        $operatorId = $_SESSION['operator_id'];
        if (!$applicationId) throw new Exception('ID da candidatura não fornecido.');
        
        try {
            $pdo = Connection::getPdo();
            $pdo->beginTransaction();
            // ... (lógica de verificação de tempo e atualização de status) ...
            $pdo->commit();
            flash('Turno cancelado com sucesso.');
            header('Location: /painel/operador/meus-turnos');
            exit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    /**
     * Mostra a página de perfil do operador logado.
     */
    public function showProfile()
    {
        $this->checkAccess();
        try {
            $pdo = Connection::getPdo();
            $operatorId = $_SESSION['operator_id'];
            $stmtOperator = $pdo->prepare("SELECT * FROM operators WHERE id = ?");
            $stmtOperator->execute([$operatorId]);
            $operator = $stmtOperator->fetch();
            if (!$operator) throw new Exception('Operador não encontrado.');
            
            $stmtQualifications = $pdo->prepare("SELECT es.name FROM operator_qualifications oq JOIN erp_systems es ON oq.erp_system_id = es.id WHERE oq.operator_id = ?");
            $stmtQualifications->execute([$operatorId]);
            $qualifications = $stmtQualifications->fetchAll(PDO::FETCH_COLUMN, 0);
            
            $stmtOffers = $pdo->prepare("SELECT COUNT(*) FROM shift_transfers WHERE to_operator_id = ? AND status = 'pendente'");
            $stmtOffers->execute([$operatorId]);
            $pendingOffers = $stmtOffers->fetchColumn();
            
            $this->view('operador/perfil', compact('operator', 'qualifications', 'pendingOffers'));
        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * Inicia uma oferta de transferência de vaga para outro operador.
     */
    public function initiateTransfer()
    {
        $this->checkAccess();
        verify_csrf_token();
        $applicationId = $_POST['application_id'] ?? null;
        $toUsername = $_POST['username'] ?? '';
        $fromOperatorId = $_SESSION['operator_id'];
        if (!$applicationId || empty($toUsername)) {
            flash('Dados da transferência em falta.', 'error');
            header('Location: /painel/operador/meus-turnos');
            exit();
        }
        
        try {
            $pdo = Connection::getPdo();
            $pdo->beginTransaction();
            // ... (lógica de verificação de tempo, utilizador, qualificação e conflitos) ...
            $stmtLog = $pdo->prepare("INSERT INTO shift_transfers (shift_id, application_id, from_operator_id, to_operator_id, status) VALUES (?, ?, ?, ?, 'pendente')");
            $stmtLog->execute([$application['shift_id'], $applicationId, $fromOperatorId, $toOperator['id']]);
            $pdo->commit();
            flash('Oferta de transferência enviada com sucesso!');
            header('Location: /painel/operador/meus-turnos');
            exit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Mostra a página com as ofertas de transferência recebidas pelo operador.
     */
    public function showTransferOffers()
    {
        $this->checkAccess();
        try {
            $pdo = Connection::getPdo();
            $operatorId = $_SESSION['operator_id'];
            $stmt = $pdo->prepare("SELECT st.id as transfer_id, s.id as shift_id, jf.name as title, s.shift_date, s.start_time, s.end_time, s.operator_payment as value, c.nome_fantasia as company_name, store.name as store_name, from_op.name as from_operator_name FROM shift_transfers st JOIN shifts s ON st.shift_id = s.id JOIN stores store ON s.store_id = store.id JOIN companies c ON s.company_id = c.id JOIN operators from_op ON st.from_operator_id = from_op.id LEFT JOIN job_functions jf ON s.job_function_id = jf.id WHERE st.to_operator_id = ? AND st.status = 'pendente'");
            $stmt->execute([$operatorId]);
            $offers = $stmt->fetchAll();
            $this->view('operador/ofertas', compact('offers'));
        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * Processa a resposta (aceite/recusa) a uma oferta de transferência.
     */
    public function respondToTransfer()
    {
        $this->checkAccess();
        verify_csrf_token();
        $transferId = $_POST['transfer_id'] ?? null;
        $response = $_POST['response'] ?? '';
        $toOperatorId = $_SESSION['operator_id'];
        if (!$transferId || !in_array($response, ['aceite', 'recusada'])) {
            throw new Exception('Ação inválida ou ID da oferta em falta.');
        }
        try {
            $pdo = Connection::getPdo();
            $pdo->beginTransaction();
            // ... (lógica de verificação e atualização da transferência) ...
            $pdo->commit();
            flash('Resposta à oferta enviada com sucesso!');
            header('Location: /painel/operador/meus-turnos');
            exit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Mostra o formulário para o operador avaliar a empresa.
     */
    public function showRateCompanyForm()
    {
        $this->checkAccess();
        $applicationId = $_GET['application_id'] ?? null;
        if (!$applicationId) throw new Exception('ID da candidatura não fornecido.');
        try {
            $pdo = Connection::getPdo();
            $stmt = $pdo->prepare("SELECT sa.id as application_id, s.shift_date, c.nome_fantasia as company_name FROM shift_applications sa JOIN shifts s ON sa.shift_id = s.id JOIN companies c ON s.company_id = c.id WHERE sa.id = ? AND sa.operator_id = ?");
            $stmt->execute([$applicationId, $_SESSION['operator_id']]);
            $shiftDetails = $stmt->fetch();
            if (!$shiftDetails) throw new Exception('Turno não encontrado ou acesso não permitido.');
            $this->view('operador/avaliar', compact('shiftDetails'));
        } catch (PDOException $e) {
            throw $e;
        }
    }
    
    /**
     * Guarda a avaliação que o operador fez da empresa.
     */
    public function rateCompany()
    {
        $this->checkAccess();
        verify_csrf_token();
        $applicationId = $_POST['application_id'] ?? null;
        $rating = $_POST['rating'] ?? null;
        $comment = $_POST['comment'] ?? '';
        $operatorId = $_SESSION['operator_id'];
        if (!$applicationId || !$rating) throw new Exception('Dados da avaliação em falta.');
        try {
            $pdo = Connection::getPdo();
            $stmt = $pdo->prepare("SELECT id FROM shift_ratings WHERE application_id = ? AND rated_operator_id = ?");
            $stmt->execute([$applicationId, $operatorId]);
            $ratingRecord = $stmt->fetch();
            if (!$ratingRecord) throw new Exception('Registo de avaliação não encontrado.');
            $stmtUpdate = $pdo->prepare("UPDATE shift_ratings SET rating_for_company = ?, comment_for_company = ? WHERE id = ?");
            $stmtUpdate->execute([$rating, $comment, $ratingRecord['id']]);
            flash('Avaliação enviada com sucesso. Obrigado!');
            header('Location: /painel/operador/meus-turnos');
            exit();
        } catch (PDOException $e) {
            throw $e;
        }
    }
}


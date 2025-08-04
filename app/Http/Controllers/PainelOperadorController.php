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
            
            $now = new DateTime("now", new DateTimeZone('America/Sao_Paulo'));
            $scheduledDateTime = new DateTime($date . ' ' . (($slot === 'manha') ? '09:00:00' : '14:00:00'), new DateTimeZone('America/Sao_Paulo'));
            if (($scheduledDateTime->getTimestamp() - $now->getTimestamp()) / 3600 < 24) {
                flash('Não é possível agendar um treinamento com menos de 24 horas de antecedência.', 'error');
                header('Location: /painel/operador/qualificacoes');
                exit();
            }
            
            $stmtCheckOperator = $pdo->prepare("SELECT COUNT(*) FROM training_requests WHERE operator_id = ? AND scheduled_date = ? AND scheduled_slot = ? AND status = 'agendado'");
            $stmtCheckOperator->execute([$operatorId, $date, $slot]);
            if ($stmtCheckOperator->fetchColumn() > 0) {
                flash('Você já tem um treinamento agendado neste mesmo horário.', 'error');
                header('Location: /painel/operador/qualificacoes');
                exit();
            }
            
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
            
            $stmtNewShift = $pdo->prepare("SELECT * FROM shifts WHERE id = ? AND status = 'aberta'");
            $stmtNewShift->execute([$shiftId]);
            $newShift = $stmtNewShift->fetch();
            if (!$newShift) {
                flash('Esta vaga acabou de ser preenchida por outro operador.', 'error');
                header('Location: /painel/operador');
                exit();
            }

            $stmtConflict = $pdo->prepare("
                SELECT COUNT(*) FROM shifts s JOIN shift_applications sa ON s.id = sa.shift_id
                WHERE sa.operator_id = :operator_id AND sa.status = 'aprovado' AND s.shift_date = :shift_date AND s.start_time < :end_time AND s.end_time > :start_time
            ");
            $stmtConflict->execute([':operator_id' => $operatorId, ':shift_date' => $newShift['shift_date'], ':start_time' => $newShift['start_time'], ':end_time' => $newShift['end_time']]);
            if ($stmtConflict->fetchColumn() > 0) {
                flash('Não foi possível aceitar a vaga. O horário entra em conflito com outro turno que você já aceitou.', 'error');
                header('Location: /painel/operador');
                exit();
            }
            
            $stmtInsert = $pdo->prepare("INSERT INTO shift_applications (shift_id, operator_id, status) VALUES (?, ?, 'aprovado')");
            $stmtInsert->execute([$shiftId, $operatorId]);
            
            $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM shift_applications WHERE shift_id = ? AND status = 'aprovado'");
            $stmtCount->execute([$shiftId]);
            $approvedCount = $stmtCount->fetchColumn();
            if ($approvedCount >= $newShift['num_positions']) {
                $stmtFill = $pdo->prepare("UPDATE shifts SET status = 'preenchida' WHERE id = ?");
                $stmtFill->execute([$shiftId]);
            }

            // Busca dados para o email
            $stmtOperator = $pdo->prepare("SELECT name, email FROM operators WHERE id = ?");
            $stmtOperator->execute([$operatorId]);
            $operator = $stmtOperator->fetch();
            
            $pdo->commit();

            if ($operator) {
                $subject = "Vaga Aceita! Detalhes do seu próximo turno na TURNY.";
                $body = "<h1>Parabéns, ".htmlspecialchars($operator['name'])."!</h1><p>Você aceitou a vaga e já está confirmado! Acesse 'Meus Turnos' para ver os detalhes.</p><p><strong>Equipe TURNY</strong></p>";
                Email::sendEmail($operator['email'], $operator['name'], $subject, $body);
            }

            flash('Vaga aceita com sucesso! Pode vê-la em "Meus Turnos".');
            header('Location: /painel/operador');
            exit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }
    
    /**
     * Mostra a lista de turnos do operador, incluindo os concluídos para avaliação.
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
            $stmt = $pdo->prepare("
                SELECT sa.id, sa.shift_id, sa.operator_id, s.shift_date, s.start_time
                FROM shift_applications sa
                JOIN shifts s ON sa.shift_id = s.id
                WHERE sa.id = ?
            ");
            $stmt->execute([$applicationId]);
            $application = $stmt->fetch();
            if (!$application || $application['operator_id'] != $operatorId) throw new Exception('Acesso não permitido.');
            
            $now = new DateTime("now", new DateTimeZone('America/Sao_Paulo'));
            $shiftStart = new DateTime($application['shift_date'] . ' ' . $application['start_time'], new DateTimeZone('America/Sao_Paulo'));
            
            if ($now > $shiftStart || ($shiftStart->getTimestamp() - $now->getTimestamp()) < (12 * 3600)) {
                flash('Não é possível cancelar um turno com menos de 12 horas de antecedência.', 'error');
                header('Location: /painel/operador/meus-turnos');
                exit();
            }

            $stmtCancel = $pdo->prepare("UPDATE shift_applications SET status = 'cancelado_operador' WHERE id = ?");
            $stmtCancel->execute([$applicationId]);
            $stmtReopen = $pdo->prepare("UPDATE shifts SET status = 'aberta' WHERE id = ?");
            $stmtReopen->execute([$application['shift_id']]);
            
            $pdo->commit();
            flash('Turno cancelado com sucesso.');
            header('Location: /painel/operador/meus-turnos');
            exit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
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

            // 1. Busca os dados principais do operador
            $stmtOperator = $pdo->prepare("SELECT * FROM operators WHERE id = ?");
            $stmtOperator->execute([$operatorId]);
            $operator = $stmtOperator->fetch();
            if (!$operator) {
                throw new Exception('Operador não encontrado.');
            }

            // 2. Busca as qualificações de SISTEMA (ERP)
            $stmtErpQualifications = $pdo->prepare("
                SELECT es.name 
                FROM operator_qualifications oq
                JOIN erp_systems es ON oq.erp_system_id = es.id
                WHERE oq.operator_id = ?
            ");
            $stmtErpQualifications->execute([$operatorId]);
            $erpQualifications = $stmtErpQualifications->fetchAll(PDO::FETCH_COLUMN, 0);

            // 3. Busca as qualificações de FUNÇÃO
            $stmtJobQualifications = $pdo->prepare("
                SELECT jf.name 
                FROM operator_job_qualifications ojq
                JOIN job_functions jf ON ojq.job_function_id = jf.id
                WHERE ojq.operator_id = ?
            ");
            $stmtJobQualifications->execute([$operatorId]);
            $jobQualifications = $stmtJobQualifications->fetchAll(PDO::FETCH_COLUMN, 0);

            // 4. Busca as ofertas pendentes
            $stmtOffers = $pdo->prepare("SELECT COUNT(*) FROM shift_transfers WHERE to_operator_id = ? AND status = 'pendente'");
            $stmtOffers->execute([$operatorId]);
            $pendingOffers = $stmtOffers->fetchColumn();
            
            // 5. Envia todos os dados para a view
            $this->view('operador/perfil', compact('operator', 'erpQualifications', 'jobQualifications', 'pendingOffers'));

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

            $stmt = $pdo->prepare("SELECT sa.id as application_id, sa.shift_id, sa.operator_id, s.shift_date, s.start_time, s.end_time, st.erp_system_id FROM shift_applications sa JOIN shifts s ON sa.shift_id = s.id JOIN stores st ON s.store_id = st.id WHERE sa.id = ? AND sa.status = 'aprovado'");
            $stmt->execute([$applicationId]);
            $application = $stmt->fetch();
            if (!$application || $application['operator_id'] != $fromOperatorId) throw new Exception('Você não tem permissão para transferir esta vaga.');
            
            $stmtToOperator = $pdo->prepare("SELECT * FROM operators WHERE username = ? AND status = 'ativo'");
            $stmtToOperator->execute([$toUsername]);
            $toOperator = $stmtToOperator->fetch();
            if (!$toOperator || $toOperator['id'] == $fromOperatorId) {
                flash('Transferência falhou: O @username inserido não foi encontrado ou não está ativo.', 'error');
                header('Location: /painel/operador/meus-turnos');
                exit();
            }

            // ... (outras verificações de tempo, qualificação e conflitos) ...

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
            $stmt = $pdo->prepare("SELECT st.id as transfer_id, s.id as shift_id, jf.name as title, s.shift_date, s.start_time, s.end_time, s.operator_payment as value, c.nome_fantasia as company_name, store.name as store_name, store.cidade, store.estado, from_op.name as from_operator_name FROM shift_transfers st JOIN shifts s ON st.shift_id = s.id JOIN stores store ON s.store_id = store.id JOIN companies c ON s.company_id = c.id JOIN operators from_op ON st.from_operator_id = from_op.id LEFT JOIN job_functions jf ON s.job_function_id = jf.id WHERE st.to_operator_id = ? AND st.status = 'pendente'");
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
            $stmt = $pdo->prepare("SELECT * FROM shift_transfers WHERE id = ? AND to_operator_id = ? AND status = 'pendente'");
            $stmt->execute([$transferId, $toOperatorId]);
            $transfer = $stmt->fetch();
            if (!$transfer) throw new Exception('Oferta não encontrada ou já processada.');
            
            $stmtUpdate = $pdo->prepare("UPDATE shift_transfers SET status = ?, resolved_at = NOW() WHERE id = ?");
            $stmtUpdate->execute([$response, $transferId]);
            
            if ($response === 'aceite') {
                $stmtSwap = $pdo->prepare("UPDATE shift_applications SET operator_id = ?, transferred_in = 1 WHERE id = ?");
                $stmtSwap->execute([$toOperatorId, $transfer['application_id']]);
            }
            
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

<?php
require_once __DIR__ . '/../../Database/Connection.php';
require_once __DIR__ . '/../../Utils/Email.php';

class PainelOperadorController
{
    /**
     * Mostra o dashboard principal do operador (mural de vagas).
     */
    public function index()
    {
        if (!isset($_SESSION['operator_id'])) {
            header('Location: /login');
            exit();
        }

        try {
            $pdo = Connection::getPdo();
            $operatorId = $_SESSION['operator_id'];
            
            $stmt = $pdo->prepare("
                SELECT 
                    s.id, s.title, s.shift_date, s.start_time, s.end_time, s.operator_payment as value,
                    c.nome_fantasia as company_name,
                    st.name as store_name, st.cidade, st.estado
                FROM shifts s
                JOIN companies c ON s.company_id = c.id
                JOIN stores st ON s.store_id = st.id
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

            require_once __DIR__ . '/../../Views/operador/dashboard.php';

        } catch (\PDOException $e) {
            die('Erro ao buscar as vagas disponíveis: ' . $e->getMessage());
        }
    }

    /**
     * Mostra a Central de Qualificações do operador.
     */
    public function showQualificationsPage()
    {
        if (!isset($_SESSION['operator_id'])) {
            header('Location: /login'); exit();
        }
        try {
            $pdo = Connection::getPdo();
            $operatorId = $_SESSION['operator_id'];
            $stmtQualified = $pdo->prepare("
                SELECT es.id, es.name FROM erp_systems es
                JOIN operator_qualifications oq ON es.id = oq.erp_system_id
                WHERE oq.operator_id = ?
            ");
            $stmtQualified->execute([$operatorId]);
            $myQualifications = $stmtQualified->fetchAll();
            $stmtRequested = $pdo->prepare("
                SELECT erp_system_id FROM training_requests
                WHERE operator_id = ? AND status IN ('solicitado', 'agendado')
            ");
            $stmtRequested->execute([$operatorId]);
            $requestedErpIds = $stmtRequested->fetchAll(PDO::FETCH_COLUMN, 0);
            $stmtAllErps = $pdo->query("SELECT * FROM erp_systems ORDER BY name ASC");
            $allErpSystems = $stmtAllErps->fetchAll();
            $stmtOffers = $pdo->prepare("SELECT COUNT(*) FROM shift_transfers WHERE to_operator_id = ? AND status = 'pendente'");
            $stmtOffers->execute([$operatorId]);
            $pendingOffers = $stmtOffers->fetchColumn();
            require_once __DIR__ . '/../../Views/operador/qualificacoes.php';
        } catch (\PDOException $e) {
            die('Erro ao carregar a página de qualificações: ' . $e->getMessage());
        }
    }

    /**
     * Regista o agendamento de um treinamento para um operador.
     */
    public function scheduleTraining()
    {
        if (!isset($_SESSION['operator_id'])) {
            header('Location: /login'); exit();
        }
        
        $erpId = $_POST['erp_system_id'] ?? null;
        $storeId = $_POST['store_id'] ?? null;
        $date = $_POST['training_date'] ?? null;
        $slot = $_POST['training_slot'] ?? null;
        $operatorId = $_SESSION['operator_id'];

        if (!$erpId || !$storeId || !$date || !$slot || !in_array($slot, ['manha', 'tarde'])) {
            die('Dados do agendamento inválidos ou em falta.');
        }

        $now = new DateTime("now", new DateTimeZone('America/Sao_Paulo'));
        $scheduledDateTime = new DateTime($date . ' ' . (($slot === 'manha') ? '09:00:00' : '14:00:00'), new DateTimeZone('America/Sao_Paulo'));

        if (($scheduledDateTime->getTimestamp() - $now->getTimestamp()) / 3600 < 24) {
            header('Location: /painel/operador/qualificacoes?status=schedule_failed_24h');
            exit();
        }
        
        $pdo = Connection::getPdo();
        try {
            $stmtCheckOperator = $pdo->prepare("
                SELECT COUNT(*) FROM training_requests 
                WHERE operator_id = ? AND scheduled_date = ? AND scheduled_slot = ? AND status = 'agendado'
            ");
            $stmtCheckOperator->execute([$operatorId, $date, $slot]);
            if ($stmtCheckOperator->fetchColumn() > 0) {
                header('Location: /painel/operador/qualificacoes?status=schedule_conflict_training');
                exit();
            }
            
            $stmtInsert = $pdo->prepare("INSERT INTO training_requests (operator_id, erp_system_id, store_id, scheduled_date, scheduled_slot, status) VALUES (?, ?, ?, ?, ?, 'agendado')");
            $stmtInsert->execute([$operatorId, $erpId, $storeId, $date, $slot]);
            
            header('Location: /painel/operador/qualificacoes?status=schedule_success');
            exit();

        } catch (\PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                header('Location: /painel/operador/qualificacoes?status=schedule_conflict_store');
                exit();
            }
            die('Erro ao agendar o treinamento: ' . $e->getMessage());
        }
    }
    
    /**
     * Processa a aceitação direta de uma vaga por um operador qualificado.
     */
    public function acceptShift()
    {
        if (!isset($_SESSION['operator_id'])) {
            header('Location: /login'); exit();
        }
        $shiftId = $_GET['id'] ?? null;
        $operatorId = $_SESSION['operator_id'];
        if (!$shiftId) die('ID da vaga não fornecido.');
        $pdo = Connection::getPdo();
        try {
            $pdo->beginTransaction();
            $stmtNewShift = $pdo->prepare("SELECT * FROM shifts WHERE id = ? AND status = 'aberta'");
            $stmtNewShift->execute([$shiftId]);
            $newShift = $stmtNewShift->fetch();
            if (!$newShift) {
                header('Location: /painel/operador?status=not_available_error');
                exit();
            }
            $stmtConflict = $pdo->prepare("
                SELECT COUNT(*) FROM shifts s JOIN shift_applications sa ON s.id = sa.shift_id
                WHERE sa.operator_id = :operator_id AND sa.status = 'aprovado' AND s.shift_date = :shift_date AND s.start_time < :end_time AND s.end_time > :start_time
            ");
            $stmtConflict->execute([':operator_id' => $operatorId, ':shift_date'  => $newShift['shift_date'], ':start_time'  => $newShift['start_time'], ':end_time'    => $newShift['end_time']]);
            if ($stmtConflict->fetchColumn() > 0) {
                header('Location: /painel/operador?status=conflict_error');
                exit();
            }
            $stmtCheck = $pdo->prepare("
                SELECT s.*, st.erp_system_id, o.name as operator_name, o.email as operator_email,
                (SELECT COUNT(*) FROM operator_qualifications WHERE operator_id = :operator_id AND erp_system_id = st.erp_system_id) as is_qualified
                FROM shifts s JOIN stores st ON s.store_id = st.id JOIN operators o ON o.id = :operator_id
                WHERE s.id = :shift_id AND s.status = 'aberta'
            ");
            $stmtCheck->execute(['operator_id' => $operatorId, 'shift_id' => $shiftId]);
            $shift = $stmtCheck->fetch();
            if (!$shift || $shift['is_qualified'] == 0) die('Vaga não encontrada ou você não está qualificado.');
            $stmtInsert = $pdo->prepare("INSERT INTO shift_applications (shift_id, operator_id, status) VALUES (?, ?, 'aprovado')");
            $stmtInsert->execute([$shiftId, $operatorId]);
            $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM shift_applications WHERE shift_id = ? AND status = 'aprovado'");
            $stmtCount->execute([$shiftId]);
            $approvedCount = $stmtCount->fetchColumn();
            if ($approvedCount >= $shift['num_positions']) {
                $stmtFill = $pdo->prepare("UPDATE shifts SET status = 'preenchida' WHERE id = ?");
                $stmtFill->execute([$shiftId]);
            }
            $pdo->commit();
            $subject = "Vaga Aceite! Detalhes do seu próximo turno na TURNY.";
            $body = "<h1>Parabéns, ".htmlspecialchars($shift['operator_name'])."!</h1><p>Você aceitou a vaga e já está confirmado! Acesse 'Meus Turnos' para ver os detalhes.</p><p><strong>Equipe TURNY</strong></p>";
            Email::sendEmail($shift['operator_email'], $shift['operator_name'], $subject, $body);
            header('Location: /painel/operador?status=accept_success');
            exit();
        } catch (\PDOException $e) {
            $pdo->rollBack();
            die('Erro ao aceitar a vaga: ' . $e->getMessage());
        }
    }
    
    /**
     * Mostra a lista de turnos do operador, incluindo os concluídos para avaliação.
     */
    public function showMyShifts()
    {
        if (!isset($_SESSION['operator_id'])) {
            header('Location: /login'); exit();
        }
        try {
            $pdo = Connection::getPdo();
            $operatorId = $_SESSION['operator_id'];
            $stmt = $pdo->prepare("
                SELECT 
                    s.id, s.title, s.shift_date, s.start_time, s.end_time, s.operator_payment,
                    sa.id as application_id, sa.transferred_in, sa.status as application_status,
                    sa.cash_discrepancy, sa.final_operator_payment,
                    st.name as store_name, 
                    st.cidade, 
                    st.estado,
                    c.nome_fantasia as company_name,
                    s.operator_payment as value
                FROM shifts s
                JOIN shift_applications sa ON s.id = sa.shift_id
                JOIN stores st ON s.store_id = st.id
                JOIN companies c ON s.company_id = c.id
                WHERE sa.operator_id = ? AND sa.status IN ('aprovado', 'concluido')
                ORDER BY s.shift_date DESC, s.start_time ASC
            ");
            $stmt->execute([$operatorId]);
            $myShifts = $stmt->fetchAll();

            $stmtRated = $pdo->prepare("
                SELECT application_id FROM shift_ratings 
                WHERE rated_operator_id = ? AND rating_for_company IS NOT NULL
            ");
            $stmtRated->execute([$operatorId]);
            $ratedApplicationIds = $stmtRated->fetchAll(PDO::FETCH_COLUMN, 0);
            
            $stmtOffers = $pdo->prepare("SELECT COUNT(*) FROM shift_transfers WHERE to_operator_id = ? AND status = 'pendente'");
            $stmtOffers->execute([$operatorId]);
            $pendingOffers = $stmtOffers->fetchColumn();
            
            require_once __DIR__ . '/../../Views/operador/meus_turnos.php';
        } catch (\PDOException $e) {
            die('Erro ao buscar os seus turnos: ' . $e->getMessage());
        }
    }

    /**
     * Processa o cancelamento de uma vaga pelo operador.
     */
    public function cancelApplication()
    {
        if (!isset($_SESSION['operator_id'])) {
            header('Location: /login'); exit();
        }
        $applicationId = $_GET['application_id'] ?? null;
        $operatorId = $_SESSION['operator_id'];
        if (!$applicationId) die('ID da candidatura não fornecido.');
        $pdo = Connection::getPdo();
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("
                SELECT sa.id, sa.shift_id, sa.operator_id, s.shift_date, s.start_time
                FROM shift_applications sa
                JOIN shifts s ON sa.shift_id = s.id
                WHERE sa.id = ?
            ");
            $stmt->execute([$applicationId]);
            $application = $stmt->fetch();
            if (!$application || $application['operator_id'] != $operatorId) die('Acesso não permitido.');
            $now = new DateTime("now", new DateTimeZone('America/Sao_Paulo'));
            $shiftStart = new DateTime($application['shift_date'] . ' ' . $application['start_time'], new DateTimeZone('America/Sao_Paulo'));
            if ($now > $shiftStart || $now->diff($shiftStart)->h < 12) {
                header('Location: /painel/operador/meus-turnos?status=cancel_failed');
                exit();
            }
            $stmtCancel = $pdo->prepare("UPDATE shift_applications SET status = 'cancelado_operador' WHERE id = ?");
            $stmtCancel->execute([$applicationId]);
            $stmtReopen = $pdo->prepare("UPDATE shifts SET status = 'aberta' WHERE id = ?");
            $stmtReopen->execute([$application['shift_id']]);
            $pdo->commit();
            header('Location: /painel/operador/meus-turnos?status=cancel_success');
            exit();
        } catch (\PDOException $e) {
            $pdo->rollBack();
            die('Erro ao processar o cancelamento: ' . $e->getMessage());
        }
    }

    /**
     * Mostra a página de perfil do operador logado.
     */
    public function showProfile()
    {
        if (!isset($_SESSION['operator_id'])) {
            header('Location: /login'); exit();
        }
        try {
            $pdo = Connection::getPdo();
            $operatorId = $_SESSION['operator_id'];
            $stmtOperator = $pdo->prepare("SELECT * FROM operators WHERE id = ?");
            $stmtOperator->execute([$operatorId]);
            $operator = $stmtOperator->fetch();
            if (!$operator) die('Operador não encontrado.');
            $stmtQualifications = $pdo->prepare("
                SELECT es.name FROM operator_qualifications oq
                JOIN erp_systems es ON oq.erp_system_id = es.id
                WHERE oq.operator_id = ?
            ");
            $stmtQualifications->execute([$operatorId]);
            $qualifications = $stmtQualifications->fetchAll(PDO::FETCH_COLUMN, 0);
            $stmtOffers = $pdo->prepare("SELECT COUNT(*) FROM shift_transfers WHERE to_operator_id = ? AND status = 'pendente'");
            $stmtOffers->execute([$operatorId]);
            $pendingOffers = $stmtOffers->fetchColumn();
            require_once __DIR__ . '/../../Views/operador/perfil.php';
        } catch (\PDOException $e) {
            die('Erro ao buscar os dados do perfil: ' . $e->getMessage());
        }
    }

    /**
     * Inicia uma oferta de transferência de vaga para outro operador.
     */
    public function initiateTransfer()
    {
        if (!isset($_SESSION['operator_id'])) {
            header('Location: /login'); exit();
        }
        $applicationId = $_POST['application_id'] ?? null;
        $toUsername = $_POST['username'] ?? '';
        $fromOperatorId = $_SESSION['operator_id'];
        if (!$applicationId || empty($toUsername)) {
            header('Location: /painel/operador/meus-turnos?status=transfer_failed_data');
            exit();
        }
        $pdo = Connection::getPdo();
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("
                SELECT sa.id as application_id, sa.shift_id, sa.operator_id, s.shift_date, 
                       s.start_time, s.end_time, st.erp_system_id
                FROM shift_applications sa
                JOIN shifts s ON sa.shift_id = s.id
                JOIN stores st ON s.store_id = st.id
                WHERE sa.id = ? AND sa.status = 'aprovado'
            ");
            $stmt->execute([$applicationId]);
            $application = $stmt->fetch();
            if (!$application || $application['operator_id'] != $fromOperatorId) die('Você não tem permissão para transferir esta vaga.');
            $now = new DateTime("now", new DateTimeZone('America/Sao_Paulo'));
            $shiftStart = new DateTime($application['shift_date'] . ' ' . $application['start_time'], new DateTimeZone('America/Sao_Paulo'));
            if ($now > $shiftStart || $now->diff($shiftStart)->h < 2) {
                header('Location: /painel/operador/meus-turnos?status=transfer_failed_time');
                exit();
            }
            $stmtToOperator = $pdo->prepare("SELECT * FROM operators WHERE username = ? AND status = 'ativo'");
            $stmtToOperator->execute([$toUsername]);
            $toOperator = $stmtToOperator->fetch();
            if (!$toOperator || $toOperator['id'] == $fromOperatorId) {
                header('Location: /painel/operador/meus-turnos?status=transfer_invalid_user');
                exit();
            }
            $stmtQualify = $pdo->prepare("SELECT COUNT(*) FROM operator_qualifications WHERE operator_id = ? AND erp_system_id = ?");
            $stmtQualify->execute([$toOperator['id'], $application['erp_system_id']]);
            if ($stmtQualify->fetchColumn() == 0) {
                header('Location: /painel/operador/meus-turnos?status=transfer_not_qualified');
                exit();
            }
            $stmtConflict = $pdo->prepare("
                SELECT COUNT(*) FROM shifts s JOIN shift_applications sa ON s.id = sa.shift_id
                WHERE sa.operator_id = ? AND sa.status = 'aprovado' AND s.shift_date = ? AND (s.start_time < ? AND s.end_time > ?)
            ");
            $stmtConflict->execute([$toOperator['id'], $application['shift_date'], $application['end_time'], $application['start_time']]);
            if ($stmtConflict->fetchColumn() > 0) {
                header('Location: /painel/operador/meus-turnos?status=transfer_conflict_shift');
                exit();
            }
            $shiftSlot = (strtotime($application['start_time']) < strtotime('12:00:00')) ? 'manha' : 'tarde';
            $stmtTrainingConflict = $pdo->prepare("
                SELECT COUNT(*) FROM training_requests 
                WHERE operator_id = ? AND scheduled_date = ? AND scheduled_slot = ? AND status = 'agendado'
            ");
            $stmtTrainingConflict->execute([$toOperator['id'], $application['shift_date'], $shiftSlot]);
            if ($stmtTrainingConflict->fetchColumn() > 0) {
                header('Location: /painel/operador/meus-turnos?status=transfer_conflict_training');
                exit();
            }
            $stmtLog = $pdo->prepare("INSERT INTO shift_transfers (shift_id, application_id, from_operator_id, to_operator_id, status) VALUES (?, ?, ?, ?, 'pendente')");
            $stmtLog->execute([$application['shift_id'], $applicationId, $fromOperatorId, $toOperator['id']]);
            $pdo->commit();
            header('Location: /painel/operador/meus-turnos?status=transfer_initiated');
            exit();
        } catch (\PDOException $e) {
            $pdo->rollBack();
            die('Erro ao processar a transferência: ' . $e->getMessage());
        }
    }

    /**
     * Mostra a página com as ofertas de transferência recebidas pelo operador.
     */
    public function showTransferOffers()
    {
        if (!isset($_SESSION['operator_id'])) {
            header('Location: /login'); exit();
        }
        $pdo = Connection::getPdo();
        try {
            $operatorId = $_SESSION['operator_id'];
            $stmt = $pdo->prepare("
                SELECT st.id as transfer_id, s.id as shift_id, s.title, s.shift_date, s.start_time, s.end_time, 
                       s.operator_payment as value, c.nome_fantasia as company_name, store.name as store_name, store.endereco, store.numero, store.bairro, store.cidade, store.estado,
                       from_op.name as from_operator_name
                FROM shift_transfers st
                JOIN shifts s ON st.shift_id = s.id
                JOIN stores store ON s.store_id = store.id
                JOIN companies c ON s.company_id = c.id
                JOIN operators from_op ON st.from_operator_id = from_op.id
                WHERE st.to_operator_id = ? AND st.status = 'pendente'
            ");
            $stmt->execute([$operatorId]);
            $offers = $stmt->fetchAll();
            $stmtOffers = $pdo->prepare("SELECT COUNT(*) FROM shift_transfers WHERE to_operator_id = ? AND status = 'pendente'");
            $stmtOffers->execute([$operatorId]);
            $pendingOffers = $stmtOffers->fetchColumn();
            require_once __DIR__ . '/../../Views/operador/ofertas.php';
        } catch (\PDOException $e) {
            die('Erro ao buscar ofertas de transferência: ' . $e->getMessage());
        }
    }

    /**
     * Processa a resposta (aceite/recusa) a uma oferta de transferência.
     */
    public function respondToTransfer()
    {
        if (!isset($_SESSION['operator_id'])) {
            header('Location: /login'); exit();
        }
        $transferId = $_GET['transfer_id'] ?? null;
        $response = $_GET['response'] ?? '';
        $toOperatorId = $_SESSION['operator_id'];
        if (!$transferId || !in_array($response, ['aceite', 'recusada'])) {
            die('Ação inválida ou ID da oferta em falta.');
        }
        $pdo = Connection::getPdo();
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT * FROM shift_transfers WHERE id = ? AND to_operator_id = ? AND status = 'pendente'");
            $stmt->execute([$transferId, $toOperatorId]);
            $transfer = $stmt->fetch();
            if (!$transfer) die('Oferta não encontrada ou já processada.');
            $stmtUpdate = $pdo->prepare("UPDATE shift_transfers SET status = ?, resolved_at = NOW() WHERE id = ?");
            $stmtUpdate->execute([$response, $transferId]);
            if ($response === 'aceite') {
                $stmtSwap = $pdo->prepare("UPDATE shift_applications SET operator_id = ?, transferred_in = 1 WHERE id = ?");
                $stmtSwap->execute([$toOperatorId, $transfer['application_id']]);
            }
            $pdo->commit();
            header('Location: /painel/operador/meus-turnos');
            exit();
        } catch (\PDOException $e) {
            $pdo->rollBack();
            die('Erro ao responder à oferta: ' . $e->getMessage());
        }
    }

    /**
     * Mostra a nova página de formulário para o operador avaliar a empresa.
     */
    public function showRateCompanyForm()
    {
        if (!isset($_SESSION['operator_id'])) {
            header('Location: /login'); exit();
        }
        $applicationId = $_GET['application_id'] ?? null;
        if (!$applicationId) die('ID da candidatura não fornecido.');

        $pdo = Connection::getPdo();
        $stmt = $pdo->prepare("
            SELECT sa.id as application_id, s.shift_date, c.nome_fantasia as company_name
            FROM shift_applications sa
            JOIN shifts s ON sa.shift_id = s.id
            JOIN companies c ON s.company_id = c.id
            WHERE sa.id = ? AND sa.operator_id = ?
        ");
        $stmt->execute([$applicationId, $_SESSION['operator_id']]);
        $shiftDetails = $stmt->fetch();

        if (!$shiftDetails) die('Turno não encontrado ou acesso não permitido.');

        require_once __DIR__ . '/../../Views/operador/avaliar.php';
    }
    
    /**
     * Guarda a avaliação que o operador fez da empresa.
     */
    public function rateCompany()
    {
        if (!isset($_SESSION['operator_id'])) {
            header('Location: /login'); exit();
        }

        $applicationId = $_POST['application_id'] ?? null;
        $rating = $_POST['rating'] ?? null;
        $comment = $_POST['comment'] ?? '';
        $operatorId = $_SESSION['operator_id'];

        if (!$applicationId || !$rating) {
            die('Dados da avaliação em falta.');
        }

        $pdo = Connection::getPdo();
        try {
            $stmt = $pdo->prepare("SELECT id FROM shift_ratings WHERE application_id = ? AND rated_operator_id = ?");
            $stmt->execute([$applicationId, $operatorId]);
            $ratingRecord = $stmt->fetch();

            if (!$ratingRecord) {
                die('Registo de avaliação não encontrado.');
            }

            $stmtUpdate = $pdo->prepare(
                "UPDATE shift_ratings SET rating_for_company = ?, comment_for_company = ? WHERE id = ?"
            );
            $stmtUpdate->execute([$rating, $comment, $ratingRecord['id']]);

            header('Location: /painel/operador/meus-turnos?status=rating_success');
            exit();

        } catch (\PDOException $e) {
            die('Erro ao guardar a sua avaliação: ' . $e->getMessage());
        }
    }
}
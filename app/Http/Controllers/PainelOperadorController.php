<?php

namespace App\Http\Controllers;

use App\Database\Connection;
use App\Utils\Email;
use PDO;
use PDOException;
use DateTime;
use DateTimeZone;
use Exception;
use Throwable;

class PainelOperadorController extends BaseController
{
/**
 * Helper para verificar se o operador está logado.
 */
protected function checkAccess(array $allowedRoles = [])
{
    if (!isset($_SESSION['operator_id'])) {
        header('Location: /login');
        exit();
    }

    // Se o BaseController usa roles, pode validar aqui também
    if (!empty($allowedRoles)) {
        $userRole = $_SESSION['role'] ?? null;
        if (!in_array($userRole, $allowedRoles)) {
            header('Location: /acesso-negado');
            exit();
        }
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
            
            $stmtOperatorLocation = $pdo->prepare("SELECT cidade, estado FROM operators WHERE id = ?");
            $stmtOperatorLocation->execute([$operatorId]);
            $operatorLocation = $stmtOperatorLocation->fetch();

            $vagasAbertas = [];

            if ($operatorLocation) {
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
                        AND st.cidade = :operator_cidade AND st.estado = :operator_estado
                        AND NOT EXISTS (
                            SELECT 1 FROM company_operator_blocks cob 
                            WHERE cob.operator_id = :operator_id AND cob.company_id = s.company_id
                        )
                    ORDER BY s.shift_date ASC, s.start_time ASC
                ");
                $stmt->execute([
                    'operator_id' => $operatorId,
                    'operator_cidade' => $operatorLocation['cidade'],
                    'operator_estado' => $operatorLocation['estado']
                ]);
                $vagasAbertas = $stmt->fetchAll();
            }

            $stmtOffers = $pdo->prepare("SELECT COUNT(*) FROM shift_transfers WHERE to_operator_id = ? AND status = 'pendente'");
            $stmtOffers->execute([$operatorId]);
            $pendingOffers = $stmtOffers->fetchColumn();

            $this->view('operador/dashboard', compact('vagasAbertas', 'pendingOffers'));
        } catch (Throwable $e) {
            throw $e;
        }
    }

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

    public function scheduleTraining()
    {
        $this->checkAccess();
        \verify_csrf_token();
        
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
                \flash('Não é possível agendar um treinamento com menos de 24 horas de antecedência.', 'error');
                header('Location: /painel/operador/qualificacoes');
                exit();
            }
            
            $stmtCheckOperator = $pdo->prepare("SELECT COUNT(*) FROM training_requests WHERE operator_id = ? AND scheduled_date = ? AND scheduled_slot = ? AND status = 'agendado'");
            $stmtCheckOperator->execute([$operatorId, $date, $slot]);
            if ($stmtCheckOperator->fetchColumn() > 0) {
                \flash('Você já tem um treinamento agendado neste mesmo horário.', 'error');
                header('Location: /painel/operador/qualificacoes');
                exit();
            }
            
            $stmtInsert = $pdo->prepare("INSERT INTO training_requests (operator_id, erp_system_id, store_id, scheduled_date, scheduled_slot, status) VALUES (?, ?, ?, ?, ?, 'agendado')");
            $stmtInsert->execute([$operatorId, $erpId, $storeId, $date, $slot]);
            
            \flash('Treinamento agendado com sucesso!');
            header('Location: /painel/operador/qualificacoes');
            exit();

        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                \flash('Já existe um treinamento agendado para esta loja neste horário.', 'error');
                header('Location: /painel/operador/qualificacoes');
                exit();
            }
            throw $e;
        }
    }
    
    public function acceptShift()
    {
        $this->checkAccess();
        \verify_csrf_token();
        $shiftId = $_POST['id'] ?? null;
        $operatorId = $_SESSION['operator_id'];
        if (!$shiftId) throw new Exception('ID da vaga não fornecido.');
        
        $pdo = Connection::getPdo();
        try {
            $pdo->beginTransaction();
            $stmtNewShift = $pdo->prepare("SELECT * FROM shifts WHERE id = ? AND status = 'aberta' FOR UPDATE");
            $stmtNewShift->execute([$shiftId]);
            $newShift = $stmtNewShift->fetch();
            if (!$newShift) {
                $pdo->rollBack();
                \flash('Esta vaga acabou de ser preenchida ou não está mais disponível.', 'error');
                header('Location: /painel/operador');
                exit();
            }
            $stmtConflict = $pdo->prepare("SELECT COUNT(*) FROM shifts s JOIN shift_applications sa ON s.id = sa.shift_id WHERE sa.operator_id = :operator_id AND sa.status IN ('aprovado', 'check_in') AND s.shift_date = :shift_date AND s.start_time < :end_time AND s.end_time > :start_time");
            $stmtConflict->execute([':operator_id' => $operatorId, ':shift_date' => $newShift['shift_date'], ':start_time' => $newShift['start_time'], ':end_time' => $newShift['end_time']]);
            if ($stmtConflict->fetchColumn() > 0) {
                $pdo->rollBack();
                \flash('Não foi possível aceitar a vaga. O horário entra em conflito com outro turno que você já tem.', 'error');
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
            $stmtOperator = $pdo->prepare("SELECT name, email FROM operators WHERE id = ?");
            $stmtOperator->execute([$operatorId]);
            $operator = $stmtOperator->fetch();
            $pdo->commit();
            if ($operator) {
                $subject = "Vaga Aceita! Detalhes do seu próximo turno na TURNY.";
                $body = "<h1>Parabéns, ".htmlspecialchars($operator['name'])."!</h1><p>Você aceitou a vaga e já está confirmado! Acesse 'Meus Turnos' para ver os detalhes.</p><p><strong>Equipe TURNY</strong></p>";
                Email::sendEmail($operator['email'], $operator['name'], $subject, $body);
            }
            \flash('Vaga aceita com sucesso! Pode vê-la em "Meus Turnos".');
            header('Location: /painel/operador');
            exit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

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

    public function cancelApplication()
    {
        $this->checkAccess();
        \verify_csrf_token();
        $applicationId = $_POST['application_id'] ?? null;
        $operatorId = $_SESSION['operator_id'];
        if (!$applicationId) throw new Exception('ID da candidatura não fornecido.');
        try {
            $pdo = Connection::getPdo();
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT sa.id, sa.shift_id, sa.operator_id, s.shift_date, s.start_time FROM shift_applications sa JOIN shifts s ON sa.shift_id = s.id WHERE sa.id = ?");
            $stmt->execute([$applicationId]);
            $application = $stmt->fetch();
            if (!$application || $application['operator_id'] != $operatorId) throw new Exception('Acesso não permitido.');
            $now = new DateTime("now", new DateTimeZone('America/Sao_Paulo'));
            $shiftStart = new DateTime($application['shift_date'] . ' ' . $application['start_time'], new DateTimeZone('America/Sao_Paulo'));
            if ($now > $shiftStart || ($shiftStart->getTimestamp() - $now->getTimestamp()) < (12 * 3600)) {
                \flash('Não é possível cancelar um turno com menos de 12 horas de antecedência.', 'error');
                header('Location: /painel/operador/meus-turnos');
                exit();
            }
            $stmtCancel = $pdo->prepare("UPDATE shift_applications SET status = 'cancelado_operador' WHERE id = ?");
            $stmtCancel->execute([$applicationId]);
            $stmtReopen = $pdo->prepare("UPDATE shifts SET status = 'aberta' WHERE id = ?");
            $stmtReopen->execute([$application['shift_id']]);
            $pdo->commit();
            \flash('Turno cancelado com sucesso.');
            header('Location: /painel/operador/meus-turnos');
            exit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    public function showProfile()
    {
        $this->checkAccess();
        try {
            $pdo = Connection::getPdo();
            $operatorId = $_SESSION['operator_id'];
            $stmtOperator = $pdo->prepare("
                SELECT o.*, w.balance 
                FROM operators o
                LEFT JOIN operator_wallets w ON o.id = w.operator_id
                WHERE o.id = ?
            ");
            $stmtOperator->execute([$operatorId]);
            $operator = $stmtOperator->fetch();
            if (!$operator) {
                throw new Exception('Operador não encontrado.');
            }
            $stmtErpQualifications = $pdo->prepare("SELECT es.name FROM operator_qualifications oq JOIN erp_systems es ON oq.erp_system_id = es.id WHERE oq.operator_id = ?");
            $stmtErpQualifications->execute([$operatorId]);
            $erpQualifications = $stmtErpQualifications->fetchAll(PDO::FETCH_COLUMN, 0);
            $stmtJobQualifications = $pdo->prepare("SELECT jf.name FROM operator_job_qualifications ojq JOIN job_functions jf ON ojq.job_function_id = jf.id WHERE ojq.operator_id = ?");
            $stmtJobQualifications->execute([$operatorId]);
            $jobQualifications = $stmtJobQualifications->fetchAll(PDO::FETCH_COLUMN, 0);
            $stmtOffers = $pdo->prepare("SELECT COUNT(*) FROM shift_transfers WHERE to_operator_id = ? AND status = 'pendente'");
            $stmtOffers->execute([$operatorId]);
            $pendingOffers = $stmtOffers->fetchColumn();
            $this->view('operador/perfil', compact('operator', 'erpQualifications', 'jobQualifications', 'pendingOffers'));
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public function showWallet()
    {
        $this->checkAccess();
        try {
            $pdo = Connection::getPdo();
            $operatorId = $_SESSION['operator_id'];
            $stmtWallet = $pdo->prepare("SELECT id, balance FROM operator_wallets WHERE operator_id = ?");
            $stmtWallet->execute([$operatorId]);
            $wallet = $stmtWallet->fetch();
            $transactions = [];
            if ($wallet) {
                $stmtTx = $pdo->prepare("SELECT * FROM wallet_transactions WHERE wallet_id = ? ORDER BY created_at DESC LIMIT 15");
                $stmtTx->execute([$wallet['id']]);
                $transactions = $stmtTx->fetchAll();
            }
            $stmtOffers = $pdo->prepare("SELECT COUNT(*) FROM shift_transfers WHERE to_operator_id = ? AND status = 'pendente'");
            $stmtOffers->execute([$operatorId]);
            $pendingOffers = $stmtOffers->fetchColumn();
            $this->view('operador/carteira', [
                'balance' => $wallet['balance'] ?? 0.00,
                'transactions' => $transactions,
                'pendingOffers' => $pendingOffers
            ]);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function showTransferForm()
    {
        $this->checkAccess();
        try {
            $pdo = Connection::getPdo();
            $stmt = $pdo->prepare("SELECT balance FROM operator_wallets WHERE operator_id = ?");
            $stmt->execute([$_SESSION['operator_id']]);
            $wallet = $stmt->fetch();

            $this->view('operador/transferir', ['balance' => $wallet['balance'] ?? 0.00]);
        } catch (Throwable $e) {
            throw $e;
        }
    }

    public function processTransfer()
    {
        $this->checkAccess();
        \verify_csrf_token();
        $destinatarioKey = $_POST['destinatario_key'] ?? null;
        $amountStr = str_replace(',', '.', $_POST['amount'] ?? '0');
        $amount = round((float)$amountStr, 2);
        $password = $_POST['password'] ?? null;
        $payerId = $_SESSION['operator_id'];

        if (!$destinatarioKey || $amount <= 0 || !$password) {
            \flash('Todos os campos são obrigatórios.', 'error');
            header('Location: /painel/operador/transferir');
            exit();
        }

        $pdo = Connection::getPdo();
        try {
            $pdo->beginTransaction();
            $cleanKey = preg_replace('/[^0-9]/', '', $destinatarioKey);
            $stmtPayee = $pdo->prepare("SELECT id, name FROM operators WHERE email = ? OR cpf = ? OR phone = ?");
            $stmtPayee->execute([$destinatarioKey, $cleanKey, $cleanKey]);
            $payee = $stmtPayee->fetch();
            if (!$payee) {
                throw new \Exception("Nenhum operador encontrado com esta Chave.");
            }
            $payeeId = $payee['id'];
            if ($payerId == $payeeId) {
                 throw new \Exception("Você não pode transferir para si mesmo.");
            }

            $stmtPayer = $pdo->prepare("SELECT password, name FROM operators WHERE id = ?");
            $stmtPayer->execute([$payerId]);
            $payer = $stmtPayer->fetch();
            if (!$payer || !password_verify($password, $payer['password'])) {
                throw new \Exception("Senha inválida.");
            }

            $stmtWalletPayer = $pdo->prepare("SELECT id, balance FROM operator_wallets WHERE operator_id = ? FOR UPDATE");
            $stmtWalletPayer->execute([$payerId]);
            $payerWallet = $stmtWalletPayer->fetch();
            if (!$payerWallet || $payerWallet['balance'] < $amount) {
                throw new \Exception("Saldo insuficiente.");
            }

            $stmtWalletPayee = $pdo->prepare("SELECT id FROM operator_wallets WHERE operator_id = ? FOR UPDATE");
            $stmtWalletPayee->execute([$payeeId]);
            $payeeWallet = $stmtWalletPayee->fetch();
            if (!$payeeWallet) {
                $pdo->prepare("INSERT INTO operator_wallets (operator_id) VALUES (?)")->execute([$payeeId]);
                $payeeWallet['id'] = $pdo->lastInsertId();
            }

            $pdo->prepare("UPDATE operator_wallets SET balance = balance - ? WHERE id = ?")->execute([$amount, $payerWallet['id']]);
            $pdo->prepare("UPDATE operator_wallets SET balance = balance + ? WHERE id = ?")->execute([$amount, $payeeWallet['id']]);

            $descPayer = "Transferência para " . htmlspecialchars($payee['name']);
            $pdo->prepare("INSERT INTO wallet_transactions (wallet_id, type, amount, description, counterparty_operator_id) VALUES (?, 'transfer_out', ?, ?, ?)")->execute([$payerWallet['id'], $amount, $descPayer, $payeeId]);
            
            $descPayee = "Transferência recebida de " . htmlspecialchars($payer['name']);
            $stmtPayeeTx = $pdo->prepare("INSERT INTO wallet_transactions (wallet_id, type, amount, description, counterparty_operator_id) VALUES (?, 'transfer_in', ?, ?, ?)");
            $stmtPayeeTx->execute([$payeeWallet['id'], $amount, $descPayee, $payerId]);
            $lastTxId = $pdo->lastInsertId();

            $notificationMessage = "Você recebeu uma transferência de R$ " . number_format($amount, 2, ',', '.') . " de " . htmlspecialchars($payer['name']);
            $receiptUrl = "/painel/operador/comprovante?id=" . $lastTxId;
            $pdo->prepare("INSERT INTO operator_notifications (operator_id, message, related_url) VALUES (?, ?, ?)")->execute([$payeeId, $notificationMessage, $receiptUrl]);

            $pdo->commit();
            \flash('Transferência realizada com sucesso!');
            header('Location: /painel/operador/carteira');
            exit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            \flash('Erro: ' . $e->getMessage(), 'error');
            header('Location: /painel/operador/transferir');
            exit();
        }
    }

    public function initiateTransfer()
    {
        $this->checkAccess();
        \verify_csrf_token();
        $applicationId = $_POST['application_id'] ?? null;
        $toUsername = $_POST['username'] ?? '';
        $fromOperatorId = $_SESSION['operator_id'];
        if (!$applicationId || empty($toUsername)) {
            \flash('Dados da transferência em falta.', 'error');
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
                \flash('Transferência falhou: O @username inserido não foi encontrado ou não está ativo.', 'error');
                header('Location: /painel/operador/meus-turnos');
                exit();
            }
            $stmtLog = $pdo->prepare("INSERT INTO shift_transfers (shift_id, application_id, from_operator_id, to_operator_id, status) VALUES (?, ?, ?, ?, 'pendente')");
            $stmtLog->execute([$application['shift_id'], $applicationId, $fromOperatorId, $toOperator['id']]);
            $pdo->commit();
            \flash('Oferta de transferência enviada com sucesso!');
            header('Location: /painel/operador/meus-turnos');
            exit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

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

    public function respondToTransfer()
    {
        $this->checkAccess();
        \verify_csrf_token();
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
            \flash('Resposta à oferta enviada com sucesso!');
            header('Location: /painel/operador/meus-turnos');
            exit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

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
    
    public function rateCompany()
    {
        $this->checkAccess();
        \verify_csrf_token();
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
            if (!$ratingRecord) {
                 throw new Exception('Registo de avaliação não encontrado. A empresa precisa finalizar o turno primeiro.');
            }
            $stmtUpdate = $pdo->prepare("UPDATE shift_ratings SET rating_for_company = ?, comment_for_company = ? WHERE id = ?");
            $stmtUpdate->execute([$rating, $comment, $ratingRecord['id']]);
            \flash('Avaliação enviada com sucesso. Obrigado!');
            header('Location: /painel/operador/meus-turnos');
            exit();
        } catch (PDOException $e) {
            throw $e;
        }
    }

    public function showPaymentPage()
    {
        $this->checkAccess();
        $this->view('operador/pagar');
    }

    public function showPaymentSuccessPage()
    {
        $this->checkAccess();
        $this->view('operador/pagamento_sucesso');
    }

    public function showChargePage()
    {
        $this->checkAccess();
        $this->view('operador/cobrar');
    }

    public function showQrCodePage()
    {
        $this->checkAccess();
        $this->view('operador/qrcode');
    }

    public function getNotifications()
    {
        $this->checkAccess();
        try {
            $pdo = Connection::getPdo();
            $operatorId = $_SESSION['operator_id'];

            // Busca a notificação não lida mais recente
            $stmt = $pdo->prepare(
                "SELECT id, message, related_url FROM operator_notifications 
                 WHERE operator_id = ? AND is_read = 0 
                 ORDER BY created_at DESC LIMIT 1"
            );
            $stmt->execute([$operatorId]);
            $notification = $stmt->fetch();

            if ($notification) {
                // Marca a notificação como lida para não mostrá-la novamente
                $stmtUpdate = $pdo->prepare("UPDATE operator_notifications SET is_read = 1 WHERE id = ?");
                $stmtUpdate->execute([$notification['id']]);
                
                \json_response(['status' => 'success', 'notification' => $notification]);
            } else {
                \json_response(['status' => 'no_new_notifications']);
            }
        } catch (Throwable $e) {
            \json_response(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
	
	public function showReceipt()
{
    $this->checkAccess();
    $txId = $_GET['id'] ?? null;
    if (!$txId) {
        \flash('ID da transação não fornecido.', 'error');
        header('Location: /painel/operador/carteira');
        exit();
    }

    try {
        $pdo = Connection::getPdo();
        $operatorId = $_SESSION['operator_id'];

        $stmt = $pdo->prepare(
            "SELECT wt.*, o.name as payer_name 
             FROM wallet_transactions wt
             LEFT JOIN operators o ON wt.counterparty_operator_id = o.id
             JOIN operator_wallets w ON wt.wallet_id = w.id
             WHERE wt.id = ? AND w.operator_id = ?"
        );
        $stmt->execute([$txId, $operatorId]);
        $transaction = $stmt->fetch();

        if (!$transaction) {
            \flash('Transação não encontrada.', 'error');
            header('Location: /painel/operador/carteira');
            exit();
        }

        $this->view('operador/comprovante', compact('transaction'));
    } catch (PDOException $e) {
        throw $e;
    }
}

}

<?php

namespace App\Http\Controllers;

use App\Database\Connection;
use Throwable;
use chillerlan\QRCode\QRCode;

class PaymentController extends BaseController
{
    /**
     * EMPRESA → Gera um QR Code de cobrança
     */
    public function generateQrCode()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {
            if (session_status() === PHP_SESSION_NONE) session_start();

            if (!isset($_SESSION['user_id']) || empty($_SESSION['company_id'])) {
                throw new \Exception('Acesso negado.', 403);
            }

            \verify_csrf_token();

            $amountStr = str_replace(',', '.', $_POST['amount'] ?? '0');
            $amount = round((float)$amountStr, 2);
            if ($amount <= 0) throw new \Exception('O valor deve ser maior que zero.', 400);

            $pdo = Connection::getPdo();
            $hmacSecret = getenv('HMAC_SECRET') ?: 'default-secret-change-me';

            $payload = [
                'store_id' => $_SESSION['store_id'] ?? 0,
                'company_id' => $_SESSION['company_id'],
                'user_id' => $_SESSION['user_id'],
                'amount' => $amount,
                'token' => bin2hex(random_bytes(32)),
                'timestamp' => time()
            ];

            $payloadJson = json_encode($payload);
            $signature = hash_hmac('sha256', $payloadJson, $hmacSecret);
            $qrContent = json_encode(['payload' => $payload, 'signature' => $signature]);

            $stmt = $pdo->prepare("
                INSERT INTO payment_tokens
                (company_id, store_id, created_by_user_id, transaction_token, amount, status)
                VALUES (?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([$payload['company_id'], $payload['store_id'], $payload['user_id'], $payload['token'], $payload['amount']]);

            $qrDataUri = (new QRCode)->render($qrContent);
            \generate_csrf_token();

            \json_response([
                'status' => 'success',
                'qr_code_data_uri' => $qrDataUri,
                'transaction_token' => $payload['token'],
                'new_token' => $_SESSION['csrf_token']
            ]);

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_ACTIVE && empty($_SESSION['csrf_token'])) \generate_csrf_token();
            $errorCode = in_array($e->getCode(), [400, 403]) ? $e->getCode() : 500;
            \json_response([
                'status' => 'error',
                'message' => 'Erro interno ao gerar QR Code: ' . $e->getMessage(),
                'new_token' => $_SESSION['csrf_token'] ?? null
            ], $errorCode);
        }
    }

    /**
     * EMPRESA → Consome QR Code em loja
     */
    public function consumeQrCode()
    {
        if (!isset($_SESSION['operator_id'])) {
            \json_response(['status' => 'error', 'message' => 'Acesso negado. Apenas operadores podem realizar pagamentos.'], 403);
        }

        \verify_csrf_token();

        $qrContent = $_POST['qr_content'] ?? null;
        $password = $_POST['password'] ?? null;

        if (!$qrContent || !$password) {
            \json_response(['status' => 'error', 'message' => 'Conteúdo do QR Code e senha são obrigatórios.'], 400);
        }

        $pdo = Connection::getPdo();

        try {
            $qrData = json_decode($qrContent, true);
            if (json_last_error() !== JSON_ERROR_NONE || !isset($qrData['payload'], $qrData['signature'])) {
                throw new \Exception("QR Code malformado.", 400);
            }

            $payload = $qrData['payload'];
            $signature = $qrData['signature'];
            $hmacSecret = getenv('HMAC_SECRET') ?: 'default-secret-change-me';
            $payloadJson = json_encode($payload);

            if (!hash_equals(hash_hmac('sha256', $payloadJson, $hmacSecret), $signature)) {
                throw new \Exception("Assinatura do QR Code inválida. Risco de fraude.", 403);
            }

            if (time() - ($payload['timestamp'] ?? 0) > 120) {
                throw new \Exception("QR Code expirado. Por favor, peça para gerar um novo.", 400);
            }

            $pdo->beginTransaction();

            $operatorId = $_SESSION['operator_id'];
            $stmtOp = $pdo->prepare("SELECT password FROM operators WHERE id = ?");
            $stmtOp->execute([$operatorId]);
            $operator = $stmtOp->fetch();

            if (!$operator || empty($operator['password']) || !password_verify($password, $operator['password'])) {
                throw new \Exception("Senha inválida.", 403);
            }

            $stmtToken = $pdo->prepare("SELECT id, status FROM payment_tokens WHERE transaction_token = ? FOR UPDATE");
            $stmtToken->execute([$payload['token']]);
            $paymentToken = $stmtToken->fetch();
            if (!$paymentToken || $paymentToken['status'] !== 'pending') {
                throw new \Exception("Este QR Code de pagamento é inválido ou já foi utilizado.", 400);
            }

            $amount = (float)$payload['amount'];

            $stmtWallet = $pdo->prepare("SELECT id, balance FROM operator_wallets WHERE operator_id = ? FOR UPDATE");
            $stmtWallet->execute([$operatorId]);
            $wallet = $stmtWallet->fetch();
            if (!$wallet || $wallet['balance'] < $amount) throw new \Exception("Saldo insuficiente.", 400);

            $walletId = $wallet['id'];
            $pdo->prepare("UPDATE operator_wallets SET balance = balance - ? WHERE id = ?")->execute([$amount, $walletId]);
            $pdo->prepare("INSERT INTO wallet_transactions (wallet_id, type, amount, description, origin_ref) VALUES (?, 'debit', ?, ?, ?)")->execute([$walletId, $amount, 'Pagamento na loja via QR Code', $payload['token']]);

            $stmtUpdateToken = $pdo->prepare("UPDATE payment_tokens SET status = 'completed', completed_at = NOW() WHERE id = ?");
            $stmtUpdateToken->execute([$paymentToken['id']]);

            $pdo->commit();
            \generate_csrf_token();
            \json_response([
                'status' => 'success',
                'message' => 'Pagamento aprovado com sucesso!',
                'new_token' => $_SESSION['csrf_token']
            ]);

        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
            $errorCode = in_array($e->getCode(), [400, 403]) ? $e->getCode() : 500;
            \generate_csrf_token();
            \json_response(['status' => 'error', 'message' => $e->getMessage(), 'new_token' => $_SESSION['csrf_token'] ?? null], $errorCode);
        }
    }

    /**
     * EMPRESA → Polling de status
     */
    public function getPaymentStatus()
    {
        if (!isset($_SESSION['user_id']) || empty($_SESSION['company_id'])) {
            \json_response(['status' => 'error', 'message' => 'Acesso negado.'], 403);
        }

        $token = $_GET['token'] ?? null;
        if (!$token) \json_response(['status' => 'error', 'message' => 'Token não fornecido.'], 400);

        try {
            $pdo = Connection::getPdo();
            $stmt = $pdo->prepare("SELECT status FROM payment_tokens WHERE transaction_token = ? AND company_id = ?");
            $stmt->execute([$token, $_SESSION['company_id']]);
            $result = $stmt->fetch();

            \json_response(['payment_status' => $result['status'] ?? 'not_found']);
        } catch (Throwable $e) {
            \json_response(['status' => 'error', 'message' => 'Erro no servidor.'], 500);
        }
    }

    /**
     * OPERADOR → Gera QR Code para outro operador
     */
    public function operatorGenerateQrCode()
    {
        if (!isset($_SESSION['operator_id'])) {
            \json_response(['status' => 'error', 'message' => 'Acesso negado.'], 403);
        }

        \verify_csrf_token();

        $amountStr = str_replace(',', '.', $_POST['amount'] ?? '0');
        $amount = round((float)$amountStr, 2);
        if ($amount <= 0) {
            \json_response(['status' => 'error', 'message' => 'O valor deve ser maior que zero.'], 400);
        }

        try {
            $hmacSecret = getenv('HMAC_SECRET') ?: 'default-secret-change-me';
            $payload = [
                'payee_operator_id' => $_SESSION['operator_id'],
                'operator_name' => $_SESSION['user_name'],
                'amount' => $amount,
                'token' => bin2hex(random_bytes(32)),
                'timestamp' => time()
            ];

            $payloadJson = json_encode($payload);
            $signature = hash_hmac('sha256', $payloadJson, $hmacSecret);
            $qrContent = json_encode(['payload' => $payload, 'signature' => $signature]);
            $qrDataUri = (new QRCode)->render($qrContent);

            \generate_csrf_token();
            \json_response([
                'status' => 'success',
                'qr_code_data_uri' => $qrDataUri,
                'new_token' => $_SESSION['csrf_token']
            ]);
        } catch (Throwable $e) {
            \generate_csrf_token();
            \json_response(['status' => 'error', 'message' => 'Erro ao gerar QR Code: '.$e->getMessage(), 'new_token' => $_SESSION['csrf_token'] ?? null], 500);
        }
    }

    /**
     * OPERADOR → Consome QR Code de outro operador (transferência de saldo) com notificação
     */
    public function operatorConsumeQrCode()
    {
        if (!isset($_SESSION['operator_id'])) {
            \json_response(['status' => 'error', 'message' => 'Acesso negado. Apenas operadores podem realizar pagamentos.'], 403);
        }

        \verify_csrf_token();

        $qrContent = $_POST['qr_content'] ?? null;
        $password = $_POST['password'] ?? null;

        if (!$qrContent || !$password) {
            \json_response(['status' => 'error', 'message' => 'Conteúdo do QR Code e senha são obrigatórios.'], 400);
        }

        $pdo = Connection::getPdo();

        try {
            $qrData = json_decode($qrContent, true);
            if (json_last_error() !== JSON_ERROR_NONE || !isset($qrData['payload'], $qrData['signature'])) {
                throw new \Exception("QR Code malformado.", 400);
            }

            $payload = $qrData['payload'];
            $signature = $qrData['signature'];
            $hmacSecret = getenv('HMAC_SECRET') ?: 'default-secret-change-me';
            $payloadJson = json_encode($payload);

            if (!hash_equals(hash_hmac('sha256', $payloadJson, $hmacSecret), $signature)) {
                throw new \Exception("Assinatura inválida no QR Code. Possível fraude.", 403);
            }

            if (time() - ($payload['timestamp'] ?? 0) > 120) {
                throw new \Exception("QR Code expirado. Peça para gerar um novo.", 400);
            }

            $payeeId = $payload['payee_operator_id'];
            $payerId = $_SESSION['operator_id'];
            $amount = (float)$payload['amount'];

            if ($payerId == $payeeId) {
                throw new \Exception("Você não pode pagar para si mesmo.", 400);
            }

            $pdo->beginTransaction();

            // valida senha do operador pagador
            $stmtOp = $pdo->prepare("SELECT password, name FROM operators WHERE id = ?");
            $stmtOp->execute([$payerId]);
            $payer = $stmtOp->fetch();

            if (!$payer || empty($payer['password']) || !password_verify($password, $payer['password'])) {
                throw new \Exception("Senha inválida.", 403);
            }

            // pega carteira do pagador
            $stmtWalletPayer = $pdo->prepare("SELECT id, balance FROM operator_wallets WHERE operator_id = ? FOR UPDATE");
            $stmtWalletPayer->execute([$payerId]);
            $payerWallet = $stmtWalletPayer->fetch();
            if (!$payerWallet || $payerWallet['balance'] < $amount) {
                throw new \Exception("Saldo insuficiente.", 400);
            }

            // pega carteira do recebedor
            $stmtWalletPayee = $pdo->prepare("SELECT id, balance FROM operator_wallets WHERE operator_id = ? FOR UPDATE");
            $stmtWalletPayee->execute([$payeeId]);
            $payeeWallet = $stmtWalletPayee->fetch();
            if (!$payeeWallet) {
                throw new \Exception("Carteira do recebedor não encontrada.", 400);
            }

            // debita pagador
            $pdo->prepare("UPDATE operator_wallets SET balance = balance - ? WHERE id = ?")
                ->execute([$amount, $payerWallet['id']]);
            $pdo->prepare("INSERT INTO wallet_transactions (wallet_id, type, amount, description, origin_ref)
                VALUES (?, 'debit', ?, ?, ?)")
                ->execute([$payerWallet['id'], $amount, 'Transferência para operador ID '.$payeeId, $payload['token']]);

            // credita recebedor
            $pdo->prepare("UPDATE operator_wallets SET balance = balance + ? WHERE id = ?")
                ->execute([$amount, $payeeWallet['id']]);
            $pdo->prepare("INSERT INTO wallet_transactions (wallet_id, type, amount, description, origin_ref)
                VALUES (?, 'credit', ?, ?, ?)")
                ->execute([$payeeWallet['id'], $amount, 'Transferência recebida do operador ID '.$payerId, $payload['token']]);

            // --- NOTIFICAÇÃO PARA O RECEBEDOR ---
            $notificationMessage = "Você recebeu um pagamento de R$ " . number_format($amount, 2, ',', '.') . " de " . htmlspecialchars($payer['name']);
            $stmtNotification = $pdo->prepare(
                "INSERT INTO operator_notifications (operator_id, message, related_url) VALUES (?, ?, ?)"
            );
            $lastTxId = $pdo->lastInsertId(); 
            $receiptUrl = "/painel/operador/comprovante?id=" . $lastTxId;
            $stmtNotification->execute([$payeeId, $notificationMessage, $receiptUrl]);
            // --- FIM DA NOTIFICAÇÃO ---

            $pdo->commit();
            \generate_csrf_token();

            \json_response([
                'status' => 'success',
                'message' => 'Transferência concluída com sucesso!',
                'new_token' => $_SESSION['csrf_token']
            ]);

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errorCode = in_array($e->getCode(), [400, 403]) ? $e->getCode() : 500;
            \generate_csrf_token();
            \json_response(['status' => 'error', 'message' => $e->getMessage(), 'new_token' => $_SESSION['csrf_token'] ?? null], $errorCode);
        }
    }
/**
 * OPERADOR → Polling de status da transferência entre operadores
 */
public function getOperatorPaymentStatus()
{
    if (!isset($_SESSION['operator_id'])) {
        \json_response(['status' => 'error', 'message' => 'Acesso negado.'], 403);
    }

    $token = $_GET['token'] ?? null;
    if (!$token) {
        \json_response(['status' => 'error', 'message' => 'Token não fornecido.'], 400);
    }

    try {
        $pdo = Connection::getPdo();

        // Busca o ID do operador recebedor na transação
        $stmtTx = $pdo->prepare("SELECT payee_operator_id FROM operator_transactions WHERE transaction_token = ?");
        $stmtTx->execute([$token]);
        $tx = $stmtTx->fetch();

        if (!$tx) {
            \json_response(['payment_status' => 'not_found']);
        }

        $payeeId = $tx['payee_operator_id'];

        // Verifica se já existe a transação de crédito para o recebedor específico
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM wallet_transactions wt
            JOIN operator_wallets w ON wt.wallet_id = w.id
            WHERE wt.origin_ref = ? AND wt.type = 'credit' AND w.operator_id = ?
        ");
        $stmt->execute([$token, $payeeId]);
        $creditExists = $stmt->fetchColumn();

        \json_response(['payment_status' => $creditExists ? 'completed' : 'pending']);

    } catch (Throwable $e) {
        \json_response(['status' => 'error', 'message' => 'Erro no servidor.'], 500);
    }
}

/**
 * OPERADOR → Exibir comprovante de transferência recebida
 */
public function showOperatorPaymentReceipt()
{
    if (!isset($_SESSION['operator_id'])) {
        throw new \Exception('Acesso negado.', 403);
    }

    $txId = $_GET['id'] ?? null;
    if (!$txId) {
        throw new \Exception('ID da transação não fornecido.');
    }

    try {
        $pdo = Connection::getPdo();
        $stmt = $pdo->prepare("
            SELECT wt.id, wt.amount, wt.created_at, wt.type, wt.description,
                   o.name as operator_name
            FROM wallet_transactions wt
            JOIN operator_wallets w ON wt.wallet_id = w.id
            JOIN operators o ON w.operator_id = o.id
            WHERE wt.id = ? AND w.operator_id = ?
        ");
        $stmt->execute([$txId, $_SESSION['operator_id']]);
        $transaction = $stmt->fetch();

        if (!$transaction) {
            throw new \Exception('Transação não encontrada ou acesso não permitido.');
        }

        $this->view('operador/comprovante', ['transaction' => $transaction]);

    } catch (Throwable $e) {
        throw $e;
    }
}


    /**
     * EMPRESA → Exibir comprovante de pagamento
     */
    public function showQrPaymentReceipt()
    {
        if (!isset($_SESSION['user_id']) || empty($_SESSION['company_id'])) {
            throw new \Exception('Acesso negado.', 403);
        }
        $token = $_GET['token'] ?? null;
        if (!$token) {
            throw new \Exception('Token da transação não fornecido.');
        }

        try {
            $pdo = Connection::getPdo();
            $stmt = $pdo->prepare("
                SELECT pt.amount, pt.completed_at, pt.transaction_token,
                       o.name as operator_name, u.name as user_name, s.name as store_name
                FROM payment_tokens pt
                LEFT JOIN wallet_transactions wt ON pt.transaction_token = wt.origin_ref AND wt.type = 'debit'
                LEFT JOIN operator_wallets w ON wt.wallet_id = w.id
                LEFT JOIN operators o ON w.operator_id = o.id
                LEFT JOIN users u ON pt.created_by_user_id = u.id
                LEFT JOIN stores s ON pt.store_id = s.id
                WHERE pt.transaction_token = ? AND pt.company_id = ?
            ");
            $stmt->execute([$token, $_SESSION['company_id']]);
            $receiptData = $stmt->fetch();

            if (!$receiptData) {
                throw new \Exception('Comprovante de pagamento não encontrado ou acesso não permitido.');
            }

            $this->view('empresa/pagamentos/comprovante_qrcode', ['receipt' => $receiptData]);

        } catch (Throwable $e) {
            throw $e;
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Database\Connection;
use Throwable;
use chillerlan\QRCode\QRCode;

class PaymentController extends BaseController
{
    /**
     * Gera um QR Code para uma cobrança e registra a transação como pendente.
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
                'store_id'     => $_SESSION['store_id'] ?? 0,
                'company_id'   => $_SESSION['company_id'],
                'user_id'      => $_SESSION['user_id'],
                'amount'       => $amount,
                'token'        => bin2hex(random_bytes(32)),
                'timestamp'    => time()
            ];

            $payloadJson = json_encode($payload);
            $signature = hash_hmac('sha256', $payloadJson, $hmacSecret);
            $qrContent = json_encode(['payload' => $payload, 'signature' => $signature]);

            $stmt = $pdo->prepare(
                "INSERT INTO payment_tokens (company_id, store_id, created_by_user_id, transaction_token, amount) VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$payload['company_id'], $payload['store_id'], $payload['user_id'], $payload['token'], $payload['amount']]);

            $qrDataUri = (new QRCode)->render($qrContent);
            \generate_csrf_token();

            \json_response([
                'status'              => 'success',
                'qr_code_data_uri'    => $qrDataUri,
                'transaction_token'   => $payload['token'],
                'new_token'           => $_SESSION['csrf_token']
            ]);

        } catch (Throwable $e) {
            if (session_status() === PHP_SESSION_ACTIVE && empty($_SESSION['csrf_token'])) \generate_csrf_token();
            $errorCode = in_array($e->getCode(), [400, 403]) ? $e->getCode() : 500;
            \json_response([
                'status'    => 'error',
                'message'   => 'Erro interno ao gerar QR Code: ' . $e->getMessage(),
                'new_token' => $_SESSION['csrf_token'] ?? null
            ], $errorCode);
        }
    }

    /**
     * Processa um pagamento via QR Code, validando-o e debitando da carteira do operador.
     */
    public function consumeQrCode()
    {
        if (!isset($_SESSION['operator_id'])) {
            \json_response(['status' => 'error', 'message' => 'Acesso negado. Apenas operadores podem realizar pagamentos.'], 403);
        }
        \verify_csrf_token();

        $qrContent = $_POST['qr_content'] ?? null;
        $password = $_POST['password'] ?? null; // Pega a senha

        if (!$qrContent || !$password) {
            \json_response(['status' => 'error', 'message' => 'Dados incompletos. Conteúdo do QR Code e senha são obrigatórios.'], 400);
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

            if (time() - ($payload['timestamp'] ?? 0) > 120) { // QR Code expira em 2 minutos
                throw new \Exception("QR Code expirado. Por favor, peça para gerar um novo.", 400);
            }

            $pdo->beginTransaction();

            $operatorId = $_SESSION['operator_id'];
            
            // --- VERIFICAÇÃO DE SENHA ADICIONADA ---
            $stmtOp = $pdo->prepare("SELECT password FROM operators WHERE id = ?");
            $stmtOp->execute([$operatorId]);
            $operator = $stmtOp->fetch();
            
            if (!$operator || !password_verify($password, $operator['password'])) {
                throw new \Exception("Senha inválida.", 403);
            }
            // --- FIM DA VERIFICAÇÃO DE SENHA ---

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
     * Endpoint para o JavaScript (polling) verificar o status de um pagamento.
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
}
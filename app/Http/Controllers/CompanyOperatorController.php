<?php

namespace App\Http\Controllers;

use App\Database\Connection;
use PDOException;
use Exception;
use Throwable;

class CompanyOperatorController extends BaseController
{
    /**
     * Bloqueia um operador para que não veja mais vagas desta empresa.
     */
    public function block()
    {
        try {
            if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['gerente', 'administrador'])) {
                throw new Exception('Acesso negado.', 403);
            }
            \verify_csrf_token(); // <-- CORREÇÃO APLICADA

            $operatorId = $_POST['operator_id'] ?? null;
            $companyId = $_SESSION['company_id'];
            $managerId = $_SESSION['user_id'];

            if (!$operatorId) throw new Exception('ID do operador não fornecido.');

            $pdo = Connection::getPdo();
            $sql = "INSERT INTO company_operator_blocks (company_id, operator_id, blocked_by_user_id) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$companyId, $operatorId, $managerId]);

            if (\is_ajax_request()) { // <-- CORREÇÃO APLICADA
                \generate_csrf_token();
                \json_response([ // <-- CORREÇÃO APLICADA
                    'status' => 'success', 
                    'message' => 'Operador bloqueado com sucesso.',
                    'new_token' => $_SESSION['csrf_token']
                ]);
            }

            \flash('Operador bloqueado com sucesso.', 'success'); // <-- CORREÇÃO APLICADA
            \redirect_back(); // <-- CORREÇÃO APLICADA

        } catch (Throwable $e) {
            $message = 'Ocorreu um erro ao tentar bloquear o operador.';
            if ($e instanceof PDOException && $e->errorInfo[1] == 1062) {
                $message = 'Este operador já está bloqueado.';
            }

            if (\is_ajax_request()) { // <-- CORREÇÃO APLICADA
                \generate_csrf_token();
                \json_response(['status' => 'error', 'message' => $message, 'new_token' => $_SESSION['csrf_token']], 400); // <-- CORREÇÃO APLICADA
            }

            \flash($message, 'error'); // <-- CORREÇÃO APLICADA
            \redirect_back(); // <-- CORREÇÃO APLICADA
        }
    }

    /**
     * Desbloqueia um operador.
     */
    public function unblock()
    {
        try {
            if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['gerente', 'administrador'])) {
                throw new Exception('Acesso negado.', 403);
            }
            \verify_csrf_token(); // <-- CORREÇÃO APLICADA

            $operatorId = $_POST['operator_id'] ?? null;
            $companyId = $_SESSION['company_id'];

            if (!$operatorId) throw new Exception('ID do operador não fornecido.');
            
            $pdo = Connection::getPdo();
            $sql = "DELETE FROM company_operator_blocks WHERE company_id = ? AND operator_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$companyId, $operatorId]);

            if (\is_ajax_request()) { // <-- CORREÇÃO APLICADA
                \generate_csrf_token();
                \json_response([ // <-- CORREÇÃO APLICADA
                    'status' => 'success', 
                    'message' => 'Operador desbloqueado com sucesso.',
                    'new_token' => $_SESSION['csrf_token']
                ]);
            }

            \flash('Operador desbloqueado com sucesso.', 'success'); // <-- CORREÇÃO APLICADA
            \redirect_back(); // <-- CORREÇÃO APLICADA

        } catch (Throwable $e) {
            $message = 'Ocorreu um erro ao tentar desbloquear o operador.';

            if (\is_ajax_request()) { // <-- CORREÇÃO APLICADA
                \generate_csrf_token();
                \json_response(['status' => 'error', 'message' => $message, 'new_token' => $_SESSION['csrf_token']], 400); // <-- CORREÇÃO APLICADA
            }

            \flash($message, 'error'); // <-- CORREÇÃO APLICADA
            \redirect_back(); // <-- CORREÇÃO APLICADA
        }
    }
}
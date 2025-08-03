<?php

namespace App\Http\Controllers;

use App\Database\Connection;
use PDOException;
use Exception;

// A classe agora herda do nosso BaseController
class UserController extends BaseController
{
    /**
     * Mostra a lista de utilizadores de uma empresa.
     */
    public function index()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            throw new Exception('Acesso negado.', 403);
        }
        $companyId = $_GET['company_id'] ?? null;
        if (!$companyId) {
            throw new Exception('ID da empresa não fornecido.');
        }

        try {
            $pdo = Connection::getPdo();
            $stmtCompany = $pdo->prepare('SELECT nome_fantasia FROM companies WHERE id = ?');
            $stmtCompany->execute([$companyId]);
            $company = $stmtCompany->fetch();
            if (!$company) {
                throw new Exception('Empresa não encontrada.');
            }
            $companyName = $company['nome_fantasia'];

            $stmtUsers = $pdo->prepare('SELECT id, name, email, role, status FROM users WHERE company_id = ?');
            $stmtUsers->execute([$companyId]);
            $users = $stmtUsers->fetchAll();

            // Usa o método view() para renderizar a página, passando os dados
            $this->view('admin/utilizadores/listar', [
                'users' => $users,
                'companyName' => $companyName,
                'companyId' => $companyId
            ]);

        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * Mostra o formulário de edição de um utilizador.
     */
    public function edit()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            throw new Exception('Acesso negado.', 403);
        }
        $userId = $_GET['id'] ?? null;
        if (!$userId) {
            throw new Exception('ID do utilizador não fornecido.');
        }

        try {
            $pdo = Connection::getPdo();
            $stmt = $pdo->prepare('SELECT id, name, email, role, company_id FROM users WHERE id = ?');
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            if (!$user) {
                throw new Exception('Utilizador não encontrado.');
            }
            $this->view('admin/utilizadores/editar', ['user' => $user]);
        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * Atualiza os dados de um utilizador.
     */
    public function update()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            throw new Exception('Acesso negado.', 403);
        }
        // Proteção CSRF
        verify_csrf_token();
        
        $id = $_POST['id'] ?? null;
        $companyId = $_POST['company_id'] ?? null;
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $role = $_POST['role'] ?? '';
        $password = $_POST['password'] ?? '';

        if (!$id || !$companyId) {
            throw new Exception('IDs em falta.');
        }

        try {
            $pdo = Connection::getPdo();
            if (!empty($password)) {
                // Validação de força de senha
                if (!preg_match('/^(?=.*\d)(?=.*[A-Z])(?=.*[a-z]).{8,}$/', $password)) {
                    throw new Exception('A nova senha deve ter pelo menos 8 caracteres, incluindo um número, uma letra maiúscula e uma minúscula.');
                }
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET name = ?, email = ?, role = ?, password = ? WHERE id = ?";
                $params = [$name, $email, $role, $passwordHash, $id];
            } else {
                $sql = "UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?";
                $params = [$name, $email, $role, $id];
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            // Mensagem flash para feedback
            flash('Utilizador atualizado com sucesso!');
            header('Location: /admin/utilizadores?company_id=' . $companyId);
            exit();
        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * Alterna o status de um utilizador (ativo/inativo).
     */
    public function toggleStatus()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            throw new Exception('Acesso negado.', 403);
        }
        // Proteção CSRF para uma ação que altera dados
        verify_csrf_token();
        $userId = $_POST['id'] ?? null; // Alterado para POST

        if (!$userId) {
            throw new Exception('ID do utilizador não fornecido.');
        }

        $pdo = Connection::getPdo();
        try {
            $stmt = $pdo->prepare('SELECT status, company_id FROM users WHERE id = ?');
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            if (!$user) {
                throw new Exception('Utilizador não encontrado.');
            }
            
            $newStatus = ($user['status'] == 1) ? 0 : 1;
            
            $updateStmt = $pdo->prepare('UPDATE users SET status = ? WHERE id = ?');
            $updateStmt->execute([$newStatus, $userId]);

            flash('Status do utilizador alterado com sucesso!');
            header('Location: /admin/utilizadores?company_id=' . $user['company_id']);
            exit();
        } catch (PDOException $e) {
            throw $e;
        }
    }
}
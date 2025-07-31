<?php

require_once __DIR__ . '/../../Database/Connection.php';

class UserController
{
    public function index()
    {
        $companyId = $_GET['company_id'] ?? null;
        if (!$companyId) die('ID da empresa não fornecido.');

        try {
            $pdo = Connection::getPdo();
            $stmtCompany = $pdo->prepare('SELECT nome_fantasia FROM companies WHERE id = ?');
            $stmtCompany->execute([$companyId]);
            $company = $stmtCompany->fetch();
            if (!$company) die('Empresa não encontrada.');
            $companyName = $company['nome_fantasia'];

            // CORREÇÃO: Adicionamos a coluna 'status' à consulta
            $stmtUsers = $pdo->prepare('SELECT id, name, email, role, status FROM users WHERE company_id = ?');
            $stmtUsers->execute([$companyId]);
            $users = $stmtUsers->fetchAll();

            require_once __DIR__ . '/../../Views/admin/listar_utilizadores.php';
        } catch (\PDOException $e) {
            die('Erro ao buscar os utilizadores: ' . $e->getMessage());
        }
    }

    public function edit()
    {
        $userId = $_GET['id'] ?? null;
        if (!$userId) die('ID do utilizador não fornecido.');

        try {
            $pdo = Connection::getPdo();
            $stmt = $pdo->prepare('SELECT id, name, email, role, company_id FROM users WHERE id = ?');
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            if (!$user) die('Utilizador não encontrado.');
            require_once __DIR__ . '/../../Views/admin/editar_utilizador.php';
        } catch (\PDOException $e) {
            die('Erro ao buscar os dados do utilizador: ' . $e->getMessage());
        }
    }

    public function update()
    {
        $id = $_POST['id'] ?? null;
        $companyId = $_POST['company_id'] ?? null;
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $role = $_POST['role'] ?? '';
        $password = $_POST['password'] ?? '';

        if (!$id || !$companyId) die('Erro: IDs em falta.');

        try {
            $pdo = Connection::getPdo();
            if (!empty($password)) {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET name = ?, email = ?, role = ?, password = ? WHERE id = ?";
                $params = [$name, $email, $role, $passwordHash, $id];
            } else {
                $sql = "UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?";
                $params = [$name, $email, $role, $id];
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            header('Location: /admin/utilizadores?company_id=' . $companyId);
            exit();
        } catch (\PDOException $e) {
            die('Erro ao atualizar o utilizador: ' . $e->getMessage());
        }
    }

    /**
     * Alterna o status de um utilizador (ativo/inativo).
     */
    public function toggleStatus()
    {
        $userId = $_GET['id'] ?? null;
        if (!$userId) die('ID do utilizador não fornecido.');

        $pdo = Connection::getPdo();
        try {
            // Primeiro, precisamos do company_id para o redirecionamento
            $stmt = $pdo->prepare('SELECT status, company_id FROM users WHERE id = ?');
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            if (!$user) die('Utilizador não encontrado.');

            // Inverte o status
            $newStatus = ($user['status'] == 1) ? 0 : 1;

            // Atualiza a base de dados
            $updateStmt = $pdo->prepare('UPDATE users SET status = ? WHERE id = ?');
            $updateStmt->execute([$newStatus, $userId]);

            // Redireciona de volta para a lista de utilizadores da empresa correta
            header('Location: /admin/utilizadores?company_id=' . $user['company_id']);
            exit();
        } catch (\PDOException $e) {
            die('Erro ao alterar o status do utilizador: ' . $e->getMessage());
        }
    }
}
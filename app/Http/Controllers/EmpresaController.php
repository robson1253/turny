<?php

require_once __DIR__ . '/../../Database/Connection.php';
require_once __DIR__ . '/../../Utils/Validators.php';

class EmpresaController 
{
    /**
     * Mostra a lista de empresas.
     */
    public function index()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403); die('Acesso negado.');
        }
        try {
            $pdo = Connection::getPdo();
            // A query agora busca menos campos para a listagem principal
            $stmt = $pdo->query('SELECT id, razao_social, nome_fantasia, status FROM companies ORDER BY created_at DESC');
            $companies = $stmt->fetchAll();
            require_once __DIR__ . '/../../Views/admin/empresas/listar_empresas.php';
        } catch (\PDOException $e) {
            die('Erro ao buscar as empresas: ' . $e->getMessage());
        }
    }

    /**
     * Mostra o formulário de edição de empresa (simplificado).
     */
    public function edit()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403); die('Acesso negado.');
        }
        $id = $_GET['id'] ?? null;
        if (!$id) die('ID da empresa não fornecido.');
        try {
            $pdo = Connection::getPdo();
            // Busca apenas os dados da empresa-mãe
            $stmt = $pdo->prepare('SELECT id, razao_social, nome_fantasia, telefone, contact_email FROM companies WHERE id = ?');
            $stmt->execute([$id]);
            $company = $stmt->fetch();
            if (!$company) die('Empresa não encontrada.');
            require_once __DIR__ . '/../../Views/admin/empresas/editar_empresa.php';
        } catch (\PDOException $e) {
            die('Erro ao buscar os dados da empresa: ' . $e->getMessage());
        }
    }

    /**
     * Atualiza os dados da empresa (simplificado).
     */
    public function update()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403); die('Acesso negado.');
        }
        $id = $_POST['id'] ?? null;
        $razao_social = $_POST['razao_social'] ?? '';
        $nome_fantasia = $_POST['nome_fantasia'] ?? '';
        $telefone = $_POST['telefone'] ?? '';
        $contact_email = $_POST['contact_email'] ?? '';
        if (!$id) die('Erro: ID em falta.');
        try {
            $pdo = Connection::getPdo();
            // Query de UPDATE simplificada
            $sql = "UPDATE companies SET razao_social = ?, nome_fantasia = ?, telefone = ?, contact_email = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$razao_social, $nome_fantasia, $telefone, $contact_email, $id]);
            header('Location: /admin/empresas');
            exit();
        } catch (\PDOException $e) {
            die('Erro ao atualizar a empresa: ' . $e->getMessage());
        }
    }
    
    /**
     * Mostra o formulário de criação de empresa (simplificado).
     */
    public function showCreateForm()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403); die('Acesso negado.');
        }
        require_once __DIR__ . '/../../Views/admin/empresas/criar_empresa.php';
    }

    /**
     * Guarda a conta da empresa e os seus utilizadores (simplificado).
     */
    public function store()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403); die('Acesso negado.');
        }

        // Pega apenas os dados da empresa-mãe, sem endereço ou CNPJ
        $razao_social = $_POST['razao_social'] ?? '';
        $nome_fantasia = $_POST['nome_fantasia'] ?? '';
        $telefone = $_POST['telefone'] ?? '';
        $contact_email = $_POST['contact_email'] ?? '';

        if(empty($razao_social) || empty($nome_fantasia)) die('Razão Social e Nome Fantasia são obrigatórios.');

        $pdo = Connection::getPdo();
        try {
            $pdo->beginTransaction();
            
            // Query de INSERT simplificada
            $sqlCompany = "INSERT INTO companies (razao_social, nome_fantasia, telefone, contact_email) VALUES (?, ?, ?, ?)";
            $stmtCompany = $pdo->prepare($sqlCompany);
            $stmtCompany->execute([$razao_social, $nome_fantasia, $telefone, $contact_email]);
            $companyId = $pdo->lastInsertId();

            // A criação dos 3 utilizadores associados à empresa continua igual
            $sqlUser = "INSERT INTO users (name, email, password, role, company_id) VALUES (?, ?, ?, ?, ?)";
            $stmtUser = $pdo->prepare($sqlUser);
            $stmtUser->execute([$_POST['admin_name'], $_POST['admin_email'], password_hash($_POST['admin_password'], PASSWORD_DEFAULT), 'administrador', $companyId]);
            $stmtUser->execute([$_POST['manager_name'], $_POST['manager_email'], password_hash($_POST['manager_password'], PASSWORD_DEFAULT), 'gerente', $companyId]);
            $stmtUser->execute([$_POST['receptionist_name'], $_POST['receptionist_email'], password_hash($_POST['receptionist_password'], PASSWORD_DEFAULT), 'recepcionista', $companyId]);

            $pdo->commit();
            // Após criar a empresa, redireciona para a página de gestão de lojas da nova empresa
            header('Location: /admin/stores?company_id=' . $companyId);
            exit();
        } catch (\PDOException $e) {
            $pdo->rollBack();
            die('Erro ao criar a conta da empresa: ' . $e->getMessage());
        }
    }

    /**
     * Alterna o status de uma empresa (ativo/inativo).
     */
    public function toggleStatus() 
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403); die('Acesso negado.');
        }
        $id = $_GET['id'] ?? null;
        if (!$id) die('ID da empresa não fornecido.');
        $pdo = Connection::getPdo();
        try {
            $stmt = $pdo->prepare('SELECT status FROM companies WHERE id = ?');
            $stmt->execute([$id]);
            $company = $stmt->fetch();
            if (!$company) die('Empresa não encontrada.');
            $newStatus = ($company['status'] == 1) ? 0 : 1;
            $updateStmt = $pdo->prepare('UPDATE companies SET status = ? WHERE id = ?');
            $updateStmt->execute([$newStatus, $id]);
            header('Location: /admin/empresas');
            exit();
        } catch (\PDOException $e) {
            die('Erro ao alterar o status da empresa: ' . $e->getMessage());
        }
    }
}
<?php

namespace App\Http\Controllers;

use App\Database\Connection;
use PDOException;
use Exception;

// A classe já herda corretamente do BaseController
class EmpresaController extends BaseController
{
    /**
     * Mostra a lista de empresas.
     */
    public function index()
    {
        // A verificação de segurança agora é uma única linha!
        $this->checkAccess(['admin']);
        
        try {
            $pdo = Connection::getPdo();
            $stmt = $pdo->query('SELECT id, razao_social, nome_fantasia, status FROM companies ORDER BY created_at DESC');
            $companies = $stmt->fetchAll();
            
            $this->view('admin/empresas/listar_empresas', ['companies' => $companies]);

        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * Mostra o formulário de edição de empresa.
     */
    public function edit()
    {
        $this->checkAccess(['admin']);
        $id = $_GET['id'] ?? null;
        if (!$id) {
            throw new Exception('ID da empresa não fornecido.');
        }
        try {
            $pdo = Connection::getPdo();
            $stmt = $pdo->prepare('SELECT id, razao_social, nome_fantasia, telefone, contact_email FROM companies WHERE id = ?');
            $stmt->execute([$id]);
            $company = $stmt->fetch();
            if (!$company) {
                throw new Exception('Empresa não encontrada.');
            }
            $this->view('admin/empresas/editar_empresa', ['company' => $company]);
        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * Atualiza os dados da empresa.
     */
    public function update()
    {
        $this->checkAccess(['admin']);
        verify_csrf_token();

        $id = $_POST['id'] ?? null;
        $razao_social = $_POST['razao_social'] ?? '';
        $nome_fantasia = $_POST['nome_fantasia'] ?? '';
        $telefone = $_POST['telefone'] ?? '';
        $contact_email = $_POST['contact_email'] ?? '';

        if (!$id) {
            throw new Exception('ID em falta.');
        }

        $telefoneLimpo = preg_replace('/[^0-9]/', '', $telefone);

        try {
            $pdo = Connection::getPdo();
            $sql = "UPDATE companies SET razao_social = ?, nome_fantasia = ?, telefone = ?, contact_email = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$razao_social, $nome_fantasia, $telefoneLimpo, $contact_email, $id]);
            
            flash('Empresa atualizada com sucesso!');
            header('Location: /admin/empresas');
            exit();
        } catch (PDOException $e) {
            throw $e;
        }
    }
    
    /**
     * Mostra o formulário de criação de empresa.
     */
    public function showCreateForm()
    {
        $this->checkAccess(['admin']);
        $this->view('admin/empresas/criar_empresa');
    }

    /**
     * Guarda a conta da empresa e os seus utilizadores.
     */
    public function store()
    {
        $this->checkAccess(['admin']);
        verify_csrf_token();
        
        $razao_social = $_POST['razao_social'] ?? '';
        $nome_fantasia = $_POST['nome_fantasia'] ?? '';
        $telefone = $_POST['telefone'] ?? '';
        $contact_email = $_POST['contact_email'] ?? '';
        $telefoneLimpo = preg_replace('/[^0-9]/', '', $telefone);

        if(empty($razao_social) || empty($nome_fantasia)) {
            throw new Exception('Razão Social e Nome Fantasia são obrigatórios.');
        }
        if(empty($_POST['admin_password']) || empty($_POST['manager_password']) || empty($_POST['receptionist_password'])) {
            throw new Exception('Todas as senhas dos utilizadores são obrigatórias.');
        }

        $pdo = Connection::getPdo();
        try {
            $pdo->beginTransaction();
            
            $sqlCompany = "INSERT INTO companies (razao_social, nome_fantasia, telefone, contact_email) VALUES (?, ?, ?, ?)";
            $stmtCompany = $pdo->prepare($sqlCompany);
            $stmtCompany->execute([$razao_social, $nome_fantasia, $telefoneLimpo, $contact_email]);
            $companyId = $pdo->lastInsertId();

            $sqlUser = "INSERT INTO users (name, email, password, role, company_id) VALUES (?, ?, ?, ?, ?)";
            $stmtUser = $pdo->prepare($sqlUser);
            $stmtUser->execute([$_POST['admin_name'], $_POST['admin_email'], password_hash($_POST['admin_password'], PASSWORD_DEFAULT), 'administrador', $companyId]);
            $stmtUser->execute([$_POST['manager_name'], $_POST['manager_email'], password_hash($_POST['manager_password'], PASSWORD_DEFAULT), 'gerente', $companyId]);
            $stmtUser->execute([$_POST['receptionist_name'], $_POST['receptionist_email'], password_hash($_POST['receptionist_password'], PASSWORD_DEFAULT), 'recepcionista', $companyId]);

            $pdo->commit();
            
            flash('Empresa e utilizadores criados com sucesso!');
            header('Location: /admin/stores?company_id=' . $companyId);
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Alterna o status de uma empresa (ativo/inativo).
     */
    public function toggleStatus() 
    {
        $this->checkAccess(['admin']);
        verify_csrf_token();
        $id = $_POST['id'] ?? null;

        if (!$id) {
            throw new Exception('ID da empresa não fornecido.');
        }
        $pdo = Connection::getPdo();
        try {
            $stmt = $pdo->prepare('SELECT status FROM companies WHERE id = ?');
            $stmt->execute([$id]);
            $company = $stmt->fetch();
            if (!$company) {
                throw new Exception('Empresa não encontrada.');
            }
            $newStatus = ($company['status'] == 1) ? 0 : 1;
            $updateStmt = $pdo->prepare('UPDATE companies SET status = ? WHERE id = ?');
            $updateStmt->execute([$newStatus, $id]);
            
            flash('Status da empresa alterado com sucesso!');
            header('Location: /admin/empresas');
            exit();
        } catch (PDOException $e) {
            throw $e;
        }
    }
}

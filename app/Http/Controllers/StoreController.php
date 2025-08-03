<?php

namespace App\Http\Controllers;

use App\Database\Connection;
use App\Utils\Validators;
use PDOException;
use Exception;

class StoreController extends BaseController
{
    /**
     * Mostra a lista de lojas de uma empresa específica.
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
            $stmtCompany = $pdo->prepare("SELECT id, nome_fantasia FROM companies WHERE id = ?");
            $stmtCompany->execute([$companyId]);
            $company = $stmtCompany->fetch();
            if (!$company) {
                throw new Exception('Empresa não encontrada.');
            }

            $stmtStores = $pdo->prepare("
                SELECT s.*, e.name as erp_system_name 
                FROM stores s
                LEFT JOIN erp_systems e ON s.erp_system_id = e.id
                WHERE s.company_id = ? 
                ORDER BY s.name ASC
            ");
            $stmtStores->execute([$companyId]);
            $stores = $stmtStores->fetchAll();

            $this->view('admin/stores/listar', [
                'stores' => $stores,
                'company' => $company
            ]);

        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * Mostra o formulário para criar uma nova loja para uma empresa.
     */
    public function showCreateForm()
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
            $stmtCompany = $pdo->prepare("SELECT id, nome_fantasia FROM companies WHERE id = ?");
            $stmtCompany->execute([$companyId]);
            $company = $stmtCompany->fetch();
            if (!$company) {
                throw new Exception('Empresa não encontrada.');
            }

            $erpSystems = $pdo->query("SELECT * FROM erp_systems ORDER BY name ASC")->fetchAll();

            $this->view('admin/stores/criar', [
                'company' => $company,
                'erpSystems' => $erpSystems
            ]);

        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * Guarda uma nova loja na base de dados.
     */
    public function store()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            throw new Exception('Acesso negado.', 403);
        }
        verify_csrf_token();

        $companyId = $_POST['company_id'] ?? null;
        $name = $_POST['name'] ?? '';
        $erp_system_id = $_POST['erp_system_id'] ?? null;
        
        // Limpeza e padronização de dados
        $cnpj = preg_replace('/[^0-9]/', '', $_POST['cnpj'] ?? '');
        $inscricao_estadual = preg_replace('/[^0-9]/', '', $_POST['inscricao_estadual'] ?? '');
        $cep = preg_replace('/[^0-9]/', '', $_POST['cep'] ?? '');

        $endereco = $_POST['endereco'] ?? '';
        $numero = $_POST['numero'] ?? '';
        $bairro = $_POST['bairro'] ?? '';
        $cidade = $_POST['cidade'] ?? '';
        $estado = $_POST['estado'] ?? '';

        if (!$companyId || empty($name) || empty($erp_system_id) || empty($cnpj)) {
            throw new Exception('Campos essenciais como Nome da Loja, ERP e CNPJ são obrigatórios.');
        }
        if (!Validators::validateCnpj($cnpj)) {
            throw new Exception('O CNPJ fornecido para a loja é inválido.');
        }

        try {
            $pdo = Connection::getPdo();
            $sql = "INSERT INTO stores (company_id, name, erp_system_id, cnpj, inscricao_estadual, cep, endereco, numero, bairro, cidade, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$companyId, $name, $erp_system_id, $cnpj, $inscricao_estadual, $cep, $endereco, $numero, $bairro, $cidade, $estado]);

            flash('Loja criada com sucesso!');
            header('Location: /admin/stores?company_id=' . $companyId);
            exit();
        } catch (PDOException $e) {
             if ($e->errorInfo[1] == 1062) {
                throw new Exception('O CNPJ desta loja já está registado.');
            }
            throw $e;
        }
    }
    
    /**
     * Mostra o formulário de edição de uma loja.
     */
    public function edit()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            throw new Exception('Acesso negado.', 403);
        }
        $storeId = $_GET['id'] ?? null;
        if (!$storeId) {
            throw new Exception('ID da loja não fornecido.');
        }

        try {
            $pdo = Connection::getPdo();
            $stmtStore = $pdo->prepare("SELECT * FROM stores WHERE id = ?");
            $stmtStore->execute([$storeId]);
            $store = $stmtStore->fetch();
            if (!$store) {
                throw new Exception('Loja não encontrada.');
            }
            
            $erpSystems = $pdo->query("SELECT * FROM erp_systems ORDER BY name ASC")->fetchAll();

            $this->view('admin/stores/editar', [
                'store' => $store,
                'erpSystems' => $erpSystems
            ]);

        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * Atualiza os dados de uma loja.
     */
    public function update()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            throw new Exception('Acesso negado.', 403);
        }
        verify_csrf_token();

        $storeId = $_POST['id'] ?? null;
        $companyId = $_POST['company_id'] ?? null;
        $name = $_POST['name'] ?? '';
        $erp_system_id = $_POST['erp_system_id'] ?? null;
        
        // Limpeza e padronização de dados
        $cnpj = preg_replace('/[^0-9]/', '', $_POST['cnpj'] ?? '');
        $inscricao_estadual = preg_replace('/[^0-9]/', '', $_POST['inscricao_estadual'] ?? '');
        $cep = preg_replace('/[^0-9]/', '', $_POST['cep'] ?? '');

        $endereco = $_POST['endereco'] ?? '';
        $numero = $_POST['numero'] ?? '';
        $bairro = $_POST['bairro'] ?? '';
        $cidade = $_POST['cidade'] ?? '';
        $estado = $_POST['estado'] ?? '';
        
        if (!$storeId || !$companyId || empty($name) || empty($erp_system_id) || empty($cnpj)) {
            throw new Exception('Campos essenciais como Nome da Loja, ERP e CNPJ são obrigatórios.');
        }
        if (!Validators::validateCnpj($cnpj)) {
            throw new Exception('CNPJ inválido.');
        }

        try {
            $pdo = Connection::getPdo();
            $sql = "UPDATE stores SET name = ?, erp_system_id = ?, cnpj = ?, inscricao_estadual = ?, cep = ?, endereco = ?, numero = ?, bairro = ?, cidade = ?, estado = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$name, $erp_system_id, $cnpj, $inscricao_estadual, $cep, $endereco, $numero, $bairro, $cidade, $estado, $storeId]);

            flash('Loja atualizada com sucesso!');
            header('Location: /admin/stores?company_id=' . $companyId);
            exit();
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                throw new Exception('O CNPJ desta loja já está registado.');
            }
            throw $e;
        }
    }

    /**
     * Alterna o status de uma loja (ativa/inativa).
     */
    public function toggleStatus()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            throw new Exception('Acesso negado.', 403);
        }
        verify_csrf_token();
        $storeId = $_POST['id'] ?? null;

        if (!$storeId) {
            throw new Exception('ID da loja não fornecido.');
        }

        $pdo = Connection::getPdo();
        try {
            $stmt = $pdo->prepare('SELECT status, company_id FROM stores WHERE id = ?');
            $stmt->execute([$storeId]);
            $store = $stmt->fetch();

            if (!$store) {
                throw new Exception('Loja não encontrada.');
            }

            $newStatus = ($store['status'] == 1) ? 0 : 1;

            $updateStmt = $pdo->prepare('UPDATE stores SET status = ? WHERE id = ?');
            $updateStmt->execute([$newStatus, $storeId]);

            flash('Status da loja alterado com sucesso!');
            header('Location: /admin/stores?company_id=' . $store['company_id']);
            exit();

        } catch (PDOException $e) {
            throw $e;
        }
    }
}
<?php
require_once __DIR__ . '/../../Database/Connection.php';
require_once __DIR__ . '/../../Utils/Validators.php';

class StoreController
{
    /**
     * Mostra a lista de lojas de uma empresa específica.
     */
    public function index()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403); die('Acesso negado.');
        }

        $companyId = $_GET['company_id'] ?? null;
        if (!$companyId) die('ID da empresa não fornecido.');

        try {
            $pdo = Connection::getPdo();
            $stmtCompany = $pdo->prepare("SELECT id, nome_fantasia FROM companies WHERE id = ?");
            $stmtCompany->execute([$companyId]);
            $company = $stmtCompany->fetch();
            if (!$company) die('Empresa não encontrada.');

            $stmtStores = $pdo->prepare("
                SELECT s.*, e.name as erp_system_name 
                FROM stores s
                LEFT JOIN erp_systems e ON s.erp_system_id = e.id
                WHERE s.company_id = ? 
                ORDER BY s.name ASC
            ");
            $stmtStores->execute([$companyId]);
            $stores = $stmtStores->fetchAll();

            require_once __DIR__ . '/../../Views/admin/stores/listar.php';
        } catch (\PDOException $e) {
            die('Erro ao buscar as lojas: ' . $e->getMessage());
        }
    }

    /**
     * Mostra o formulário para criar uma nova loja para uma empresa.
     */
    public function showCreateForm()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403); die('Acesso negado.');
        }
        $companyId = $_GET['company_id'] ?? null;
        if (!$companyId) die('ID da empresa não fornecido.');

        try {
            $pdo = Connection::getPdo();
            $stmtCompany = $pdo->prepare("SELECT nome_fantasia FROM companies WHERE id = ?");
            $stmtCompany->execute([$companyId]);
            $company = $stmtCompany->fetch();
            if (!$company) die('Empresa não encontrada.');

            $erpSystems = $pdo->query("SELECT * FROM erp_systems ORDER BY name ASC")->fetchAll();

            require_once __DIR__ . '/../../Views/admin/stores/criar.php';
        } catch (\PDOException $e) {
            die('Erro ao carregar a página: ' . $e->getMessage());
        }
    }

    /**
     * Guarda uma nova loja na base de dados.
     */
    public function store()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403); die('Acesso negado.');
        }

        $companyId = $_POST['company_id'] ?? null;
        $name = $_POST['name'] ?? '';
        $erp_system_id = $_POST['erp_system_id'] ?? null;
        $cnpj = $_POST['cnpj'] ?? '';
        $inscricao_estadual = $_POST['inscricao_estadual'] ?? '';
        $cep = $_POST['cep'] ?? '';
        $endereco = $_POST['endereco'] ?? '';
        $numero = $_POST['numero'] ?? '';
        $bairro = $_POST['bairro'] ?? '';
        $cidade = $_POST['cidade'] ?? '';
        $estado = $_POST['estado'] ?? '';

        if (!$companyId || empty($name) || empty($erp_system_id) || empty($cnpj) || empty($inscricao_estadual) || empty($cep) || empty($endereco) || empty($numero) || empty($bairro) || empty($cidade) || empty($estado)) {
            die('Erro: Todos os campos são obrigatórios.');
        }
        if ($cnpj && !Validators::validateCnpj($cnpj)) {
            die('Erro: O CNPJ fornecido para a loja é inválido.');
        }

        try {
            $pdo = Connection::getPdo();
            $sql = "INSERT INTO stores (company_id, name, erp_system_id, cnpj, inscricao_estadual, cep, endereco, numero, bairro, cidade, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$companyId, $name, $erp_system_id, $cnpj, $inscricao_estadual, $cep, $endereco, $numero, $bairro, $cidade, $estado]);

            header('Location: /admin/stores?company_id=' . $companyId);
            exit();
        } catch (\PDOException $e) {
             if ($e->errorInfo[1] == 1062) {
                die('Erro: O CNPJ desta loja já está registado.');
            }
            die('Erro ao guardar a loja: ' . $e->getMessage());
        }
    }
    
    /**
     * Mostra o formulário de edição de uma loja.
     */
    public function edit()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403); die('Acesso negado.');
        }
        $storeId = $_GET['id'] ?? null;
        if (!$storeId) die('ID da loja não fornecido.');

        try {
            $pdo = Connection::getPdo();
            $stmtStore = $pdo->prepare("SELECT * FROM stores WHERE id = ?");
            $stmtStore->execute([$storeId]);
            $store = $stmtStore->fetch();
            if (!$store) die('Loja não encontrada.');
            
            $erpSystems = $pdo->query("SELECT * FROM erp_systems ORDER BY name ASC")->fetchAll();

            require_once __DIR__ . '/../../Views/admin/stores/editar.php';
        } catch (\PDOException $e) {
            die('Erro ao carregar a página de edição: ' . $e->getMessage());
        }
    }

    /**
     * Atualiza os dados de uma loja.
     */
    public function update()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403); die('Acesso negado.');
        }
        $storeId = $_POST['id'] ?? null;
        $companyId = $_POST['company_id'] ?? null;
        $name = $_POST['name'] ?? '';
        $erp_system_id = $_POST['erp_system_id'] ?? null;
        $cnpj = $_POST['cnpj'] ?? '';
        $inscricao_estadual = $_POST['inscricao_estadual'] ?? '';
        $cep = $_POST['cep'] ?? '';
        $endereco = $_POST['endereco'] ?? '';
        $numero = $_POST['numero'] ?? '';
        $bairro = $_POST['bairro'] ?? '';
        $cidade = $_POST['cidade'] ?? '';
        $estado = $_POST['estado'] ?? '';
        
        if (!$storeId || !$companyId || empty($name) || empty($erp_system_id) || empty($cnpj) || empty($inscricao_estadual) || empty($cep) || empty($endereco) || empty($numero) || empty($bairro) || empty($cidade) || empty($estado)) {
            die('Erro: Todos os campos são obrigatórios.');
        }
        if (!Validators::validateCnpj($cnpj)) die('Erro: CNPJ inválido.');

        try {
            $pdo = Connection::getPdo();
            $sql = "UPDATE stores SET name = ?, erp_system_id = ?, cnpj = ?, inscricao_estadual = ?, cep = ?, endereco = ?, numero = ?, bairro = ?, cidade = ?, estado = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$name, $erp_system_id, $cnpj, $inscricao_estadual, $cep, $endereco, $numero, $bairro, $cidade, $estado, $storeId]);

            header('Location: /admin/stores?company_id=' . $companyId);
            exit();
        } catch (\PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                die('Erro: O CNPJ desta loja já está registado.');
            }
            die('Erro ao atualizar a loja: ' . $e->getMessage());
        }
    }

    /**
     * Alterna o status de uma loja (ativa/inativa).
     */
    public function toggleStatus()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403); die('Acesso negado.');
        }

        $storeId = $_GET['id'] ?? null;
        if (!$storeId) die('ID da loja não fornecido.');

        $pdo = Connection::getPdo();
        try {
            $stmt = $pdo->prepare('SELECT status, company_id FROM stores WHERE id = ?');
            $stmt->execute([$storeId]);
            $store = $stmt->fetch();

            if (!$store) die('Loja não encontrada.');

            $newStatus = ($store['status'] == 1) ? 0 : 1;

            $updateStmt = $pdo->prepare('UPDATE stores SET status = ? WHERE id = ?');
            $updateStmt->execute([$newStatus, $storeId]);

            header('Location: /admin/stores?company_id=' . $store['company_id']);
            exit();

        } catch (\PDOException $e) {
            die('Erro ao alterar o status da loja: ' . $e->getMessage());
        }
    }
}
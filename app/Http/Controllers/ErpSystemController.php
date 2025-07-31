<?php
require_once __DIR__ . '/../../Database/Connection.php';

class ErpSystemController
{
    /**
     * Mostra a lista de todos os sistemas ERP.
     */
    public function index()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403); die('Acesso negado.');
        }
        try {
            $pdo = Connection::getPdo();
            $stmt = $pdo->query('SELECT * FROM erp_systems ORDER BY name ASC');
            $erpSystems = $stmt->fetchAll();
            require_once __DIR__ . '/../../Views/admin/erps/listar.php';
        } catch (\PDOException $e) {
            die('Erro ao buscar os sistemas ERP: ' . $e->getMessage());
        }
    }

    /**
     * Mostra o formulário para criar um novo sistema ERP.
     */
    public function showCreateForm()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403); die('Acesso negado.');
        }
        require_once __DIR__ . '/../../Views/admin/erps/criar.php';
    }

    /**
     * Guarda um novo sistema ERP na base de dados.
     */
    public function store()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403); die('Acesso negado.');
        }
        $name = $_POST['name'] ?? '';
        if (empty($name)) {
            die('O nome do sistema é obrigatório.');
        }
        try {
            $pdo = Connection::getPdo();
            $sql = "INSERT INTO erp_systems (name) VALUES (?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$name]);
            header('Location: /admin/erps');
            exit();
        } catch (\PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                die('Erro: Já existe um sistema com este nome.');
            }
            die('Erro ao guardar o sistema ERP: ' . $e->getMessage());
        }
    }

    /**
     * Mostra o formulário de edição para um sistema ERP. (NOVA FUNÇÃO)
     */
    public function edit()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403); die('Acesso negado.');
        }
        $id = $_GET['id'] ?? null;
        if (!$id) die('ID do sistema não fornecido.');
        try {
            $pdo = Connection::getPdo();
            $stmt = $pdo->prepare("SELECT * FROM erp_systems WHERE id = ?");
            $stmt->execute([$id]);
            $erpSystem = $stmt->fetch();
            if (!$erpSystem) die('Sistema ERP não encontrado.');
            require_once __DIR__ . '/../../Views/admin/erps/editar.php';
        } catch (\PDOException $e) {
            die('Erro ao buscar o sistema ERP: ' . $e->getMessage());
        }
    }

    /**
     * Atualiza um sistema ERP na base de dados. (NOVA FUNÇÃO)
     */
    public function update()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403); die('Acesso negado.');
        }
        $id = $_POST['id'] ?? null;
        $name = $_POST['name'] ?? '';
        if (!$id || empty($name)) {
            die('ID e nome são obrigatórios.');
        }
        try {
            $pdo = Connection::getPdo();
            $sql = "UPDATE erp_systems SET name = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$name, $id]);
            header('Location: /admin/erps');
            exit();
        } catch (\PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                die('Erro: Já existe um sistema com este nome.');
            }
            die('Erro ao atualizar o sistema ERP: ' . $e->getMessage());
        }
    }

    /**
     * Apaga um sistema ERP da base de dados. (NOVA FUNÇÃO)
     */
    public function destroy()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403); die('Acesso negado.');
        }
        $id = $_GET['id'] ?? null;
        if (!$id) die('ID do sistema não fornecido.');
        try {
            $pdo = Connection::getPdo();
            $sql = "DELETE FROM erp_systems WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            header('Location: /admin/erps');
            exit();
        } catch (\PDOException $e) {
            die('Erro ao apagar o sistema ERP: ' . $e->getMessage());
        }
    }
}
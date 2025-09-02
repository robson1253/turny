<?php

namespace App\Http\Controllers;

use App\Database\Connection;
use PDOException;
use Exception;

class ErpSystemController extends BaseController
{
    /**
     * Mostra a lista de todos os sistemas ERP.
     */
    public function index()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            throw new Exception('Acesso negado.', 403);
        }
        try {
            $pdo = Connection::getPdo();
            $stmt = $pdo->query('SELECT * FROM erp_systems ORDER BY name ASC');
            $erpSystems = $stmt->fetchAll();

            $this->view('admin/erps/listar', ['erpSystems' => $erpSystems]);

        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * Mostra o formulário para criar um novo sistema ERP.
     */
    public function showCreateForm()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            throw new Exception('Acesso negado.', 403);
        }
        $this->view('admin/erps/criar');
    }

    /**
     * Guarda um novo sistema ERP na base de dados.
     */
    public function store()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            throw new Exception('Acesso negado.', 403);
        }
        verify_csrf_token();
        $name = trim($_POST['name'] ?? '');

        if (empty($name)) {
            flash('O nome do sistema é obrigatório.', 'error');
            header('Location: /admin/erps/criar');
            exit();
        }
        try {
            $pdo = Connection::getPdo();
            $sql = "INSERT INTO erp_systems (name) VALUES (?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$name]);

            flash('Sistema ERP criado com sucesso!');
            header('Location: /admin/erps');
            exit();
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                throw new Exception('Já existe um sistema com este nome.');
            }
            throw $e;
        }
    }

    /**
     * Mostra o formulário de edição para um sistema ERP.
     */
    public function edit()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            throw new Exception('Acesso negado.', 403);
        }
        $id = $_GET['id'] ?? null;
        if (!$id) {
            throw new Exception('ID do sistema não fornecido.');
        }
        try {
            $pdo = Connection::getPdo();
            $stmt = $pdo->prepare("SELECT * FROM erp_systems WHERE id = ?");
            $stmt->execute([$id]);
            $erpSystem = $stmt->fetch();
            if (!$erpSystem) {
                throw new Exception('Sistema ERP não encontrado.');
            }
            $this->view('admin/erps/editar', ['erpSystem' => $erpSystem]);
        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * Atualiza um sistema ERP na base de dados.
     */
    public function update()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            throw new Exception('Acesso negado.', 403);
        }
        verify_csrf_token();
        $id = $_POST['id'] ?? null;
        $name = trim($_POST['name'] ?? '');

        if (!$id || empty($name)) {
            flash('ID e nome são obrigatórios.', 'error');
            header('Location: /admin/erps/editar?id=' . $id);
            exit();
        }
        try {
            $pdo = Connection::getPdo();
            $sql = "UPDATE erp_systems SET name = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$name, $id]);
            
            flash('Sistema ERP atualizado com sucesso!');
            header('Location: /admin/erps');
            exit();
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                throw new Exception('Já existe um sistema com este nome.');
            }
            throw $e;
        }
    }

    /**
     * Apaga um sistema ERP da base de dados.
     */
    public function destroy()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            throw new Exception('Acesso negado.', 403);
        }
        verify_csrf_token();
        $id = $_POST['id'] ?? null; // Alterado para POST

        if (!$id) {
            throw new Exception('ID do sistema não fornecido.');
        }
        try {
            $pdo = Connection::getPdo();
            $sql = "DELETE FROM erp_systems WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);

            flash('Sistema ERP apagado com sucesso!', 'success');
            header('Location: /admin/erps');
            exit();
        } catch (PDOException $e) {
            // Se houver uma restrição de chave estrangeira, a exclusão falhará.
            flash('Não foi possível apagar este sistema. Ele pode estar a ser utilizado por uma ou mais lojas.', 'error');
            header('Location: /admin/erps');
            exit();
        }
    }
}
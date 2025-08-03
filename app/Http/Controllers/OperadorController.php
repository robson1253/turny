<?php

namespace App\Http\Controllers;

use App\Database\Connection;
use App\Utils\Validators;
use App\Utils\Email;
use PDOException;

class OperadorController
{
    /**
     * Mostra a lista de todos os operadores. (Read)
     */
    public function index()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403); die('Acesso negado.');
        }

        try {
            $pdo = Connection::getPdo();
            $stmt = $pdo->query('SELECT id, name, cpf, email, phone, status, pontuacao FROM operators ORDER BY name ASC');
            $operators = $stmt->fetchAll();

            require_once __DIR__ . '/../../Views/admin/operadores/listar_operadores.php';
        } catch (PDOException $e) {
            die('Erro ao buscar os operadores: ' . $e->getMessage());
        }
    }

    /**
     * Mostra o formulário para o admin criar um novo operador.
     */
    public function showCreateForm()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403); die('Acesso negado.');
        }
        require_once __DIR__ . '/../../Views/admin/operadores/criar_operador.php';
    }

    /**
     * Guarda um novo operador criado pelo admin. (Create)
     */
    public function store()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403); die('Acesso negado.');
        }

        $name = $_POST['name'] ?? '';
        $cpf = $_POST['cpf'] ?? '';
        $phone = $_POST['phone'] ?? null;
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $cep = $_POST['cep'] ?? null;
        $endereco = $_POST['endereco'] ?? null;
        $numero = $_POST['numero'] ?? null;
        $bairro = $_POST['bairro'] ?? null;
        $cidade = $_POST['cidade'] ?? null;
        $estado = $_POST['estado'] ?? null;
        
        $cpfLimpo = preg_replace('/[^0-9]/', '', $cpf);
        $telefoneLimpo = $phone ? preg_replace('/[^0-9]/', '', $phone) : null;
        $cepLimpo = $cep ? preg_replace('/[^0-9]/', '', $cep) : null;
        
        if (empty($name) || empty($cpfLimpo) || empty($email) || empty($password)) {
            die('Erro: Nome, CPF, E-mail e Senha são obrigatórios.');
        }
        if (!Validators::validateCpf($cpfLimpo)) {
            die('Erro: O CPF fornecido é inválido.');
        }

        try {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            $pdo = Connection::getPdo();
            $sql = "INSERT INTO operators (name, cpf, phone, email, password, cep, endereco, numero, bairro, cidade, estado, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ativo')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$name, $cpfLimpo, $telefoneLimpo, $email, $passwordHash, $cepLimpo, $endereco, $numero, $bairro, $cidade, $estado]);

            header('Location: /admin/operadores');
            exit();
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                die('Erro: O E-mail ou CPF fornecido já está registado na plataforma.');
            }
            die('Erro ao guardar o operador: ' . $e->getMessage());
        }
    }

    /**
     * Mostra o formulário de edição para um operador específico.
     */
    public function edit()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403); die('Acesso negado.');
        }
        $operatorId = $_GET['id'] ?? null;
        if (!$operatorId) die('ID do operador não fornecido.');
        try {
            $pdo = Connection::getPdo();
            $stmt = $pdo->prepare('SELECT * FROM operators WHERE id = ?');
            $stmt->execute([$operatorId]);
            $operator = $stmt->fetch();
            if (!$operator) die('Operador não encontrado.');
            require_once __DIR__ . '/../../Views/admin/operadores/editar_operador.php';
        } catch (PDOException $e) {
            die('Erro ao buscar os dados do operador: ' . $e->getMessage());
        }
    }

    /**
     * Atualiza os dados de um operador na base de dados. (Update)
     */
    public function update()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403); die('Acesso negado.');
        }
        
        $id = $_POST['id'] ?? null;
        $name = $_POST['name'] ?? '';
        $cpf = $_POST['cpf'] ?? '';
        $phone = $_POST['phone'] ?? null;
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $status = $_POST['status'] ?? 'ativo';
        $cep = $_POST['cep'] ?? null;
        $endereco = $_POST['endereco'] ?? null;
        $numero = $_POST['numero'] ?? null;
        $bairro = $_POST['bairro'] ?? null;
        $cidade = $_POST['cidade'] ?? null;
        $estado = $_POST['estado'] ?? null;
        
        $cpfLimpo = preg_replace('/[^0-9]/', '', $cpf);
        $telefoneLimpo = $phone ? preg_replace('/[^0-9]/', '', $phone) : null;
        $cepLimpo = $cep ? preg_replace('/[^0-9]/', '', $cep) : null;

        if (!$id) die('Erro: ID do operador em falta.');
        if (!Validators::validateCpf($cpfLimpo)) die('Erro: O CPF fornecido é inválido.');

        try {
            $pdo = Connection::getPdo();
            
            if (!empty($password)) {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE operators SET name = ?, cpf = ?, phone = ?, email = ?, password = ?, cep = ?, endereco = ?, numero = ?, bairro = ?, cidade = ?, estado = ?, status = ? WHERE id = ?";
                $params = [$name, $cpfLimpo, $telefoneLimpo, $email, $passwordHash, $cepLimpo, $endereco, $numero, $bairro, $cidade, $estado, $status, $id];
            } else {
                $sql = "UPDATE operators SET name = ?, cpf = ?, phone = ?, email = ?, cep = ?, endereco = ?, numero = ?, bairro = ?, cidade = ?, estado = ?, status = ? WHERE id = ?";
                $params = [$name, $cpfLimpo, $telefoneLimpo, $email, $cepLimpo, $endereco, $numero, $bairro, $cidade, $estado, $status, $id];
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            header('Location: /admin/operadores');
            exit();

        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                die('Erro: O E-mail ou CPF fornecido já pertence a outro operador.');
            }
            die('Erro ao atualizar o operador: ' . $e->getMessage());
        }
    }

    /**
     * Alterna o status de um operador (ativo/inativo).
     */
    public function toggleStatus()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403); die('Acesso negado.');
        }
        $operatorId = $_GET['id'] ?? null;
        if (!$operatorId) die('ID do operador não fornecido.');
        $pdo = Connection::getPdo();
        try {
            $stmt = $pdo->prepare('SELECT status FROM operators WHERE id = ?');
            $stmt->execute([$operatorId]);
            $operator = $stmt->fetch();
            if (!$operator) die('Operador não encontrado.');
            $newStatus = ($operator['status'] === 'ativo') ? 'inativo' : 'ativo';
            $updateStmt = $pdo->prepare('UPDATE operators SET status = ? WHERE id = ?');
            $updateStmt->execute([$newStatus, $operatorId]);
            header('Location: /admin/operadores');
            exit();
        } catch (PDOException $e) {
            die('Erro ao alterar o status do operador: ' . $e->getMessage());
        }
    }

    /**
     * Mostra a página de verificação para um operador pendente.
     */
    public function showVerificationForm()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403); die('Acesso negado.');
        }
        $operatorId = $_GET['id'] ?? null;
        if (!$operatorId) die('ID do operador não fornecido.');
        try {
            $pdo = Connection::getPdo();
            $stmt = $pdo->prepare("SELECT * FROM operators WHERE id = ?");
            $stmt->execute([$operatorId]);
            $operator = $stmt->fetch();
            if (!$operator) die('Operador não encontrado.');
            require_once __DIR__ . '/../../Views/admin/operadores/verificar.php';
        } catch (PDOException $e) {
            die('Erro ao buscar os dados do operador para verificação: ' . $e->getMessage());
        }
    }

    /**
     * Processa a decisão de aprovação ou rejeição de um registo.
     */
    public function processVerification()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            http_response_code(403); die('Acesso negado.');
        }

        $operatorId = $_POST['operator_id'] ?? null;
        $action = $_POST['action'] ?? '';
        $notes = $_POST['verification_notes'] ?? '';

        if (!$operatorId || !in_array($action, ['approve', 'reject'])) {
            die('Ação inválida ou ID do operador em falta.');
        }

        $newStatus = ($action === 'approve') ? 'documentos_aprovados' : 'rejeitado';

        $pdo = Connection::getPdo();
        try {
            $stmtOperator = $pdo->prepare("SELECT name, email FROM operators WHERE id = ?");
            $stmtOperator->execute([$operatorId]);
            $operator = $stmtOperator->fetch();
            if (!$operator) die('Operador não encontrado para enviar a notificação.');

            $sql = "UPDATE operators SET status = ?, verification_notes = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$newStatus, $notes, $operatorId]);

            $operatorEmail = $operator['email'];
            $operatorName = $operator['name'];
            
            if ($action === 'approve') {
                $subject = "Documentos Aprovados na TURNY!";
                $body = "<h1>Parabéns, " . htmlspecialchars($operatorName) . "!</h1><p>Os seus documentos foram aprovados pela nossa equipa.</p><p>O próximo passo é solicitar um treinamento numa das nossas lojas parceiras. Por favor, faça login na plataforma para ver as lojas disponíveis e solicitar o seu agendamento.</p><p><strong>Equipe TURNY</strong></p>";
            } else {
                $subject = "Atualização sobre o seu registo na TURNY";
                $body = "<h1>Olá, " . htmlspecialchars($operatorName) . ".</h1><p>Após uma análise, os seus documentos não foram aprovados neste momento. Se tiver alguma dúvida, por favor, entre em contacto com o nosso suporte.</p><p><strong>Equipe TURNY</strong></p>";
            }

            Email::sendEmail($operatorEmail, $operatorName, $subject, $body);

            header('Location: /admin/operadores');
            exit();
        } catch (PDOException $e) {
            die('Erro ao processar a verificação do operador: ' . $e->getMessage());
        }
    }
}
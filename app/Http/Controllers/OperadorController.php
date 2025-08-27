<?php

namespace App\Http\Controllers;

use App\Database\Connection;
use App\Utils\Validators;
use App\Utils\Email;
use PDOException;
use Exception;
use Throwable;

class OperadorController extends BaseController
{
    /**
     * Mostra a lista de todos os operadores. (Read)
     */
    public function index()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            throw new Exception('Acesso negado.', 403);
        }

        try {
            $pdo = Connection::getPdo();
            $stmt = $pdo->query('SELECT id, name, cpf, email, phone, status, pontuacao FROM operators ORDER BY name ASC');
            $operators = $stmt->fetchAll();

            $this->view('admin/operadores/listar_operadores', ['operators' => $operators]);
        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * Mostra o formulário para o admin criar um novo operador.
     */
    public function showCreateForm()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            throw new Exception('Acesso negado.', 403);
        }
        $this->view('admin/operadores/criar_operador');
    }

    /**
     * Guarda um novo operador criado pelo admin e cria sua carteira. (Create)
     */
    public function store()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            throw new Exception('Acesso negado.', 403);
        }
        
        verify_csrf_token();

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
            throw new Exception('Nome, CPF, E-mail e Senha são obrigatórios.');
        }
        if (!Validators::validateCpf($cpfLimpo)) {
            throw new Exception('O CPF fornecido é inválido.');
        }

        $pdo = Connection::getPdo();
        try {
            $pdo->beginTransaction();

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO operators (name, cpf, phone, email, password, cep, endereco, numero, bairro, cidade, estado, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ativo')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$name, $cpfLimpo, $telefoneLimpo, $email, $passwordHash, $cepLimpo, $endereco, $numero, $bairro, $cidade, $estado]);

            // Cria a carteira para o novo operador
            $newOperatorId = $pdo->lastInsertId();
            $stmtWallet = $pdo->prepare("INSERT INTO operator_wallets (operator_id) VALUES (?)");
            $stmtWallet->execute([$newOperatorId]);

            $pdo->commit();

            flash('Operador criado com sucesso!', 'success');
            header('Location: /admin/operadores');
            exit();

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            
            if ($e instanceof PDOException && $e->errorInfo[1] == 1062) {
                throw new Exception('O E-mail ou CPF fornecido já está registado na plataforma.');
            }
            throw $e;
        }
    }

    /**
     * Mostra o formulário de edição para um operador específico.
     */
    public function edit()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            throw new Exception('Acesso negado.', 403);
        }
        $operatorId = $_GET['id'] ?? null;
        if (!$operatorId) throw new Exception('ID do operador não fornecido.');
        try {
            $pdo = Connection::getPdo();
            $stmt = $pdo->prepare('SELECT * FROM operators WHERE id = ?');
            $stmt->execute([$operatorId]);
            $operator = $stmt->fetch();
            if (!$operator) throw new Exception('Operador não encontrado.');
            
            $this->view('admin/operadores/editar_operador', ['operator' => $operator]);
        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * Atualiza os dados de um operador na base de dados. (Update)
     */
    public function update()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            throw new Exception('Acesso negado.', 403);
        }
        
        verify_csrf_token();
        
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

        if (!$id) throw new Exception('ID do operador em falta.');
        if (!Validators::validateCpf($cpfLimpo)) throw new Exception('O CPF fornecido é inválido.');

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

            flash('Operador atualizado com sucesso!', 'success');
            header('Location: /admin/operadores');
            exit();

        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                throw new Exception('O E-mail ou CPF fornecido já pertence a outro operador.');
            }
            throw $e;
        }
    }

    /**
     * Alterna o status de um operador (ativo/inativo).
     */
    public function toggleStatus()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            throw new Exception('Acesso negado.', 403);
        }

        verify_csrf_token();
        
        $operatorId = $_POST['id'] ?? null;

        if (!$operatorId) throw new Exception('ID do operador não fornecido.');

        $pdo = Connection::getPdo();
        try {
            $stmt = $pdo->prepare('SELECT status FROM operators WHERE id = ?');
            $stmt->execute([$operatorId]);
            $operator = $stmt->fetch();
            
            if (!$operator) throw new Exception('Operador não encontrado.');
            
            $newStatus = ($operator['status'] === 'ativo') ? 'inativo' : 'ativo';
            
            $updateStmt = $pdo->prepare('UPDATE operators SET status = ? WHERE id = ?');
            $updateStmt->execute([$newStatus, $operatorId]);
            
            flash('Status do operador alterado com sucesso!', 'success');
            header('Location: /admin/operadores');
            exit();
        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * Mostra a página de verificação para um operador pendente.
     */
    public function showVerificationForm()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            throw new Exception('Acesso negado.', 403);
        }
        $operatorId = $_GET['id'] ?? null;
        if (!$operatorId) throw new Exception('ID do operador não fornecido.');
        try {
            $pdo = Connection::getPdo();
            $stmt = $pdo->prepare("SELECT * FROM operators WHERE id = ?");
            $stmt->execute([$operatorId]);
            $operator = $stmt->fetch();
            if (!$operator) throw new Exception('Operador não encontrado.');
            
            $this->view('admin/operadores/verificar', ['operator' => $operator]);
        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     * Processa a decisão de aprovação ou rejeição de um registo.
     */
    public function processVerification()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            throw new Exception('Acesso negado.', 403);
        }

        verify_csrf_token();

        $operatorId = $_POST['operator_id'] ?? null;
        $action = $_POST['action'] ?? '';
        $notes = $_POST['verification_notes'] ?? '';

        if (!$operatorId || !in_array($action, ['approve', 'reject'])) {
            throw new Exception('Ação inválida ou ID do operador em falta.');
        }

        $newStatus = ($action === 'approve') ? 'documentos_aprovados' : 'rejeitado';

        $pdo = Connection::getPdo();
        try {
            $stmtOperator = $pdo->prepare("SELECT name, email FROM operators WHERE id = ?");
            $stmtOperator->execute([$operatorId]);
            $operator = $stmtOperator->fetch();
            if (!$operator) throw new Exception('Operador não encontrado para enviar a notificação.');

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

            flash('Verificação processada com sucesso!', 'success');
            header('Location: /admin/operadores');
            exit();
        } catch (PDOException $e) {
            throw $e;
        }
    }
}
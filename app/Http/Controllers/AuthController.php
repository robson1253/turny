<?php

namespace App\Http\Controllers;

use App\Database\Connection;
use PDOException;
use Throwable; // Adicionado para consistência

class AuthController extends BaseController 
{
    /**
     * Mostra o formulário de login (a View).
     */
    public function showLoginForm() 
    {
        $this->view('login');
    }

    /**
     * Processa o login para TODOS os tipos de utilizador com feedback de erro e redirecionamento inteligente.
     */
    public function processLogin() 
    {
        \verify_csrf_token();

        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            header('Location: /login');
            exit();
        }

        try {
            $pdo = Connection::getPdo();
            
            // Tenta encontrar na tabela 'users'
            $stmtUser = $pdo->prepare('SELECT * FROM users WHERE email = ?');
            $stmtUser->execute([$email]);
            $user = $stmtUser->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] == 0) {
                    \flash('Esta conta de utilizador está desabilitada.', 'error');
                    header('Location: /login');
                    exit();
                }
                
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['company_id'] = $user['company_id'];
                
                switch ($user['role']) {
                    case 'admin': header('Location: /dashboard'); break;
                    case 'administrador': header('Location: /painel/empresa-admin'); break;
                    case 'gerente':
                    case 'recepcionista': header('Location: /painel/empresa'); break;
                    default: header('Location: /login'); break;
                }
                exit();
            }

            // Tenta encontrar na tabela 'operators'
            $stmtOperator = $pdo->prepare('SELECT * FROM operators WHERE email = ?');
            $stmtOperator->execute([$email]);
            $operator = $stmtOperator->fetch();

            if ($operator && password_verify($password, $operator['password'])) {
                
                session_regenerate_id(true);

                $_SESSION['operator_id'] = $operator['id'];
                $_SESSION['user_name'] = $operator['name'];
                $_SESSION['user_role'] = 'operador';
                
                // --- ATUALIZAÇÃO IMPORTANTE AQUI ---
                // Salva o caminho da foto do operador na sessão
                $_SESSION['operator_thumb'] = $operator['path_selfie_thumb'];
                // --- FIM DA ATUALIZAÇÃO ---

                switch ($operator['status']) {
                    case 'ativo': header('Location: /painel/operador'); break;
                    case 'documentos_aprovados': header('Location: /painel/operador/qualificacoes'); break;
                    default:
                        \flash('A sua conta de operador ainda não está ativa ou foi desabilitada.', 'error');
                        header('Location: /login');
                        break;
                }
                exit();
            }

            // Se chegou até aqui, o login falhou
            \flash('E-mail ou senha inválidos.', 'error');
            header('Location: /login');
            exit();

        } catch (PDOException $e) {
            // Lança a exceção para ser capturada pelo manipulador global
            throw $e;
        }
    }
}
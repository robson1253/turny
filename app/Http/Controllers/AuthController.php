<?php

namespace App\Http\Controllers;

use App\Database\Connection;
use PDOException;

// 1. A classe agora HERDA do nosso novo BaseController
class AuthController extends BaseController 
{
    /**
     * Mostra o formulário de login (a View).
     */
    public function showLoginForm() 
    {
        // 2. Usa o método view() do BaseController para renderizar a página
        // A view 'login.php' será responsável por chamar as funções do helpers.php
        $this->view('login');
    }

    /**
     * Processa o login para TODOS os tipos de utilizador com feedback de erro e redirecionamento inteligente.
     */
    public function processLogin() 
    {
        // 3. VERIFICA O TOKEN CSRF no início de qualquer ação POST
        verify_csrf_token();

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
                    // 4. USA A FUNÇÃO FLASH para mensagens de erro
                    flash('Esta conta de utilizador está desabilitada.', 'error');
                    header('Location: /login');
                    exit();
                }
                
                // 5. REGENERA O ID DA SESSÃO para prevenir ataques de fixação de sessão
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

                switch ($operator['status']) {
                    case 'ativo': header('Location: /painel/operador'); break;
                    case 'documentos_aprovados': header('Location: /painel/operador/qualificacoes'); break;
                    default:
                        flash('A sua conta de operador ainda não está ativa ou foi desabilitada.', 'error');
                        header('Location: /login');
                        break;
                }
                exit();
            }

            // Se chegou até aqui, o login falhou
            flash('E-mail ou senha inválidos.', 'error');
            header('Location: /login');
            exit();

        } catch (PDOException $e) {
            // 6. LANÇA A EXCEÇÃO para ser capturada pelo manipulador global
            throw $e;
        }
    }
}
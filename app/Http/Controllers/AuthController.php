<?php

require_once __DIR__ . '/../../Database/Connection.php';

class AuthController {
    
    /**
     * Mostra o formulário de login (a View).
     */
    public function showLoginForm() 
    {
        require_once __DIR__ . '/../../Views/login.php';
    }

    /**
     * Processa o login para TODOS os tipos de utilizador com feedback de erro e redirecionamento inteligente.
     */
    public function processLogin() 
    {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            header('Location: /login');
            exit();
        }

        try {
            $pdo = Connection::getPdo();
            
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            // 1. Tenta encontrar na tabela 'users' (admins, gerentes, etc.)
            $stmtUser = $pdo->prepare('SELECT * FROM users WHERE email = ?');
            $stmtUser->execute([$email]);
            $user = $stmtUser->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] == 0) {
                    $_SESSION['login_error'] = 'Esta conta de utilizador está desabilitada.';
                    header('Location: /login');
                    exit();
                }
                
                // SUCESSO: Login bem-sucedido para utilizador do sistema
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['company_id'] = $user['company_id'];
                
                // Redirecionamento detalhado com base no perfil ('role')
                switch ($user['role']) {
                    case 'admin':
                        header('Location: /dashboard'); // Painel Master
                        break;
                    case 'administrador':
                        header('Location: /painel/empresa-admin'); // Painel Estratégico da Empresa
                        break;
                    case 'gerente':
                    case 'recepcionista':
                        header('Location: /painel/empresa'); // Painel Operacional de Vagas
                        break;
                    default:
                        header('Location: /login'); // Se o perfil for desconhecido
                        break;
                }
                exit();
            }

            // 2. Se não encontrou em 'users' ou a senha falhou, tenta em 'operators'
            $stmtOperator = $pdo->prepare('SELECT * FROM operators WHERE email = ?');
            $stmtOperator->execute([$email]);
            $operator = $stmtOperator->fetch();

            if ($operator && password_verify($password, $operator['password'])) {
                // SUCESSO: É um operador, agora verificamos o status
                $_SESSION['operator_id'] = $operator['id'];
                $_SESSION['user_name'] = $operator['name'];
                $_SESSION['user_role'] = 'operador';

                // Redirecionamento inteligente com base no status do operador
                switch ($operator['status']) {
                    case 'ativo':
                        header('Location: /painel/operador'); // Leva para o mural de vagas
                        break;
                    case 'documentos_aprovados':
                        header('Location: /painel/operador/qualificacoes'); // Leva para a pág. de qualificações
                        break;
                    default:
                        // Para 'pendente_verificacao', 'rejeitado', 'inativo', etc.
                        $_SESSION['login_error'] = 'A sua conta de operador ainda não está ativa ou foi desabilitada.';
                        header('Location: /login');
                        break;
                }
                exit();
            }

            // 3. Se chegou até aqui, o e-mail não foi encontrado ou a senha estava incorreta
            $_SESSION['login_error'] = 'E-mail ou senha inválidos.';
            header('Location: /login');
            exit();

        } catch (PDOException $e) {
            die('Ocorreu um erro ao tentar processar o login: ' . $e->getMessage());
        }
    }
}
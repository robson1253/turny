<?php
// Inicia a sessão se ainda não tiver sido iniciada para poder ler as variáveis
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se há uma mensagem de sucesso de registo na URL
$registerSuccess = isset($_GET['status']) && $_GET['status'] === 'register_success';

// Pega a mensagem de erro de login da sessão, se existir
$loginError = $_SESSION['login_error'] ?? null;
// Limpa a mensagem da sessão para que ela não apareça novamente na próxima vez que a página for carregada
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Plataforma TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .error-message {
            background-color: #fce4e4;
            color: #c62828;
            border: 1px solid #c62828;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: left;
            font-size: 14px;
            font-weight: bold;
        }
        .success-message { /* Garantindo que o estilo de sucesso também está aqui */
             background-color: #e8f5e9;
             color: var(--cor-destaque);
             border-left: 5px solid var(--cor-sucesso);
             border-radius: 8px;
             padding: 15px;
             margin-bottom: 20px;
             font-size: 14px;
             font-weight: bold;
        }
    </style>
</head>
<body class="login-body">

    <div class="login-container">
        <?php if ($registerSuccess): ?>
            <div class="success-message">
                Registo concluído com sucesso! A sua conta está em análise. Será notificado por e-mail assim que for aprovada.
            </div>
        <?php endif; ?>

        <?php if ($loginError): ?>
            <div class="error-message">
                <?= htmlspecialchars($loginError) ?>
            </div>
        <?php endif; ?>

        <h2 style="margin-top:0;">
            <span style="color: var(--cor-texto);">Painel </span><a href="/" class="logo" style="text-decoration: none;">Turn<span>y</span></a>
        </h2>
        
        <form action="/login" method="POST">
            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Senha</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <button type="submit">Entrar</button>
            </div>
        </form>

        <div class="signup-link">
            É um operador? <a href="/registro/operador">Registe-se aqui.</a>
        </div>
    </div>

</body>
</html>
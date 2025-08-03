<?php
// A verificação de sessão já é feita no helpers.php, mas não faz mal mantê-la aqui.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se há uma mensagem de sucesso de registo na URL
$registerSuccess = isset($_GET['status']) && $_GET['status'] === 'register_success';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Plataforma TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        /* Estilos para as mensagens flash (pode mover para seu style.css principal) */
        .flash-message {
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: bold;
            text-align: left;
        }
        .flash-message.error {
            background-color: #fce4e4;
            color: #c62828;
            border: 1px solid #c62828;
        }
        .flash-message.success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #2e7d32;
        }
    </style>
</head>
<body class="login-body">

    <div class="login-container">
        <?php if ($registerSuccess): ?>
            <div class="flash-message success">
                Registo concluído com sucesso! A sua conta está em análise. Será notificado por e-mail assim que for aprovada.
            </div>
        <?php endif; ?>

        <?php 
        // 1. Exibe a mensagem flash de erro (se existir) e depois a apaga.
        display_flash_message(); 
        ?>

        <h2 style="margin-top:0;">
            <span style="color: var(--cor-texto);">Painel </span><a href="/" class="logo" style="text-decoration: none;">Turn<span>y</span></a>
        </h2>
        
        <form action="/login" method="POST">
            <?php 
            // 2. Adiciona o campo CSRF oculto e obrigatório ao formulário.
            csrf_field(); 
            ?>
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
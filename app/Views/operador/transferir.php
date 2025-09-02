<?php
$activePage = 'carteira';
$balance = $balance ?? 0.00;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transferir Saldo - TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
	<style>
	        .header-user-profile { display: flex; align-items: center; gap: 10px; }
        .header-user-profile img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
        @media (max-width: 600px) {
            .wallet-actions { grid-template-columns: 1fr; }
        }
	</style>
</head>
<body class="operador-body">
    <div class="operador-container">
        <?php require_once __DIR__ . '/../partials/operador_header.php'; ?>

        <main class="operador-content">
            <h1>Transferir Saldo para Outro Operador</h1>
            <p><a href="/painel/operador/carteira">&larr; Voltar para a Carteira</a></p>
            
            <?php \display_flash_message(); ?>

            <div class="info-box" style="margin-bottom: 20px;">
                <p style="margin: 0;">Seu saldo atual: <strong>R$ <?= htmlspecialchars(number_format($balance, 2, ',', '.')) ?></strong></p>
            </div>

            <form action="/painel/operador/transferir" method="POST" class="form-panel">
                <?php \csrf_field(); ?>
                <fieldset>
                    <legend>Dados da Transferência</legend>
                    <div class="form-group">
                        <label for="destinatario_key">Chave do Destinatário (E-mail, CPF ou Celular)</label>
                        <input type="text" id="destinatario_key" name="destinatario_key" required placeholder="Digite a chave do operador">
                    </div>
                    <div class="form-group">
                        <label for="amount">Valor a Transferir (R$)</label>
                        <input type="text" id="amount" name="amount" required placeholder="Ex: 50,00">
                    </div>
                    <div class="form-group">
                        <label for="password">Sua Senha (para confirmar)</label>
                        <input type="password" id="password" name="password" required autocomplete="current-password">
                    </div>
                </fieldset>

                <div class="form-group" style="margin-top: 20px;">
                    <button type="submit">Confirmar Transferência</button>
                </div>
            </form>
        </main>
        
        <?php require_once __DIR__ . '/../partials/operador_footer.php'; ?>
    </div>
</body>
</html>
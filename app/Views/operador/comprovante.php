<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Comprovante - TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
    <style> .receipt { border: 1px solid #ddd; padding: 20px; max-width: 400px; margin: 20px auto; } </style>
</head>
<body class="operador-body">
    <div class="operador-container">
        <?php require_once __DIR__ . '/../partials/operador_header.php'; ?>
        <main class="operador-content">
            <h1>Comprovante de Transação</h1>
            <p><a href="/painel/operador/carteira">&larr; Voltar para a Carteira</a></p>
            <div class="receipt">
                <h3><?= htmlspecialchars($transaction['description']) ?></h3>
                <p><strong>ID da Transação:</strong> <?= htmlspecialchars($transaction['id']) ?></p>
                <p><strong>Data:</strong> <?= (new DateTime($transaction['created_at']))->format('d/m/Y H:i:s') ?></p>
                <p><strong>Tipo:</strong> <?= htmlspecialchars($transaction['type']) ?></p>
                <p><strong>Valor:</strong> <span style="font-weight: bold; color: var(--cor-perigo);">- R$ <?= htmlspecialchars(number_format($transaction['amount'], 2, ',', '.')) ?></span></p>
            </div>
        </main>
        <?php require_once __DIR__ . '/../partials/operador_footer.php'; ?>
    </div>
</body>
</html>
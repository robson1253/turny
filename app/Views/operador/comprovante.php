<?php
// Preparação segura de variáveis
$transaction = $transaction ?? [];
$isDebit = in_array($transaction['type'] ?? '', ['debit', 'transfer_out', 'pix_transfer']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprovante - TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .receipt { 
            background: #fff;
            border: 1px solid #ddd; 
            padding: 25px; 
            max-width: 450px; 
            margin: 30px auto; 
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .receipt h3 {
            margin-top: 0;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
            color: #333;
            font-size: 1.4em;
        }
        .receipt p { 
            margin: 10px 0;
            display: flex;
            justify-content: space-between;
            font-size: 1.1em;
            color: #555;
        }
        .receipt p strong {
            color: #333;
        }
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
            <h1>Comprovante de Transação</h1>
            <p><a href="/painel/operador/carteira">&larr; Voltar para a Carteira</a></p>
            
            <div class="receipt" id="receipt">
                <h3><?= htmlspecialchars($transaction['description']) ?></h3>
                <p><strong>ID da Transação:</strong> <span><?= htmlspecialchars($transaction['id']) ?></span></p>
                <p><strong>Data:</strong> <span><?= (new DateTime($transaction['created_at']))->format('d/m/Y H:i:s') ?></span></p>
                <p><strong>Valor:</strong> 
                    <span style="font-weight:bold; color: <?= $isDebit ? 'var(--cor-perigo)' : 'var(--cor-sucesso)' ?>;">
                        <?= $isDebit ? '- R$' : '+ R$' ?> <?= htmlspecialchars(number_format($transaction['amount'], 2, ',', '.')) ?>
                    </span>
                </p>
            </div>
            


        </main>
        <?php require_once __DIR__ . '/../partials/operador_footer.php'; ?>
    </div>
</body>
</html>
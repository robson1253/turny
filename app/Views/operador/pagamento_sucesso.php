<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Pagamento Aprovado - TURNY</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/css/style.css">
    <meta http-equiv="refresh" content="3;url=/painel/operador/carteira">
    <style>
        .success-container { text-align: center; padding-top: 50px; }
        .success-icon { width: 100px; height: 100px; fill: var(--cor-sucesso); }
				        @media (max-width: 600px) {
            .wallet-actions {
                grid-template-columns: 1fr; /* Uma coluna em telas pequenas */
            }
        }
    </style>
</head>
<body class="operador-body">
    <div class="operador-container">
        <main class="operador-content">
            <div class="success-container">
                <svg class="success-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M11,16.5L6.5,12L7.91,10.59L11,13.67L16.59,8.09L18,9.5L11,16.5Z" /></svg>
                <h1>Pagamento Aprovado!</h1>
                <p>O valor foi debitado da sua carteira com sucesso.</p>
                <p>Você será redirecionado para a sua carteira em 3 segundos...</p>
                <p><a href="/painel/operador/carteira">Ou clique aqui para voltar agora.</a></p>
            </div>
        </main>
    </div>
</body>
</html>
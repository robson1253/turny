<?php
// Define a página ativa para o menu do rodapé
$activePage = 'carteira';

// Prepara as variáveis vindas do controlador de forma segura
$balance = $balance ?? 0.00;
$transactions = $transactions ?? [];
$pendingOffers = $pendingOffers ?? 0;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Carteira - TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .wallet-balance-card { background: linear-gradient(45deg, var(--cor-primaria), #018b6f); color: #fff; padding: 25px; border-radius: 12px; text-align: center; margin-bottom: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); }
        .wallet-balance-card .balance-label { font-size: 1em; opacity: 0.8; margin: 0; }
        .wallet-balance-card .balance-amount { font-size: 2.8em; font-weight: bold; line-height: 1.2; margin: 5px 0 0 0; }
        .wallet-actions { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-top: 30px; }
        .wallet-actions .btn { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; padding: 15px; background-color: #fff; border: 1px solid var(--cor-borda); border-radius: 8px; color: var(--cor-texto-escuro) !important; text-decoration: none; font-weight: bold; transition: all 0.2s; box-shadow: 0 2px 5px rgba(0,0,0,0.05); position: relative; }
        .wallet-actions .btn:hover { transform: translateY(-3px); box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .wallet-actions .btn svg { width: 32px; height: 32px; fill: var(--cor-primaria); }
        .wallet-actions .btn.disabled { opacity: 0.5; cursor: not-allowed; transform: none; box-shadow: none; }
        .tag-badge { background-color: #ff9800; color: #fff; font-size: 0.7em; padding: 3px 8px; border-radius: 10px; font-weight: normal; margin-top: 4px; }
        .transaction-list { list-style: none; padding: 0; }
        .transaction-item { display: flex; align-items: center; padding: 15px; border-bottom: 1px solid var(--cor-borda); }
        .transaction-item:last-child { border-bottom: none; }
        .transaction-icon { flex-shrink: 0; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px; }
        .transaction-icon.credit { background-color: #e8f5e9; }
        .transaction-icon.debit { background-color: #fce4e4; }
        .transaction-icon svg { width: 24px; height: 24px; }
        .transaction-icon.credit svg { fill: var(--cor-sucesso); }
        .transaction-icon.debit svg { fill: var(--cor-perigo); }
        .transaction-info { flex-grow: 1; }
        .transaction-info .description { display: block; font-weight: bold; color: var(--cor-texto-escuro); }
        .transaction-info .date { display: block; font-size: 0.85em; color: #777; }
        .transaction-amount { font-weight: bold; font-size: 1.1em; }
        .transaction-amount.credit { color: var(--cor-sucesso); }
        .transaction-amount.debit { color: var(--cor-perigo); }
        .offers-notification { text-align: center; padding: 15px; background-color: #fff3cd; border: 1px solid #ffeeba; border-radius: 8px; margin: 20px 0; }
        .btn .badge { margin-top: 8px; background: #ff9800; color: #fff; font-size: 0.75em; font-weight: 500; padding: 3px 8px; border-radius: 8px; }
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
        <h1>Minha Carteira</h1>

        <?php \display_flash_message(); ?>

        <div class="wallet-balance-card">
            <p class="balance-label">Saldo Disponível</p>
            <p class="balance-amount">R$ <?= htmlspecialchars(number_format($balance, 2, ',', '.')) ?></p>
        </div>

<div class="wallet-actions">
    <a href="/painel/operador/pagar" class="btn">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M4 4H10V10H4V4M20 4V10H14V4H20M14 14H20V20H14V14M4 14H10V20H4V14M12 12H6V6H12V12M18 12H12V6H18V12M12 18H6V12H12V18M18 18H12V12H18V18Z" /></svg>
        <span>Pagar (QR)</span>
    </a>
    <a href="/painel/operador/transferir" class="btn">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M17,17.5C17,16.04 15.46,14.89 13.82,14.66C14.55,14.15 15,13.38 15,12.5C15,11.12 13.88,10 12.5,10H9V17H11V15H12.5C13.88,15 15,13.88 15,12.5C15,11.75 14.61,11.09 14,10.66C15.11,10.29 16,9.25 16,8C16,6.62 14.88,5.5 13.5,5.5H9V3H7V5.5H5V7.5H7V17H5V19H7V21H9V19H13.5C15.93,19 18,16.93 18,14.5C18,13.04 17.2,11.79 16,11.04C16.89,10.12 18,8.81 18,7C18,4.79 16.21,3 14,3H9A2,2 0 0,0 7,5V7H2V9H7V17A2,2 0 0,0 9,19H11V21H13V19H14.5C16.43,19 18.06,17.84 18.72,16.18C17.65,16.89 17,17.5 17,17.5Z" /></svg>
        <span>Transferir</span>
    </a>
    <a href="#" class="btn disabled">
        <div class="soon-tag">Em Breve</div>
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M10,3L4,9H7V16H13V9H16L10,3M13,18V20H7V18H13Z" /></svg>
        <span>Sacar (PIX)</span>
    </a>
</div>

        <?php if ($pendingOffers > 0): ?>
            <div class="offers-notification">
                <a href="/painel/operador/ofertas">Você tem <strong><?= htmlspecialchars($pendingOffers) ?></strong> oferta(s) de transferência de turno para avaliar.</a>
            </div>
        <?php endif; ?>

        <h3 style="margin-top: 40px;">Extrato Recente</h3>

        <div class="card-list">
            <?php if (empty($transactions)): ?>
                <div class="info-box"><p>Nenhuma transação na sua carteira ainda.</p></div>
            <?php else: ?>
                <ul class="transaction-list">
                    <?php foreach ($transactions as $tx): 
                        $isCredit = in_array($tx['type'], ['credit', 'transfer_in']);
                        $iconClass = str_replace('_', '-', $tx['type']);
                        $amountClass = $isCredit ? 'credit' : 'debit';
                        $amountSign = $isCredit ? '+' : '-';
                        ?>
                        <li class="transaction-item">
                            <div class="transaction-icon <?= $iconClass ?>">
                                 <?php if ($isCredit): ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M11,4H13V16L18.5,10.5L19.92,11.92L12,19.84L4.08,11.92L5.5,10.5L11,16V4Z" /></svg>
                                <?php else: ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M13,20H11V8L5.5,13.5L4.08,12.08L12,4.16L19.92,12.08L18.5,13.5L13,8V20Z" /></svg>
                                <?php endif; ?>
                            </div>
                            <div class="transaction-info">
                                <a href="/painel/operador/comprovante?id=<?= $tx['id'] ?>" style="text-decoration: none;">
                                    <span class="description"><?= htmlspecialchars($tx['description']) ?></span>
                                </a>
                                <span class="date"><?= (new DateTime($tx['created_at']))->format('d/m/Y \à\s H:i') ?></span>
                            </div>
                            <span class="transaction-amount <?= $amountClass ?>">
                                <?= $amountSign ?> R$ <?= htmlspecialchars(number_format($tx['amount'], 2, ',', '.')) ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </main>

				<?php require_once __DIR__ . '/../partials/operador_footer.php'; ?>	
</div>
</body>
</html>



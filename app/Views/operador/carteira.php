<?php

$activePage = 'carteira';

$balance = $balance ?? 0.00;
$transactions = $transactions ?? [];
$pendingOffers = $pendingOffers ?? 0;

// Pega os dados do operador da sessão para o cabeçalho
$operatorName = $_SESSION['user_name'] ?? 'Operador';
$operatorThumb = $_SESSION['operator_thumb'] ?? '/images/default-avatar.png';

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Minha Carteira - TURNY</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .wallet-balance-card { background: linear-gradient(45deg, var(--cor-primaria), #018b6f); color: #fff; padding: 25px; border-radius: 12px; text-align: center; margin-bottom: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); }
        .wallet-balance-card .balance-label { font-size: 1em; opacity: 0.8; margin: 0; }
        .wallet-balance-card .balance-amount { font-size: 2.8em; font-weight: bold; line-height: 1.2; margin: 5px 0 0 0; }
        
        /* --- ESTILOS PARA OS BOTÕES DE AÇÃO --- */
        .wallet-actions { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-top: 30px; }
        .wallet-actions .btn { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; padding: 15px; background-color: #fff; border: 1px solid var(--cor-borda); border-radius: 8px; color: var(--cor-texto-escuro) !important; text-decoration: none; font-weight: bold; transition: all 0.2s; box-shadow: 0 2px 5px rgba(0,0,0,0.05); position: relative; }
        .wallet-actions .btn:hover { transform: translateY(-3px); box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .wallet-actions .btn svg { width: 32px; height: 32px; fill: var(--cor-primaria); }
        .wallet-actions .btn.disabled { opacity: 0.5; cursor: not-allowed; transform: none; box-shadow: none; }

        /* --- TAG BONITINHA "EM BREVE" --- */
        .tag-badge { 
            background-color: #ff9800; 
            color: #fff; 
            font-size: 0.7em; 
            padding: 3px 8px; 
            border-radius: 10px; 
            font-weight: normal; 
            margin-top: 4px; 
        }

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

    .btn .badge {
        margin-top: 8px;
        background: #ff9800;
        color: #fff;
        font-size: 0.75em;
        font-weight: 500;
        padding: 3px 8px;
        border-radius: 8px;
    }
        /* FOTO NO HEADER */
        .header-user-profile { display: flex; align-items: center; gap: 10px; }
        .header-user-profile img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
		        @media (max-width: 600px) {
            .wallet-actions {
                grid-template-columns: 1fr; /* Uma coluna em telas pequenas */
            }
        }
    </style>
</head>
<body class="operador-body">
    <div class="operador-container">
        <header class="operador-header">
            <div class="logo">Turn<span>y</span></div>
            <div class="header-user-profile">
                <span><?= htmlspecialchars($operatorName) ?></span>
                <img src="<?= htmlspecialchars($operatorThumb) ?>" alt="Foto do Operador">
                <a href="/logout" style="margin-left: 10px;">Sair</a>
            </div>
        </header>

        <main class="operador-content">
            <h1>Minha Carteira</h1>

            <div class="wallet-balance-card">
                <p class="balance-label">Saldo Disponível</p>
                <p class="balance-amount">R$ <?= htmlspecialchars(number_format($balance, 2, ',', '.')) ?></p>
            </div>

            <div class="wallet-actions">
                <!-- PAGAR -->
                <a href="/painel/operador/pagar" class="btn">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M4 4H6V6H4V4M6 18H4V20H6V18M4 14H6V16H4V14M4 10H6V12H4V10M10 4H8V6H10V4M12 4H14V6H12V4M10 20H8V18H10V20M14 20H12V18H14V20M16 20H18V18H16V20M18 4H16V6H18V4M20 10H18V12H20V10M18 14H20V16H18V14M20 4V6H22V4H20M20 18V20H22V18H20M14 10H16V12H14V10M10 14H12V16H10V14M8 8H16V16H8V8Z" /></svg>
                    <span>Pagar</span>
                </a>

                <!-- SACAR (PIX) - DESATIVADO -->
<a href="#" class="btn disabled">
    <!-- Ícone de Carteira -->
    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 10V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-3"/>
        <path d="M21 10h-4a2 2 0 0 0-2 2v0a2 2 0 0 0 2 2h4"/>
    </svg>
    <span>Sacar (PIX)</span>
    <span class="badge">Em breve</span>
</a>

                <!-- COBRAR -->
                <a href="/painel/empresa/receber" class="btn">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M11 15H13V17H11V15M11 7H13V13H11V7M12 2C17.5 2 22 6.5 22 12S17.5 22 12 22 2 17.5 2 12 6.5 2 12 2M12 4C7.58 4 4 7.58 4 12S7.58 20 12 20 20 16.42 20 12 16.42 4 12 4Z" /></svg>
                    <span>Cobrar</span>
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
                            $amountClass = $isCredit ? 'credit' : 'debit';
                            $amountSign = $isCredit ? '+' : '-';
                        ?>
                            <li class="transaction-item">
                                <div class="transaction-icon <?= $amountClass ?>">
                                    <?php if ($isCredit): ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M11,4H13V16L18.5,10.5L19.92,11.92L12,19.84L4.08,11.92L5.5,10.5L11,16V4Z" /></svg>
                                    <?php else: ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M13,20H11V8L5.5,13.5L4.08,12.08L12,4.16L19.92,12.08L18.5,13.5L13,8V20Z" /></svg>
                                    <?php endif; ?>
                                </div>
                                <div class="transaction-info">
                                    <span class="description"><?= htmlspecialchars($tx['description']) ?></span>
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
			
			<div class="transaction-info">
    <a href="/painel/operador/comprovante?id=<?= $tx['id'] ?>" style="text-decoration: none;">
        <span class="description"><?= htmlspecialchars($tx['description']) ?></span>
    </a>
    <span class="date"><?= (new DateTime($tx['created_at']))->format('d/m/Y \à\s H:i') ?></span>
</div>
			
        </main>
        
        <footer class="operador-footer">
            <a href="/painel/operador" class="footer-icon <?= ($activePage ?? '') === 'vagas' ? 'active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M10,20V14H14V20H19V12H22L12,3L2,12H5V20H10Z" /></svg>
                <span>Vagas</span>
            </a>
            <a href="/painel/operador/meus-turnos" class="footer-icon <?= ($activePage ?? '') === 'turnos' ? 'active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19,19H5V8H19M16,1V3H8V1H6V3H5C3.89,3 3,3.89 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5C21,3.89 20.1,3 19,3H18V1M17,12H12V17H17V12Z" /></svg>
                <span>Meus Turnos</span>
            </a>
            <a href="/painel/operador/ofertas" class="footer-icon <?= ($activePage ?? '') === 'ofertas' ? 'active' : '' ?>" style="position: relative;">
                <?php if (($pendingOffers ?? 0) > 0): ?>
                    <span class="notification-badge"><?= htmlspecialchars($pendingOffers) ?></span>
                <?php endif; ?>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M22 17H20V12H4V17H2V12C2 10.9 2.9 10 4 10H20C21.1 10 22 10.9 22 12V17M15.5 2H8.5L7.3 5H16.7L15.5 2M18 5H6L5 7V9H19V7L18 5M12 13C13.1 13 14 13.9 14 15S13.1 17 12 17 10 16.1 10 15 10.9 13 12 13Z" /></svg>
                <span>Ofertas</span>
            </a>
            <a href="/painel/operador/carteira" class="footer-icon <?= ($activePage ?? '') === 'carteira' ? 'active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M21,18V19A2,2 0 0,1 19,21H5A2,2 0 0,1 3,19V5A2,2 0 0,1 5,3H19A2,2 0 0,1 21,5V6H12A2,2 0 0,0 10,8V16A2,2 0 0,0 12,18H21M12,16H22V8H12V16M16,13.5A1.5,1.5 0 0,1 14.5,12A1.5,1.5 0 0,1 16,10.5A1.5,1.5 0 0,1 17.5,12A1.5,1.5 0 0,1 16,13.5Z" /></svg>
                <span>Carteira</span>
            </a>
            <a href="/painel/operador/perfil" class="footer-icon <?= ($activePage ?? '') === 'perfil' ? 'active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z" /></svg>
                <span>Perfil</span>
            </a>
        </footer>
    </div>
</body>
</html>

        
               <footer class="operador-footer">
    <a href="/painel/operador" class="footer-icon <?= ($activePage ?? '') === 'vagas' ? 'active' : '' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M10,20V14H14V20H19V12H22L12,3L2,12H5V20H10Z" /></svg>
        <span>Vagas</span>
    </a>
    <a href="/painel/operador/meus-turnos" class="footer-icon <?= ($activePage ?? '') === 'turnos' ? 'active' : '' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19,19H5V8H19M16,1V3H8V1H6V3H5C3.89,3 3,3.89 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5C21,3.89 20.1,3 19,3H18V1M17,12H12V17H17V12Z" /></svg>
        <span>Meus Turnos</span>
    </a>
    <a href="/painel/operador/ofertas" class="footer-icon <?= ($activePage ?? '') === 'ofertas' ? 'active' : '' ?>" style="position: relative;">
        <?php if (($pendingOffers ?? 0) > 0): ?>
            <span class="notification-badge"><?= htmlspecialchars($pendingOffers) ?></span>
        <?php endif; ?>
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M22 17H20V12H4V17H2V12C2 10.9 2.9 10 4 10H20C21.1 10 22 10.9 22 12V17M15.5 2H8.5L7.3 5H16.7L15.5 2M18 5H6L5 7V9H19V7L18 5M12 13C13.1 13 14 13.9 14 15S13.1 17 12 17 10 16.1 10 15 10.9 13 12 13Z" /></svg>
        <span>Ofertas</span>
    </a>
    <a href="/painel/operador/carteira" class="footer-icon <?= ($activePage ?? '') === 'carteira' ? 'active' : '' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M21,18V19A2,2 0 0,1 19,21H5A2,2 0 0,1 3,19V5A2,2 0 0,1 5,3H19A2,2 0 0,1 21,5V6H12A2,2 0 0,0 10,8V16A2,2 0 0,0 12,18H21M12,16H22V8H12V16M16,13.5A1.5,1.5 0 0,1 14.5,12A1.5,1.5 0 0,1 16,10.5A1.5,1.5 0 0,1 17.5,12A1.5,1.5 0 0,1 16,13.5Z" /></svg>
        <span>Carteira</span>
    </a>
    <a href="/painel/operador/perfil" class="footer-icon <?= ($activePage ?? '') === 'perfil' ? 'active' : '' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z" /></svg>
        <span>Perfil</span>
    </a>
</footer>
    </div>
</body>
</html>
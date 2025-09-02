<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vagas Disponíveis - TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        /* Estilo para o formulário de aceitar vaga parecer um botão grande */
        .vaga-card-footer form {
            flex-grow: 2;
            display: flex;
			background-color: green;
        }
        .vaga-card-footer button {
            width: 100%;
			background-color: green;
        }
    </style>
</head>
<body class="operador-body">

    <div class="operador-container">
        <header class="operador-header">
            <div class="logo">Turn<span>y</span></div>
            <div class="user-info">
                <a href="/logout">Sair</a>
            </div>
        </header>

        <main class="operador-content">
            
            <?php 
            // Exibe mensagens de sucesso ou erro vindas do controller
            display_flash_message(); 
            ?>

            <h2 style="color: var(--cor-destaque); margin-top: 0;">Vagas Disponíveis</h2>
            <p style="margin-top: -10px; margin-bottom: 30px;">Olá, <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong>! Estas são as oportunidades abertas para si.</p>
            
            <div class="vagas-grid" style="grid-template-columns: 1fr; gap: 15px;">
                <?php if (empty($vagasAbertas)): ?>
                    <div class="info-box">
                        <p>Nenhuma vaga aberta no momento para as suas qualificações. Volte mais tarde!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($vagasAbertas as $vaga): ?>
                        <div class="vaga-card">
                            <div class="vaga-card-header">
                                <h3><?= htmlspecialchars($vaga['title']) ?></h3>
                                <p style="margin: 5px 0; font-weight: bold; color: var(--cor-texto);"><?= htmlspecialchars($vaga['company_name']) ?></p>
                                <p style="margin: 5px 0 0 0; color: #777; font-size: 14px;"><?= htmlspecialchars($vaga['store_name']) ?> (<?= htmlspecialchars($vaga['cidade']) ?>/<?= htmlspecialchars($vaga['estado']) ?>)</p>
                            </div>
                            <div class="vaga-card-body">
                                <div class="vaga-card-detail">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19,19H5V8H19M16,1V3H8V1H6V3H5C3.89,3 3,3.89 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5C21,3.89 20.1,3 19,3H18V1M17,12H12V17H17V12Z" /></svg>
                                    <span><?= htmlspecialchars(date('d/m/Y', strtotime($vaga['shift_date']))) ?></span>
                                </div>
                                <div class="vaga-card-detail">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22A10,10 0 0,1 2,12A10,10 0 0,1 12,2M12.5,7V12.25L17,14.92L16.25,16.15L11,13V7H12.5Z" /></svg>
                                    <span><?= htmlspecialchars(substr($vaga['start_time'], 0, 5)) ?> - <?= htmlspecialchars(substr($vaga['end_time'], 0, 5)) ?></span>
                                </div>
                                <div class="vaga-card-detail">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1.25 15.5v-1.5h2.5v1.5h-2.5zm1.25-11c-.69 0-1.25.56-1.25 1.25v.25h2.5v-.25c0-.69-.56-1.25-1.25-1.25zm5 6.25c0 .69-.56 1.25-1.25 1.25H9.25c-.69 0-1.25-.56-1.25-1.25V9.5c0-.69.56-1.25 1.25-1.25h5.5c.69 0 1.25.56 1.25 1.25v5.25z" /></svg>
                                    <span>R$ <?= htmlspecialchars(number_format($vaga['value'], 2, ',', '.')) ?></span>
                                </div>
                            </div>
                            <div class="vaga-card-footer">
                                <!-- Ação de Aceitar Vaga agora é um formulário POST seguro -->
                                <form action="/painel/operador/vagas/aceitar" method="POST" onsubmit="return confirm('Tem a certeza que deseja aceitar esta vaga? A sua presença será confirmada instantaneamente.');">
                                    <?php csrf_field(); ?>
                                    <input type="hidden" name="id" value="<?= $vaga['id'] ?>">
                                    <button type="submit" class="btn success-btn">Aceitar Vaga</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>

<footer class="operador-footer">
    <a href="/painel/operador" class="footer-icon <?= ($activePage === 'vagas') ? 'active' : '' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M10,20V14H14V20H19V12H22L12,3L2,12H5V20H10Z" /></svg>
        <span>Vagas</span>
    </a>
    <a href="/painel/operador/meus-turnos" class="footer-icon <?= ($activePage === 'turnos') ? 'active' : '' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19,19H5V8H19M16,1V3H8V1H6V3H5C3.89,3 3,3.89 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5C21,3.89 20.1,3 19,3H18V1M17,12H12V17H17V12Z" /></svg>
        <span>Meus Turnos</span>
    </a>
    <a href="/painel/operador/ofertas" class="footer-icon <?= ($activePage === 'ofertas') ? 'active' : '' ?>" style="position: relative;">
        <?php if (($pendingOffers ?? 0) > 0): ?>
            <span class="notification-badge"><?= htmlspecialchars($pendingOffers) ?></span>
        <?php endif; ?>
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M22 17H20V12H4V17H2V12C2 10.9 2.9 10 4 10H20C21.1 10 22 10.9 22 12V17M15.5 2H8.5L7.3 5H16.7L15.5 2M18 5H6L5 7V9H19V7L18 5M12 13C13.1 13 14 13.9 14 15S13.1 17 12 17 10 16.1 10 15 10.9 13 12 13Z" /></svg>
        <span>Ofertas</span>
    </a>
    <a href="/painel/operador/carteira" class="footer-icon <?= ($activePage === 'carteira') ? 'active' : '' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M21,18V19A2,2 0 0,1 19,21H5A2,2 0 0,1 3,19V5A2,2 0 0,1 5,3H19A2,2 0 0,1 21,5V6H12A2,2 0 0,0 10,8V16A2,2 0 0,0 12,18H21M12,16H22V8H12V16M16,13.5A1.5,1.5 0 0,1 14.5,12A1.5,1.5 0 0,1 16,10.5A1.5,1.5 0 0,1 17.5,12A1.5,1.5 0 0,1 16,13.5Z" /></svg>
        <span>TURNYPay</span>
    </a>
    <a href="/painel/operador/perfil" class="footer-icon <?= ($activePage === 'perfil') ? 'active' : '' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z" /></svg>
        <span>Perfil</span>
    </a>
</footer>
    </div>

</body>
</html>
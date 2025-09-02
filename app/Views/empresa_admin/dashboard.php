<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel do Administrador - TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="container" style="padding: 40px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
             <div>
                <h1>Painel do Administrador</h1>
                <p style="margin-top:-10px; font-size: 1.1em;">Bem-vindo, <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong>!</p>
            </div>
            <div>
                <a href="/logout" style="color: #777; font-weight: bold;">Sair (Logout)</a>
            </div>
        </div>
        <hr style="margin-top: 20px; margin-bottom: 40px; border: 0; border-top: 1px solid #ddd;">

        <?php 
        // Adicionado para exibir mensagens de feedback de outras ações
        display_flash_message(); 
        ?>

        <div class="stats-grid">
            <div class="stat-card vagas">
                <div class="stat-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19,19H5V8H19M16,1V3H8V1H6V3H5C3.89,3 3,3.89 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5C21,3.89 20.1,3 19,3H18V1M17,12H12V17H17V12Z" /></svg>
                </div>
                <div class="stat-card-info">
                    <div class="stat-number"><?= htmlspecialchars($stats['total_vagas']) ?></div>
                    <div class="stat-label">Total de Vagas Criadas</div>
                </div>
            </div>
            <div class="stat-card lojas">
                <div class="stat-card-icon">
                     <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 3L2 12h3v8h14v-8h3L12 3zm0 2.83L15.17 9H8.83L12 5.83zM7 18v-6h10v6H7z"/></svg>
                </div>
                <div class="stat-card-info">
                    <div class="stat-number"><?= htmlspecialchars($stats['lojas_ativas']) ?></div>
                    <div class="stat-label">Lojas Ativas</div>
                </div>
            </div>
             <div class="stat-card operadores">
                <div class="stat-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1.25 15.5v-1.5h2.5v1.5h-2.5zm1.25-11c-.69 0-1.25.56-1.25 1.25v.25h2.5v-.25c0-.69-.56-1.25-1.25-1.25zm5 6.25c0 .69-.56 1.25-1.25 1.25H9.25c-.69 0-1.25-.56-1.25-1.25V9.5c0-.69.56-1.25 1.25-1.25h5.5c.69 0 1.25.56 1.25 1.25v5.25z" /></svg>
                </div>
                <div class="stat-card-info">
                    <div class="stat-number">R$ <?= htmlspecialchars(number_format($stats['valor_gasto'], 2, ',', '.')) ?></div>
                    <div class="stat-label">Valor Gasto (Vagas Concluídas)</div>
                </div>
            </div>
        </div>

        <div class="actions-box">
             <h3>Ações Rápidas</h3>
             <a href="#" class="btn">Gerir Utilizadores</a>
             <a href="#" class="btn">Ver Relatórios Detalhados</a>
        </div>
    </div>
</body>
</html>
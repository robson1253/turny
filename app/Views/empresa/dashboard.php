<?php
// Proteção e busca de dados
if (!isset($_SESSION['user_id']) || empty($_SESSION['company_id'])) {
    header('Location: /login'); exit();
}
// Garante que a variável $stores existe para o formulário no modal, mesmo que venha vazia
if (!isset($stores)) { 
    $stores = []; 
}
// Garante que a variável de estatísticas existe
if (!isset($stats)) { 
    $stats = ['vagas_abertas' => 0, 'lojas_ativas' => 0, 'vagas_ocupadas' => 0]; 
}
// Garante que a variável de solicitações de treinamento existe
if (!isset($pendingTrainingRequests)) {
    $pendingTrainingRequests = 0;
}
// Garante que as configurações existem para o info-box do valor
if (!isset($settings)) {
    $settings = ['valor_minimo_turno_6h' => '60,00'];
}

// Verifica se há uma mensagem de sucesso na URL para mostrar um feedback
$successMessage = '';
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'success' || $_GET['status'] === 'success_batch') {
        $successMessage = 'Vaga(s) publicada(s) com sucesso!';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel da Empresa - TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .actions-box { 
            margin-top: 20px; 
            background-color: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: 1px solid var(--cor-borda);
        }
        .actions-box h3 { 
            margin-top: 0;
            margin-bottom: 20px; 
            color: var(--cor-destaque);
            border-bottom: 1px solid var(--cor-borda);
            padding-bottom: 10px;
        }
        .actions-box .btn { 
            display: inline-block; 
            padding: 12px 25px; 
            background-color: var(--cor-primaria);
            color: var(--cor-branco) !important;
            border-radius: 5px; 
            text-decoration: none;
            font-weight: bold; 
            margin-right: 10px; 
            margin-bottom: 10px;
            cursor: pointer;
            border: none;
            position: relative;
        }
        .actions-box .btn:hover { 
            opacity: 0.9;
            transform: translateY(-2px);
            transition: all 0.2s;
        }
        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: var(--cor-perigo);
            color: var(--cor-branco);
            border-radius: 50%;
            padding: 2px 7px;
            font-size: 12px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container" style="padding: 40px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <div>
                <h1>Painel de Gestão da Empresa</h1>
                <p style="margin-top:-10px; font-size: 1.1em;">Bem-vindo de volta, <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong>!</p>
            </div>
            <div>
                 <a href="/logout" style="color: #777; font-weight: bold;">Sair (Logout)</a>
            </div>
        </div>
        
        <?php if ($successMessage): ?>
            <div class="success-message" style="margin-top: 20px;">
                <?= htmlspecialchars($successMessage) ?>
            </div>
        <?php endif; ?>

        <hr style="margin-top: 20px; margin-bottom: 40px; border: 0; border-top: 1px solid #ddd;">

        <div class="stats-grid">
            <div class="stat-card vagas"><div class="stat-card-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19,19H5V8H19M16,1V3H8V1H6V3H5C3.89,3 3,3.89 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5C21,3.89 20.1,3 19,3H18V1M17,12H12V17H17V12Z" /></svg></div><div class="stat-card-info"><div class="stat-number"><?= $stats['vagas_abertas'] ?></div><div class="stat-label">Vagas Abertas</div></div></div>
            <div class="stat-card lojas"><div class="stat-card-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 3L2 12h3v8h14v-8h3L12 3zm0 2.83L15.17 9H8.83L12 5.83zM7 18v-6h10v6H7z"/></svg></div><div class="stat-card-info"><div class="stat-number"><?= $stats['lojas_ativas'] ?></div><div class="stat-label">Lojas Ativas</div></div></div>
            <div class="stat-card operadores"><div class="stat-card-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z" /></svg></div><div class="stat-card-info"><div class="stat-number"><?= $stats['vagas_ocupadas'] ?></div><div class="stat-label">Vagas Ocupadas</div></div></div>
        </div>

        <div class="actions-box">
            <h3>Gestão de Vagas/Turnos</h3>
            <?php if ($_SESSION['user_role'] === 'gerente'): ?>
                <a href="/painel/vagas/criar" class="btn">Publicar Vaga Rápida</a>
                <a href="/painel/vagas/planear" class="btn" style="background-color: #17a2b8;">Planeador Semanal</a>
                <a href="/painel/vagas/templates" class="btn" style="background-color: #6c757d;">Gerir Turnos Padrão</a>
            <?php endif; ?>
            <a href="/painel/vagas" class="btn">Gerir Vagas Publicadas</a>
        </div>
        
        <div class="actions-box">
            <h3>Gestão de Operadores</h3>
            <?php if ($_SESSION['user_role'] === 'gerente'): ?>
                <a href="/painel/treinamentos" class="btn">
                    Solicitações de Treinamento
                    <?php if ($pendingTrainingRequests > 0): ?>
                        <span class="notification-badge"><?= $pendingTrainingRequests ?></span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
    
</body>
</html>
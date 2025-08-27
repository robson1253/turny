<?php
// --- LÓGICA DE PROTEÇÃO E PREPARAÇÃO DE DADOS ---

// 1. Garante que o usuário está logado e tem acesso
if (!isset($_SESSION['user_id']) || empty($_SESSION['company_id'])) {
    header('Location: /login'); 
    exit();
}

// 2. Prepara as variáveis da View de forma segura para evitar erros
$stats = $stats ?? ['vagas_abertas' => 0, 'lojas_ativas' => 0, 'vagas_ocupadas' => 0];
$pendingTrainingRequests = $pendingTrainingRequests ?? 0;
$userName = $_SESSION['user_name'] ?? 'Usuário';
$userRole = $_SESSION['user_role'] ?? '';
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
            transition: all 0.2s;
        }
        .actions-box .btn:hover { 
            opacity: 0.9;
            transform: translateY(-2px);
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

        /* Estilos para o novo card de pagamento */
        .payment-action-card {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px;
            background-color: #f8f9fa;
            border: 1px solid var(--cor-borda);
            border-radius: 8px;
        }
        .payment-action-card .icon {
            flex-shrink: 0;
        }
        .payment-action-card .icon svg {
            width: 50px;
            height: 50px;
            fill: var(--cor-primaria);
        }
        .payment-action-card .info p {
            margin: 0 0 10px 0;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="container" style="padding: 40px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <div>
                <h1>Painel de Gestão da Empresa</h1>
                <p style="margin-top:-10px; font-size: 1.1em;">Bem-vindo de volta, <strong><?= htmlspecialchars($userName) ?></strong>!</p>
            </div>
            <div>
                <a href="/logout" style="color: #777; font-weight: bold;">Sair (Logout)</a>
            </div>
        </div>

        <?php display_flash_message(); ?>

        <hr style="margin-top: 20px; margin-bottom: 40px; border: 0; border-top: 1px solid #ddd;">

        <div class="stats-grid">
            <div class="stat-card vagas">
                <div class="stat-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M19,19H5V8H19M16,1V3H8V1H6V3H5C3.89,3 3,3.89 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5C21,3.89 20.1,3 19,3H18V1M17,12H12V17H17V12Z" />
                    </svg>
                </div>
                <div class="stat-card-info">
                    <div class="stat-number"><?= htmlspecialchars($stats['vagas_abertas']) ?></div>
                    <div class="stat-label">Vagas Abertas</div>
                </div>
            </div>
            <div class="stat-card lojas">
                <div class="stat-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M12 3L2 12h3v8h14v-8h3L12 3zm0 2.83L15.17 9H8.83L12 5.83zM7 18v-6h10v6H7z"/>
                    </svg>
                </div>
                <div class="stat-card-info">
                    <div class="stat-number"><?= htmlspecialchars($stats['lojas_ativas']) ?></div>
                    <div class="stat-label">Lojas Ativas</div>
                </div>
            </div>
            <div class="stat-card operadores">
                <div class="stat-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z" />
                    </svg>
                </div>
                <div class="stat-card-info">
                    <div class="stat-number"><?= htmlspecialchars($stats['vagas_ocupadas']) ?></div>
                    <div class="stat-label">Vagas Ocupadas</div>
                </div>
            </div>
        </div>

        <div class="actions-box">
            <h3>Gestão de Vagas/Turnos</h3>
            <?php if ($userRole === 'gerente'): ?>
                <a href="/painel/vagas/criar" class="btn">Publicar Vaga Rápida</a>
                <a href="/painel/vagas/planear" class="btn" style="background-color: #17a2b8;">Planeador Semanal</a>
                <a href="/painel/vagas/templates" class="btn" style="background-color: #6c757d;">Gerir Turnos Padrão</a>
            <?php endif; ?>
            <a href="/painel/vagas" class="btn">Gerir Vagas Publicadas</a>
        </div>

        <?php if ($userRole === 'gerente'): ?>
        <div class="actions-box">
            <h3>Gestão de Operadores</h3>
            
            <a href="/painel/empresa/operadores" class="btn">Ver Operadores Qualificados</a>
            
            <a href="/painel/treinamentos" class="btn">
                Solicitações de Treinamento
                <?php if ($pendingTrainingRequests > 0): ?>
                    <span class="notification-badge"><?= htmlspecialchars($pendingTrainingRequests) ?></span>
                <?php endif; ?>
            </a>
        </div>
        <?php endif; ?>

        <div class="actions-box">
            <h3>Pagamentos</h3>
            <div class="payment-action-card">
                <div class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M4,4H6V6H4V4M6,18H4V20H6V18M4,14H6V16H4V14M4,10H6V12H4V10M10,4H8V6H10V4M12,4H14V6H12V4M10,20H8V18H10V20M14,20H12V18H14V20M16,20H18V18H16V20M18,4H16V6H18V4M20,10H18V12H20V10M18,14H20V16H18V14M20,4V6H22V4H20M20,18V20H22V18H20M14,10H16V12H14V10M10,14H12V16H10V14M8,8H16V16H8V8Z" />
                    </svg>
                </div>
                <div class="info">
                    <p>Gere um QR Code para que operadores possam pagar por produtos e serviços na sua loja utilizando o saldo da carteira TURNY.</p>
                    <a href="/painel/empresa/receber" class="btn" style="background-color: #28a745;">Receber com QR Code</a>
                </div>
            </div>
        </div>

    </div>
</body>
</html>

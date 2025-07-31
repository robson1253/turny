<?php
if (!isset($_SESSION['user_id']) || empty($_SESSION['company_id'])) {
    header('Location: /login'); exit();
}
if (!isset($storeInfo) || !isset($formattedDate) || !isset($storeId) || !isset($date)) {
    die('Erro: Dados essenciais da página não foram carregados. Contacte o suporte.');
}
if (!isset($shifts)) {
    $shifts = [];
}

// Lógica de Navegação de Dias
$currentDateObj = new DateTime($date);
$previousDate = (clone $currentDateObj)->modify('-1 day')->format('Y-m-d');
$nextDate = (clone $currentDateObj)->modify('+1 day')->format('Y-m-d');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Vagas do Dia - TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .day-navigation {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--cor-borda);
        }
        .day-navigation a {
            padding: 8px 15px;
            background-color: var(--cor-primaria);
            color: var(--cor-branco) !important;
            border-radius: 5px;
            font-weight: bold;
            text-decoration: none;
        }
        .day-navigation a:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container" style="padding: 40px 20px;">
        <div class="wizard-header">
            <p class="breadcrumb">
                <a href="/painel/vagas">Lojas</a>
                <span>&gt;</span>
                <a href="/painel/vagas/dias?store_id=<?= htmlspecialchars($storeId) ?>">Dias</a>
                <span>&gt;</span>
                Vagas do Dia
            </p>
            <h1>Vagas em <?= htmlspecialchars($storeInfo['store_name']) ?></h1>
            <p style="margin-top:-10px; font-size: 1.2em; color: #555;"><?= htmlspecialchars($formattedDate) ?></p>
        </div>

        <div class="day-navigation">
            <a href="/painel/vagas/dia?store_id=<?= $storeId ?>&date=<?= $previousDate ?>">&larr; Dia Anterior</a>
			<a href="/painel/empresa" class="btn" style="background-color: var(--cor-destaque);">Voltar ao Painel Principal</a>
            <a href="/painel/vagas/dia?store_id=<?= $storeId ?>&date=<?= $nextDate ?>">Dia Seguinte &rarr;</a>
        </div>

        <div class="vagas-grid">
            <?php if (empty($shifts)): ?>
                <div class="info-box" style="grid-column: 1 / -1;">
                    <p>Nenhuma vaga encontrada para esta loja neste dia.</p>
                    <p style="margin-top: 10px; margin-bottom: 0;">Use a navegação acima para ver o dia anterior ou o próximo, ou <a href="/painel/vagas/criar">clique aqui</a> para criar uma nova vaga para este dia.</p>
                </div>
            <?php else: ?>
                <?php foreach ($shifts as $shift): ?>
                    <div class="vaga-card">
                        <div class="vaga-card-header">
                            <h3><?= htmlspecialchars($shift['title']) ?></h3>
                            <span class="status-badge status-<?= htmlspecialchars($shift['status']) ?>">
                                <?= str_replace('_', ' ', htmlspecialchars($shift['status'])) ?>
                            </span>
                        </div>
                        <div class="vaga-card-body">
                            <div class="vaga-card-detail">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22A10,10 0 0,1 2,12A10,10 0 0,1 12,2M12.5,7V12.25L17,14.92L16.25,16.15L11,13V7H12.5Z" /></svg>
                                <span><?= htmlspecialchars(substr($shift['start_time'], 0, 5)) ?> - <?= htmlspecialchars(substr($shift['end_time'], 0, 5)) ?></span>
                            </div>
                            <div class="vaga-card-detail">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1.25 15.5v-1.5h2.5v1.5h-2.5zm1.25-11c-.69 0-1.25.56-1.25 1.25v.25h2.5v-.25c0-.69-.56-1.25-1.25-1.25zm5 6.25c0 .69-.56 1.25-1.25 1.25H9.25c-.69 0-1.25-.56-1.25-1.25V9.5c0-.69.56-1.25 1.25-1.25h5.5c.69 0 1.25.56 1.25 1.25v5.25z" /></svg>
                                <span>R$ <?= htmlspecialchars(number_format($shift['operator_payment'], 2, ',', '.')) ?></span>
                            </div>
                            <div class="vaga-card-detail">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z" /></svg>
                                <span><?= $shift['approved_count'] ?> / <?= $shift['num_positions'] ?> Vaga(s)</span>
                            </div>
                        </div>
                        
                        <div class="vaga-card-footer">
                            <?php 
                                $isManager = $_SESSION['user_role'] === 'gerente';
                                $hasApprovedOperators = $shift['approved_count'] > 0;
                            ?>

                            <?php if ($hasApprovedOperators): ?>
                                <a href="/painel/vagas/candidatos?shift_id=<?= $shift['id'] ?>" class="btn" style="background-color: #0d6efd; flex-grow: 2;">
                                    Ver Operadores (<?= $shift['approved_count'] ?>)
                                </a>
                            <?php endif; ?>

                            <?php if ($isManager && $shift['status'] === 'aberta'): ?>
                                <a href="/painel/vagas/editar?id=<?= $shift['id'] ?>" class="edit-btn">Editar</a>
                                <a href="/painel/vagas/cancelar?id=<?= $shift['id'] ?>" class="cancel-btn" onclick="return confirm('Tem a certeza que deseja cancelar esta vaga?');">Cancelar</a>
                            <?php endif; ?>

                            <?php if (in_array($shift['status'], ['cancelada', 'concluida'])): ?>
                                <a href="#" style="background-color: #ccc; color: #666; cursor: not-allowed; flex-grow: 2; text-transform: capitalize;" onclick="return false;"><?= str_replace('_', ' ', htmlspecialchars($shift['status'])) ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Vagas do Dia - TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
        <style>
        .day-navigation { display: flex; justify-content: space-between; align-items: center; margin-top: 20px; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid var(--cor-borda, #ddd); }
        .day-navigation a { padding: 8px 15px; background-color: var(--cor-primaria, #016e57); color: var(--cor-branco, #fff) !important; border-radius: 5px; font-weight: bold; text-decoration: none; }
        .day-navigation a:hover { opacity: 0.9; }

        /* --- ESTILOS CORRIGIDOS E COMPLETOS PARA ESTA PÁGINA --- */

        /* 1. Badges de Status com Cores Corretas */
        .status-badge { display: inline-block; padding: 5px 12px; border-radius: 15px; font-size: 12px; font-weight: bold; text-transform: capitalize; line-height: 1.2; border: 1px solid transparent; }
        .status-badge.status-aberta { background-color: #e9f5e9; color: #2e7d32; border-color: #a5d6a7; }
        .status-badge.status-preenchida { background-color: #fce4e4; color: #c62828; border-color: #ef9a9a; }
        .status-badge.status-cancelada { background-color: #f5f5f5; color: #616161; border-color: #e0e0e0; }
        .status-badge.status-concluida { background-color: #e3f2fd; color: #1565c0; border-color: #90caf9; }
        
        /* 2. Padronização dos Botões no Rodapé do Card */
        .vaga-card-footer { display: flex; gap: 10px; align-items: stretch; }
        .vaga-card-footer > a,
        .vaga-card-footer > form {
            flex: 1; /* Faz cada item ocupar o mesmo espaço */
            display: flex;
        }
        .vaga-card-footer .btn,
        .vaga-card-footer .edit-btn,
        .vaga-card-footer .cancel-btn {
            width: 100%;
            text-align: center;
            padding: 8px 12px;
            font-size: 14px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white !important; /* Garante texto branco para todos */
        }

        /* 3. Cores Específicas para Cada Botão */
        .vaga-card-footer .btn { /* Botão "Ver Operadores" */
            background-color: #0d6efd; /* Azul */
        }
        .vaga-card-footer .btn:hover {
            background-color: #0b5ed7;
        }
        .vaga-card-footer .edit-btn { /* Link "Editar" */
            background-color: #17a2b8; /* Azul-petróleo */
        }
        .vaga-card-footer .edit-btn:hover {
            background-color: #138496;
            text-decoration: none;
        }
        .vaga-card-footer button.cancel-btn { /* Botão "Cancelar" */
            background-color: #dc3545; /* Vermelho */
            border-color: #dc3545;
        }
        .vaga-card-footer button.cancel-btn:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }
    </style>
</head>
<body>
    <div class="container" style="padding: 40px 20px;">
        <div class="wizard-header">
            <?php
                // CORREÇÃO: Garante que as variáveis sempre tenham um valor padrão para evitar erros.
                $safeStoreId = htmlspecialchars($storeId ?? 0);
                $storeName = htmlspecialchars($storeInfo['store_name'] ?? 'Loja Desconhecida');
                $displayDate = htmlspecialchars($formattedDate ?? 'Data Inválida');
            ?>
            <p class="breadcrumb"> <a href="/painel/vagas">Lojas</a> <span>&gt;</span> <a href="/painel/vagas/dias?store_id=<?= $safeStoreId ?>">Dias</a> <span>&gt;</span> Vagas do Dia </p>
            <h1>Vagas em <?= $storeName ?></h1>
            <p style="margin-top:-10px; font-size: 1.2em; color: #555;"><?= $displayDate ?></p>
        </div>

        <?php display_flash_message(); ?>

        <div class="day-navigation">
            <?php
                // CORREÇÃO: Verifica se $date existe antes de usá-la.
                if (isset($date)) {
                    $currentDateObj = new DateTime($date);
                    $previousDate = (clone $currentDateObj)->modify('-1 day')->format('Y-m-d');
                    $nextDate = (clone $currentDateObj)->modify('+1 day')->format('Y-m-d');
                } else {
                    // Define valores padrão se a data não for fornecida
                    $previousDate = '#';
                    $nextDate = '#';
                }
            ?>
             <a href="/painel/vagas/dia?store_id=<?= $safeStoreId ?>&date=<?= $previousDate ?>">&larr; Dia Anterior</a>
            <a href="/painel/empresa" class="btn" style="background-color: var(--cor-destaque);">Voltar ao Painel Principal</a>
            <a href="/painel/vagas/dia?store_id=<?= $safeStoreId ?>&date=<?= $nextDate ?>">Dia Seguinte &rarr;</a>
        </div>

        <div class="vagas-grid">
            <?php if (empty($shifts)): ?>
                <div class="info-box" style="grid-column: 1 / -1;">
                    <p>Nenhuma vaga encontrada para esta loja neste dia.</p>
                    <p style="margin-top: 10px; margin-bottom: 0;">Use a navegação acima para ver o dia anterior ou o próximo, ou <a href="/painel/vagas/criar?store_id=<?= $safeStoreId ?>&date=<?= htmlspecialchars($date ?? '') ?>">clique aqui</a> para criar uma nova vaga para este dia.</p>
                </div>
            <?php else: ?>
                <?php foreach ($shifts as $shift): ?>
                    <div class="vaga-card">
                        <div class="vaga-card-header">
                            <h3><?= htmlspecialchars($shift['title'] ?? 'Título Indefinido') ?></h3>
                            <span class="status-badge status-<?= htmlspecialchars($shift['status'] ?? 'desconhecido') ?>">
                                <?= str_replace('_', ' ', htmlspecialchars($shift['status'] ?? 'desconhecido')) ?>
                            </span>
                        </div>
                        <div class="vaga-card-body">
                            <div class="vaga-card-detail">
                                <svg ...></svg>
                                <span><?= htmlspecialchars(substr($shift['start_time'] ?? '00:00', 0, 5)) ?> - <?= htmlspecialchars(substr($shift['end_time'] ?? '00:00', 0, 5)) ?></span>
                            </div>
                            <div class="vaga-card-detail">
                                <svg ...></svg>
                                <span>R$ <?= htmlspecialchars(number_format($shift['operator_payment'] ?? 0, 2, ',', '.')) ?></span>
                            </div>
                            <div class="vaga-card-detail">
                                <svg ...></svg>
                                <span><?= htmlspecialchars($shift['relevant_operator_count'] ?? 0) ?> / <?= htmlspecialchars($shift['num_positions'] ?? 0) ?> Vaga(s)</span>
                            </div>
                        </div>
                        <div class="vaga-card-footer">
                            <?php
                                // CORREÇÃO: Verifica a variável de sessão de forma segura.
                                $isManager = ($_SESSION['user_role'] ?? '') === 'gerente';
                                $hasRelevantOperators = ($shift['relevant_operator_count'] ?? 0) > 0;
                            ?>
                            <?php if ($hasRelevantOperators): ?>
                                <a href="/painel/vagas/candidatos?shift_id=<?= $shift['id'] ?? 0 ?>" class="btn"> Ver Operadores (<?= htmlspecialchars($shift['relevant_operator_count']) ?>) </a>
                            <?php endif; ?>
                            
                            <?php if ($isManager && ($shift['status'] ?? '') === 'aberta'): ?>
                                <a href="/painel/vagas/editar?id=<?= $shift['id'] ?? 0 ?>" class="edit-btn">Editar</a>
                                <form action="/painel/vagas/cancelar" method="POST" onsubmit="return confirm('Tem a certeza que deseja cancelar esta vaga?');">
                                    <?php csrf_field(); ?>
                                    <input type="hidden" name="id" value="<?= $shift['id'] ?? 0 ?>">
                                    <button type="submit" class="cancel-btn">Cancelar</button>
                                </form>
                            <?php endif; ?>

                            <?php if (in_array(($shift['status'] ?? ''), ['cancelada', 'concluida'])): ?>
                                <a href="#" class="btn disabled"><?= str_replace('_', ' ', htmlspecialchars($shift['status'])) ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
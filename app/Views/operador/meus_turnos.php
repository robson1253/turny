<?php
if (!isset($_SESSION['operator_id'])) {
    header('Location: /login'); exit();
}
if (!isset($myShifts)) {
    $myShifts = [];
}
if (!isset($pendingOffers)) {
    $pendingOffers = 0;
}
if (!isset($ratedApplicationIds)) {
    $ratedApplicationIds = [];
}

// Lógica para processar as mensagens de status da URL
$successMessage = '';
$errorMessage = '';
if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'cancel_success':
            $successMessage = 'Turno cancelado com sucesso! A vaga foi reaberta.';
            break;
        case 'transfer_initiated':
            $successMessage = 'Oferta de transferência enviada! O outro operador será notificado.';
            break;
        case 'transfer_success':
            $successMessage = 'Vaga transferida com sucesso!';
            break;
        case 'rating_success':
            $successMessage = 'Avaliação enviada com sucesso. Obrigado!';
            break;
        case 'cancel_failed':
            $errorMessage = 'Não foi possível cancelar. O turno começa em menos de 12 horas.';
            break;
        case 'transfer_failed_time':
            $errorMessage = 'Não foi possível transferir. O turno começa em menos de 2 horas.';
            break;
        case 'transfer_invalid_user':
            $errorMessage = 'Transferência falhou: O @username inserido não foi encontrado ou não está ativo.';
            break;
        case 'transfer_not_qualified':
            $errorMessage = 'Transferência falhou: O operador de destino não tem a qualificação necessária para esta loja.';
            break;
        case 'transfer_conflict_shift':
        case 'transfer_conflict_training':
            $errorMessage = 'Transferência falhou: O operador de destino já tem um compromisso que conflita com este horário.';
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Turnos - TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .error-message { background-color: #fce4e4; color: #c62828; border: 1px solid var(--cor-perigo); border-radius: 8px; padding: 15px; margin-bottom: 20px; font-weight: bold; }
        .btn-transfer { flex: 1; padding: 10px; font-weight: bold; border-radius: 8px; border: none; cursor: pointer; background-color: #0d6efd; color: var(--cor-branco); transition: all 0.2s; font-size: 14px; }
        .vaga-card-footer .cancel-btn { font-size: 14px; }
        .vaga-card-footer .cancel-btn.disabled { background-color: #ccc; cursor: not-allowed; opacity: 0.7; }
    </style>
</head>
<body class="operador-body">
    <div class="operador-container">
        <header class="operador-header">
            <div class="logo">Turn<span>y</span></div>
            <div class="user-info"><a href="/logout">Sair</a></div>
        </header>

        <main class="operador-content">
            <?php if ($successMessage): ?>
                <div class="success-message"><?= htmlspecialchars($successMessage) ?></div>
            <?php endif; ?>
            <?php if ($errorMessage): ?>
                <div class="error-message"><?= htmlspecialchars($errorMessage) ?></div>
            <?php endif; ?>

            <h2 style="color: var(--cor-destaque); margin-top: 0;">Meus Turnos</h2>
            <p style="margin-top: -10px; margin-bottom: 30px;">Acompanhe os seus compromissos e o histórico de turnos.</p>
            
            <div class="vagas-grid" style="grid-template-columns: 1fr; gap: 15px;">
                <?php if (empty($myShifts)): ?>
                    <div class="info-box">
                        <p>Você ainda não tem nenhum turno confirmado. <a href="/painel/operador">Clique aqui</a> para ver as vagas disponíveis.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($myShifts as $vaga): ?>
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
                                    <span>R$ <?= htmlspecialchars(number_format($vaga['operator_payment'], 2, ',', '.')) ?></span>
                                </div>
                            </div>
                            
                            <?php if ($vaga['application_status'] === 'concluido'): ?>
                                <div class="vaga-card-body" style="background-color: #f8f9fa; border-top: 1px solid var(--cor-borda);">
                                    <h4 style="margin-top:0; color: var(--cor-destaque);">Resumo Financeiro</h4>
                                    <p style="margin: 5px 0;"><strong>Pagamento Base:</strong> R$ <?= htmlspecialchars(number_format($vaga['operator_payment'], 2, ',', '.')) ?></p>
                                    <p style="margin: 5px 0; color: var(--cor-perigo);"><strong>Quebra de Caixa:</strong> - R$ <?= htmlspecialchars(number_format($vaga['cash_discrepancy'], 2, ',', '.')) ?></p>
                                    <hr style="border: 0; border-top: 1px solid #ddd; margin: 10px 0;">
                                    <p style="font-weight: bold; font-size: 1.1em; margin: 5px 0;">PAGAMENTO FINAL: R$ <?= htmlspecialchars(number_format($vaga['final_operator_payment'], 2, ',', '.')) ?></p>
                                </div>
                            <?php endif; ?>

                            <div class="vaga-card-footer">
                                <?php if ($vaga['application_status'] === 'aprovado'): ?>
                                    <?php
                                        $now = new DateTime("now", new DateTimeZone('America/Sao_Paulo'));
                                        $shiftStart = new DateTime($vaga['shift_date'] . ' ' . $vaga['start_time'], new DateTimeZone('America/Sao_Paulo'));
                                        $interval = $now->diff($shiftStart);
                                        $hoursDifference = ($interval->days * 24) + $interval->h + ($interval->i / 60);
                                        $canCancel = ($now < $shiftStart && $hoursDifference >= 12);
                                        $canTransfer = ($now < $shiftStart && $hoursDifference >= 2);
                                    ?>
                                    <?php if ($canTransfer && $vaga['transferred_in'] == 0): ?>
                                        <button type="button" class="btn-transfer" data-application-id="<?= $vaga['application_id'] ?>">Transferir</button>
                                    <?php endif; ?>
                                    <?php if ($canCancel): ?>
                                        <a href="/painel/operador/meus-turnos/cancelar?application_id=<?= $vaga['application_id'] ?>" class="cancel-btn" onclick="return confirm('Tem a certeza que deseja cancelar a sua participação neste turno?');">Cancelar</a>
                                    <?php else: ?>
                                        <a href="#" class="cancel-btn disabled" onclick="alert('Não é possível cancelar ou transferir este turno por falta de antecedência.'); return false;">Ações Indisponíveis</a>
                                    <?php endif; ?>
                                <?php elseif ($vaga['application_status'] === 'concluido'): ?>
                                    <?php if (in_array($vaga['application_id'], $ratedApplicationIds)): ?>
                                        <button type="button" class="btn-request disabled" style="flex-grow: 2;" disabled>Avaliação Enviada</button>
                                    <?php else: ?>
                                        <a href="/painel/operador/avaliar?application_id=<?= $vaga['application_id'] ?>" class="btn" style="background-color: var(--cor-destaque); flex-grow: 2;">Avaliar Empresa</a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>

        <footer class="operador-footer">
            <a href="/painel/operador" class="footer-icon"> <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M10,20V14H14V20H19V12H22L12,3L2,12H5V20H10Z" /></svg>
                Vagas
            </a>
            <a href="/painel/operador/meus-turnos" class="footer-icon active">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19,19H5V8H19M16,1V3H8V1H6V3H5C3.89,3 3,3.89 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5C21,3.89 20.1,3 19,3H18V1M17,12H12V17H17V12Z" /></svg>
                Meus Turnos
            </a>
            <a href="/painel/operador/ofertas" class="footer-icon" style="position: relative;">
                <?php if (isset($pendingOffers) && $pendingOffers > 0): ?>
                    <span class="notification-badge"><?= $pendingOffers ?></span>
                <?php endif; ?>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M22 17H20V12H4V17H2V12C2 10.9 2.9 10 4 10H20C21.1 10 22 10.9 22 12V17M15.5 2H8.5L7.3 5H16.7L15.5 2M18 5H6L5 7V9H19V7L18 5M12 13C13.1 13 14 13.9 14 15S13.1 17 12 17 10 16.1 10 15 10.9 13 12 13Z" /></svg>
                Ofertas
            </a>
            <a href="/painel/operador/perfil" class="footer-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z" /></svg>
                Perfil
            </a>
        </footer>
    </div>
    
    <div class="modal-overlay" id="transfer-modal-overlay"></div>
    <div class="modal-content" id="transfer-modal-content" style="max-width: 400px;"></div>
    
    <script>

        document.addEventListener('DOMContentLoaded', function() {
            const body = document.body;
            body.classList.remove('modal-open');

            const transferModalOverlay = document.getElementById('transfer-modal-overlay');
            const transferModalContent = document.getElementById('transfer-modal-content');
            const openTransferBtns = document.querySelectorAll('.btn-transfer');

            function openTransferModal(applicationId) {
                transferModalContent.innerHTML = `
                    <div class="modal-header">
                        <h2>Transferir Vaga</h2>
                        <button type="button" class="modal-close-btn" id="close-transfer-modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <form action="/painel/operador/meus-turnos/transferir" method="POST" id="transfer-form">
                            <input type="hidden" name="application_id" value="${applicationId}">
                            <div class="form-group">
                                <label for="username">@Username do Operador de Destino</label>
                                <input type="text" id="username" name="username" required placeholder="ex: joao_silva">
                            </div>
                            <div class="form-group" style="margin-top: 20px;">
                                <button type="submit">Confirmar Transferência</button>
                            </div>
                        </form>
                    </div>
                `;
                body.classList.add('modal-open');
                transferModalContent.querySelector('#close-transfer-modal').addEventListener('click', closeTransferModal);
            }

            function closeTransferModal() {
                body.classList.remove('modal-open');
            }

            openTransferBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    openTransferModal(this.dataset.applicationId);
                });
            });

            if (transferModalOverlay) {
                transferModalOverlay.addEventListener('click', function(e) {
                    if (e.target === transferModalOverlay) {
                        closeTransferModal();
                    }
                });
            }
        });
    </script>
</body>
</html>
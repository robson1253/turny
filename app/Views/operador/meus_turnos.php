<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Turnos - TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .btn-transfer { flex: 1; padding: 10px; font-weight: bold; border-radius: 8px; border: none; cursor: pointer; background-color: #0d6efd; color: var(--cor-branco); transition: all 0.2s; font-size: 14px; }
        .vaga-card-footer form { flex: 1; display: flex; }
        .vaga-card-footer .cancel-btn { font-size: 14px; width: 100%; color: #ffffff; background-color: #B22222; }
        .vaga-card-footer .cancel-btn.disabled { background-color: #ccc; cursor: not-allowed; opacity: 0.7; }

        /* --- ESTRUTURA DE MODAL CORRIGIDA E DEFINITIVA --- */
        .modal-container { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); z-index: 1000; align-items: center; justify-content: center; padding: 20px; }
        .modal-dialog { background: #fff; padding: 25px; border-radius: 10px; width: 100%; max-width: 400px; box-shadow: 0 5px 20px rgba(0,0,0,0.3); animation: fadeInModal 0.3s ease-out; position: relative; }
        @keyframes fadeInModal { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .modal-header h2 { margin: 0; font-size: 1.5em; }
        .modal-close-btn { position: absolute; top: 10px; right: 15px; background: transparent; border: none; font-size: 28px; cursor: pointer; color: #aaa; line-height: 1; }
        .modal-close-btn:hover { color: #333; }
    </style>
</head>
<body class="operador-body">
    <div class="operador-container">
        <header class="operador-header">
            <div class="logo">Turn<span>y</span></div>
            <div class="user-info"><a href="/logout">Sair</a></div>
        </header>

        <main class="operador-content">
            
            <?php display_flash_message(); ?>

            <h2 style="color: var(--cor-destaque); margin-top: 0;">Meus Turnos</h2>
            <p style="margin-top: -10px; margin-bottom: 30px;">Acompanhe os seus compromissos e o histórico de turnos.</p>
            
            <div class="vagas-grid" style="grid-template-columns: 1fr; gap: 15px;">
                <?php if (empty($myShifts)): ?>
                    <div class="info-box">
                        <p>Você ainda não tem nenhum turno confirmado. <a href="/painel/operador">Clique aqui</a> para ver as vagas disponíveis.</p>
                    </div>
                <?php else: ?>
                    <?php
                        $ratedApplicationIds = $ratedApplicationIds ?? [];
                    ?>
                    <?php foreach ($myShifts as $vaga): ?>
                        <?php
                            // REFINAMENTO: Centraliza a extração segura de dados no início do loop para clareza.
                            $applicationId = $vaga['application_id'] ?? 0;
                            $applicationStatus = $vaga['application_status'] ?? 'desconhecido';
                            $title = $vaga['title'] ?? 'Turno';
                            $companyName = $vaga['company_name'] ?? 'Empresa não informada';
                            $storeName = $vaga['store_name'] ?? 'Loja';
                            $cidade = $vaga['cidade'] ?? 'N/A';
                            $estado = $vaga['estado'] ?? 'N/A';
                            $operatorPayment = $vaga['operator_payment'] ?? 0;
                            $cashDiscrepancy = $vaga['cash_discrepancy'] ?? 0;
                            $finalOperatorPayment = $vaga['final_operator_payment'] ?? $operatorPayment;

                            // REFINAMENTO: Lógica de data e hora à prova de falhas com try-catch.
                            $formattedShiftDate = 'Data Inválida';
                            $formattedStartTime = '00:00';
                            $formattedEndTime = '00:00';
                            $canCancel = false;
                            $canTransfer = false;
                            try {
                                if (!empty($vaga['shift_date'])) {
                                    $shiftDateObj = new DateTime($vaga['shift_date']);
                                    $formattedShiftDate = $shiftDateObj->format('d/m/Y');
                                }
                                if (!empty($vaga['start_time'])) {
                                    $formattedStartTime = substr($vaga['start_time'], 0, 5);
                                }
                                if (!empty($vaga['end_time'])) {
                                    $formattedEndTime = substr($vaga['end_time'], 0, 5);
                                }

                                // Lógica de cancelamento/transferência dentro do try-catch
                                if (!empty($vaga['shift_date']) && !empty($vaga['start_time'])) {
                                    $now = new DateTime("now", new DateTimeZone('America/Sao_Paulo'));
                                    $shiftStart = new DateTime($vaga['shift_date'] . ' ' . $vaga['start_time'], new DateTimeZone('America/Sao_Paulo'));
                                    
                                    if ($now < $shiftStart) {
                                        $diffInSeconds = $shiftStart->getTimestamp() - $now->getTimestamp();
                                        $canCancel = $diffInSeconds >= (12 * 3600);
                                        $canTransfer = $diffInSeconds >= (2 * 3600);
                                    }
                                }
                            } catch (Exception $e) {
                                // Em caso de erro de data, todas as ações são desabilitadas e as datas/horas ficam como padrão.
                            }
                        ?>
                        <div class="vaga-card">
                            <div class="vaga-card-header">
                                <h3><?= htmlspecialchars($title) ?></h3>
                                <p style="margin: 5px 0; font-weight: bold; color: var(--cor-texto);"><?= htmlspecialchars($companyName) ?></p>
                                <p style="margin: 5px 0 0 0; color: #777; font-size: 14px;"><?= htmlspecialchars($storeName) ?> (<?= htmlspecialchars($cidade) ?>/<?= htmlspecialchars($estado) ?>)</p>
                            </div>
                            <div class="vaga-card-body">
                                <div class="vaga-card-detail">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path d="M19,19H5V8H19M16,1V3H8V1H6V3H5C3.89,3 3,3.89 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5C21,3.89 20.1,3 19,3H18V1M17,12H12V17H17V12Z" /></svg>
                                    <span><?= htmlspecialchars($formattedShiftDate) ?></span>
                                </div>
                                <div class="vaga-card-detail">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path d="M12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22A10,10 0 0,1 2,12A10,10 0 0,1 12,2M12.5,7V12.25L17,14.92L16.25,16.15L11,13V7H12.5Z" /></svg>
                                    <span><?= htmlspecialchars($formattedStartTime) ?> - <?= htmlspecialchars($formattedEndTime) ?></span>
                                </div>
                                <div class="vaga-card-detail">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1.25 15.5v-1.5h2.5v1.5h-2.5zm1.25-11c-.69 0-1.25.56-1.25 1.25v.25h2.5v-.25c0-.69-.56-1.25-1.25-1.25zm5 6.25c0 .69-.56 1.25-1.25 1.25H9.25c-.69 0-1.25-.56-1.25-1.25V9.5c0-.69.56-1.25 1.25-1.25h5.5c.69 0 1.25.56 1.25 1.25v5.25z" /></svg>
                                    <span>R$ <?= htmlspecialchars(number_format($operatorPayment, 2, ',', '.')) ?></span>
                                </div>
                            </div>
                            
                            <?php if ($applicationStatus === 'concluido'): ?>
                                <div class="vaga-card-body" style="background-color: #f8f9fa; border-top: 1px solid var(--cor-borda);">
                                    <h4 style="margin-top:0; color: var(--cor-destaque);">Resumo Financeiro</h4>
                                    <p style="margin: 5px 0;"><strong>Pagamento Base:</strong> R$ <?= htmlspecialchars(number_format($operatorPayment, 2, ',', '.')) ?></p>
                                    <p style="margin: 5px 0; color: var(--cor-perigo);"><strong>Quebra de Caixa:</strong> - R$ <?= htmlspecialchars(number_format($cashDiscrepancy, 2, ',', '.')) ?></p>
                                    <hr style="border: 0; border-top: 1px solid #ddd; margin: 10px 0;">
                                    <p style="font-weight: bold; font-size: 1.1em; margin: 5px 0;">PAGAMENTO FINAL: R$ <?= htmlspecialchars(number_format($finalOperatorPayment, 2, ',', '.')) ?></p>
                                </div>
                            <?php endif; ?>

                            <div class="vaga-card-footer">
                                <?php if ($applicationStatus === 'aprovado'): ?>
                                    <?php if ($canTransfer && empty($vaga['transferred_in'])): ?>
                                        <button type="button" class="btn-transfer open-transfer-modal" data-application-id="<?= htmlspecialchars($applicationId) ?>">Transferir</button>
                                    <?php endif; ?>
                                    
                                    <?php if ($canCancel): ?>
                                        <form action="/painel/operador/meus-turnos/cancelar" method="POST" onsubmit="return confirm('Tem a certeza que deseja cancelar a sua participação neste turno?');">
                                            <?php csrf_field(); ?>
                                            <input type="hidden" name="application_id" value="<?= htmlspecialchars($applicationId) ?>">
                                            <button type="submit" class="btn cancel-btn">Cancelar</button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn cancel-btn disabled" title="Não é possível cancelar ou transferir este turno por falta de antecedência." disabled>Ações Indisponíveis</button>
                                    <?php endif; ?>

                                <?php elseif ($applicationStatus === 'concluido'): ?>
                                    <?php if (in_array($applicationId, $ratedApplicationIds)): ?>
                                        <button type="button" class="btn disabled" style="flex-grow: 2;" disabled>Avaliação Enviada</button>
                                    <?php else: ?>
                                        <a href="/painel/operador/avaliar?application_id=<?= urlencode($applicationId) ?>" class="btn" style="background-color: var(--cor-destaque); flex-grow: 2;">Avaliar Empresa</a>
                                    <?php endif; ?>
                                <?php endif; ?>
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
    
    <div id="transfer-modal-container" class="modal-container">
        <div class="modal-dialog">
            <div class="modal-header">
                <h2>Transferir Vaga</h2>
                <button type="button" class="modal-close-btn" id="close-transfer-modal">&times;</button>
            </div>
            <div class="modal-body">
                <form action="/painel/operador/meus-turnos/transferir" method="POST" id="transfer-form">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="application_id" id="transfer_application_id">
                    <div class="form-group">
                        <label for="username">@Username do Operador de Destino</label>
                        <input type="text" id="username" name="username" required placeholder="ex: joao_silva">
                    </div>
                    <div class="form-group" style="margin-top: 20px;">
                        <button type="submit">Confirmar Transferência</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const transferModalContainer = document.getElementById('transfer-modal-container');
        if (transferModalContainer) {
            const openTransferBtns = document.querySelectorAll('.open-transfer-modal');
            const closeTransferBtn = document.getElementById('close-transfer-modal');
            const transferApplicationIdInput = document.getElementById('transfer_application_id');

            function openTransferModal(applicationId) {
                if (transferApplicationIdInput) {
                    transferApplicationIdInput.value = applicationId;
                }
                transferModalContainer.style.display = 'flex';
            }

            function closeTransferModal() {
                transferModalContainer.style.display = 'none';
            }

            openTransferBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    openTransferModal(this.dataset.applicationId);
                });
            });

            if (closeTransferBtn) {
                closeTransferBtn.addEventListener('click', closeTransferModal);
            }
            
            transferModalContainer.addEventListener('click', (e) => {
                if (e.target === transferModalContainer) {
                    closeTransferModal();
                }
            });

            document.addEventListener('keydown', (e) => { 
                if (e.key === "Escape" && transferModalContainer.style.display === 'flex') {
                    closeTransferModal();
                }
            });
        }
    });
    </script>
    </body>
</html>
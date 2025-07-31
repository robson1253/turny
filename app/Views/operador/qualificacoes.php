<?php
if (!isset($_SESSION['operator_id'])) {
    header('Location: /login'); exit();
}
if (!isset($myQualifications)) $myQualifications = [];
if (!isset($requestedErpIds)) $requestedErpIds = [];
if (!isset($allErpSystems)) $allErpSystems = [];
if (!isset($pendingOffers)) $pendingOffers = 0;

// Lógica de Mensagens Completa
$successMessage = '';
$errorMessage = '';
if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'schedule_success':
            $successMessage = 'Treinamento agendado com sucesso!';
            break;
        case 'request_sent':
            $successMessage = 'Solicitação de treinamento enviada com sucesso!';
            break;
        case 'schedule_failed_past':
            $errorMessage = 'Não é possível agendar um treinamento para uma data ou hora que já passou.';
            break;
        case 'schedule_failed_24h':
            $errorMessage = 'O agendamento deve ser feito com pelo menos 24 horas de antecedência.';
            break;
        case 'schedule_conflict_training':
            $errorMessage = 'Falha no agendamento: Você já tem outro treinamento agendado neste mesmo dia e período.';
            break;
        case 'schedule_conflict_store':
            $errorMessage = 'Falha no agendamento: Este horário acabou de ser preenchido por outro operador. Por favor, tente novamente.';
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Qualificações - TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .btn-request { width: 100%; padding: 12px; font-weight: bold; border-radius: 8px; border: none; cursor: pointer; background-color: var(--cor-sucesso); color: var(--cor-branco); transition: all 0.2s; text-decoration: none; display: block; text-align: center; }
        .btn-request.disabled { background-color: #ccc; color: #666; cursor: not-allowed; }
        .notification-badge { position: absolute; top: 0; right: 5px; background-color: var(--cor-perigo); color: var(--cor-branco); border-radius: 50%; height: 20px; width: 20px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold; }
        .qualification-badge { background-color: #e9f2f9; color: var(--cor-primaria); padding: 6px 15px; border-radius: 20px; font-size: 14px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; border: 1px solid #cce0f1; }
        .qualification-badge .icon { width: 16px; height: 16px; fill: var(--cor-primaria); }
        .modal-body .form-step { display: none; }
        .modal-body .form-step.active { display: block; animation: fadeIn 0.5s; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        #store-list .selection-card { cursor: pointer; }
        .slot-options { display: flex; gap: 15px; margin-top: 10px; }
        .slot-button { flex: 1; padding: 15px; font-size: 1em; font-weight: bold; border-radius: 8px; border: 2px solid transparent; cursor: pointer; color: var(--cor-branco); }
        .slot-button.available { background-color: var(--cor-sucesso); border-color: #1e7e34; }
        .slot-button.available:hover { opacity: 0.85; }
        .slot-button.unavailable { background-color: var(--cor-perigo); border-color: #b21f2d; opacity: 0.7; cursor: not-allowed; }
        .btn-prev { background-color: #eee; color: #555; padding: 8px 15px; border-radius: 5px; border: 1px solid #ccc; cursor: pointer; }
        .error-message { background-color: #fce4e4; color: #c62828; border: 1px solid var(--cor-perigo); border-radius: 8px; padding: 15px; margin-bottom: 20px; font-weight: bold; }
    </style>
</head>
<body class="operador-body">
    <div class="operador-container">
        <header class="operador-header">
            <div class="logo">Turn<span>y</span></div>
            <a href="/logout" style="color: #777; font-weight: bold;">Sair</a>
        </header>

        <main class="operador-content">
            <?php if ($successMessage): ?>
                <div class="success-message"><?= htmlspecialchars($successMessage) ?></div>
            <?php endif; ?>
            <?php if ($errorMessage): ?>
                <div class="error-message"><?= htmlspecialchars($errorMessage) ?></div>
            <?php endif; ?>

            <h2 style="color: var(--cor-destaque); margin-top: 0;">Centro de Qualificação</h2>
            <div class="info-box" style="background-color: #e3f2fd; border-left-color: #1565c0; color: #0d47a1;">
                <p style="margin-bottom: 0;">Para ver mais vagas, qualifique-se nos sistemas utilizados pelas nossas lojas parceiras. Solicite um treinamento abaixo.</p>
            </div>

            <div class="profile-section" style="margin-top: 30px;">
                <h3>Meus Emblemas (Sistemas Qualificados)</h3>
                <div class="qualifications-grid">
                    <?php if (empty($myQualifications)): ?>
                        <p>Você ainda não possui nenhuma qualificação.</p>
                    <?php else: ?>
                        <?php foreach ($myQualifications as $qual): ?>
                            <span class="qualification-badge">
                                <span class="icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M11,16.5L6.5,12L7.91,10.59L11,13.67L16.59,8.09L18,9.5L11,16.5Z"></path></svg></span>
                                <?= htmlspecialchars($qual['name']) ?>
                            </span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="profile-section" id="qualificacoes-disponiveis">
                <h3>Qualificações Disponíveis para Solicitar</h3>
                <div class="vagas-grid" style="grid-template-columns: 1fr; gap: 15px;">
                    <?php foreach ($allErpSystems as $system): ?>
                        <div class="vaga-card">
                            <div class="vaga-card-header" style="padding-bottom: 15px;">
                                <h3 style="font-size: 1.2em;"><?= htmlspecialchars($system['name']) ?></h3>
                            </div>
                            <div class="vaga-card-footer">
                                <?php if (in_array($system['id'], array_column($myQualifications, 'id'))): ?>
                                    <button class="btn-request disabled" disabled>Qualificado</button>
                                <?php elseif (in_array($system['id'], $requestedErpIds)): ?>
                                    <button class="btn-request disabled" disabled>Solicitação Pendente</button>
                                <?php else: ?>
                                    <button type="button" class="btn-request open-training-modal" data-erp-id="<?= $system['id'] ?>" data-erp-name="<?= htmlspecialchars($system['name']) ?>">Solicitar Treinamento</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
        
        <footer class="operador-footer">
            <a href="/painel/operador" class="footer-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M10,20V14H14V20H19V12H22L12,3L2,12H5V20H10Z" /></svg>
                Vagas
            </a>
            <a href="/painel/operador/meus-turnos" class="footer-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19,19H5V8H19M16,1V3H8V1H6V3H5C3.89,3 3,3.89 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5C21,3.89 20.1,3 19,3H18V1M17,12H12V17H17V12Z" /></svg>
                Meus Turnos
            </a>
            <a href="/painel/operador/ofertas" class="footer-icon active" style="position: relative;">
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

    <div class="modal-overlay" id="training-modal-overlay"></div>
    <div class="modal-content" id="training-modal-content">
        <div class="modal-header">
            <h2 id="training-modal-title">Solicitar Treinamento</h2>
            <button class="modal-close-btn" id="close-training-modal">&times;</button>
        </div>
        <div class="modal-body">
            <form action="/painel/operador/qualificacoes/agendar" method="POST" id="training-form">
                <input type="hidden" name="erp_system_id" id="form_erp_id">
                <input type="hidden" name="store_id" id="form_store_id">
                <input type="hidden" name="training_slot" id="form_training_slot">

                <div class="form-step active" id="step-store">
                    <h4>Passo 1: Escolha a Loja</h4>
                    <p id="store-list-info" style="color: #555;">A carregar lojas disponíveis...</p>
                    <div id="store-list" class="selection-grid"></div>
                </div>

                <div class="form-step" id="step-schedule">
                    <p><button type="button" class="btn-prev">&larr; Trocar de Loja</button></p>
                    <h4 style="margin-top: 20px;">Passo 2: Escolha a Data e o Horário</h4>
                    <div class="form-group">
                        <label for="training_date">Escolha uma data:</label>
                        <input type="date" name="training_date" id="training_date" required min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group" id="slots-container" style="display: none;">
                        <label>Horários Disponíveis:</label>
                        <div class="slot-options" id="slots-options"></div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
	
	


	
    document.addEventListener('DOMContentLoaded', function() {
        const modalOverlay = document.getElementById('training-modal-overlay');
        const modalContent = document.getElementById('training-modal-content');
        const openModalBtns = document.querySelectorAll('.open-training-modal');
        const closeModalBtn = document.getElementById('close-training-modal');
        const modalTitle = document.getElementById('training-modal-title');
        const trainingForm = document.getElementById('training-form');
        const formErpIdInput = document.getElementById('form_erp_id');
        const formStoreIdInput = document.getElementById('form_store_id');
        const formTrainingSlotInput = document.getElementById('form_training_slot');
        const stepStore = document.getElementById('step-store');
        const stepSchedule = document.getElementById('step-schedule');
        const storeListDiv = document.getElementById('store-list');
        const storeListInfo = document.getElementById('store-list-info');
        const dateInput = document.getElementById('training_date');
        const slotsContainer = document.getElementById('slots-container');
        const slotsOptions = document.getElementById('slots-options');
        const prevButton = stepSchedule.querySelector('.btn-prev');

        function openModal() { document.body.classList.add('modal-open'); }
        function closeModal() {
            document.body.classList.remove('modal-open');
            stepStore.classList.add('active');
            stepSchedule.classList.remove('active');
            dateInput.value = '';
            slotsContainer.style.display = 'none';
        }

        openModalBtns.forEach(btn => {
            btn.addEventListener('click', async function() {
                const erpId = this.dataset.erpId;
                const erpName = this.dataset.erpName;
                modalTitle.textContent = `Solicitar Treinamento em ${erpName}`;
                formErpIdInput.value = erpId;
                storeListDiv.innerHTML = '';
                storeListInfo.style.display = 'block';
                storeListInfo.textContent = 'A carregar lojas disponíveis...';
                openModal();
                try {
                    const response = await fetch(`/api/stores-by-erp?erp_id=${erpId}`);
                    const stores = await response.json();
                    if (stores.error || stores.length === 0) {
                        storeListInfo.textContent = stores.error || 'Nenhuma loja encontrada para este sistema no momento.';
                    } else {
                        storeListInfo.style.display = 'none';
                        stores.forEach(store => {
                            const fullAddress = `${store.endereco}, ${store.numero} - ${store.bairro}, ${store.cidade}/${store.estado}`;
                            storeListDiv.innerHTML += `<div class="selection-card" data-store-id="${store.id}" tabindex="0"><div class="text">${store.name}<br><small>${fullAddress}</small></div></div>`;
                        });
                    }
                } catch (error) { storeListInfo.textContent = 'Ocorreu um erro ao buscar as lojas.'; }
            });
        });

        storeListDiv.addEventListener('click', function(e) {
            const selectedCard = e.target.closest('.selection-card');
            if (selectedCard) {
                const storeId = selectedCard.dataset.storeId;
                formStoreIdInput.value = storeId;
                stepStore.classList.remove('active');
                stepSchedule.classList.add('active');
            }
        });

        prevButton.addEventListener('click', function() {
            stepSchedule.classList.remove('active');
            stepStore.classList.add('active');
            dateInput.value = '';
            slotsContainer.style.display = 'none';
        });

        dateInput.addEventListener('change', async function() {
            const selectedDate = this.value;
            const storeId = formStoreIdInput.value;
            if (!selectedDate || !storeId) return;
            slotsOptions.innerHTML = '<p>A verificar horários...</p>';
            slotsContainer.style.display = 'block';
            try {
                const response = await fetch(`/api/training-slots?store_id=${storeId}&date=${selectedDate}`);
                if (!response.ok) {
                    const errorData = await response.json();
                    slotsOptions.innerHTML = `<p style="color: red;">${errorData.error || 'Ocorreu um erro.'}</p>`;
                    return;
                }
                const availability = await response.json();
                slotsOptions.innerHTML = '';
                if (availability.manha) {
                    slotsOptions.innerHTML += '<button type="button" class="slot-button available" data-slot="manha">Manhã (09:00 - 11:00)</button>';
                } else {
                    slotsOptions.innerHTML += '<button type="button" class="slot-button unavailable" disabled>Manhã (Ocupado)</button>';
                }
                if (availability.tarde) {
                    slotsOptions.innerHTML += '<button type="button" class="slot-button available" data-slot="tarde">Tarde (14:00 - 16:00)</button>';
                } else {
                    slotsOptions.innerHTML += '<button type="button" class="slot-button unavailable" disabled>Tarde (Ocupado)</button>';
                }
            } catch (error) { slotsOptions.innerHTML = '<p style="color: red;">Ocorreu um erro de comunicação ao buscar os horários.</p>'; }
        });

        slotsOptions.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('available')) {
                const selectedSlot = e.target.dataset.slot;
                if (confirm(`Confirma o agendamento para o período da ${selectedSlot}?`)) {
                    formTrainingSlotInput.value = selectedSlot;
                    trainingForm.submit();
                }
            }
        });

        closeModalBtn.addEventListener('click', closeModal);
        modalOverlay.addEventListener('click', closeModal);
    });
    </script>
</body>
</html>
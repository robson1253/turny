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
        
        /* --- ESTRUTURA DE MODAL CORRIGIDA E DEFINITIVA --- */
        .modal-container { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); z-index: 1000; align-items: center; justify-content: center; padding: 20px; }
        .modal-dialog { background: #fff; padding: 25px; border-radius: 10px; width: 100%; max-width: 500px; box-shadow: 0 5px 20px rgba(0,0,0,0.3); animation: fadeInModal 0.3s ease-out; position: relative; }
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
            <a href="/logout" style="color: #777; font-weight: bold;">Sair</a>
        </header>

        <main class="operador-content">
            <?php display_flash_message(); ?>

            <h2 style="color: var(--cor-destaque); margin-top: 0;">Centro de Qualificação</h2>
            <div class="info-box" style="background-color: #e3f2fd; border-left-color: #1565c0; color: #0d47a1;">
                <p style="margin-bottom: 0;">Para ver mais vagas, qualifique-se nos sistemas utilizados pelas nossas lojas parceiras. Solicite um treinamento abaixo.</p>
            </div>

            <div class="profile-section" style="margin-top: 30px;">
                <h3>Meus Emblemas (Sistemas Qualificados)</h3>
                <div class="qualifications-grid">
                    <?php if (empty($myErpQualifications)): ?>
                        <p>Você ainda não possui nenhuma qualificação de sistema.</p>
                    <?php else: ?>
                        <?php foreach ($myErpQualifications as $qual): ?>
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
                                <?php if (in_array($system['id'], array_column($myErpQualifications, 'id'))): ?>
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

    <!-- Estrutura do Modal (Atualizada) -->
    <div id="training-modal-container" class="modal-container">
        <div class="modal-dialog">
            <div class="modal-header">
                <h2 id="training-modal-title">Solicitar Treinamento</h2>
                <button type="button" class="modal-close-btn" id="close-training-modal">&times;</button>
            </div>
            <div class="modal-body">
                <form action="/painel/operador/qualificacoes/agendar" method="POST" id="training-form">
                    <?php csrf_field(); ?>
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
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const modalContainer = document.getElementById('training-modal-container');
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

        function openModal() { modalContainer.style.display = 'flex'; }
        function closeModal() {
            modalContainer.style.display = 'none';
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
                if (!response.ok) throw new Error('Falha na resposta da rede');
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
        modalContainer.addEventListener('click', (e) => { if (e.target === modalContainer) closeModal(); });
        document.addEventListener('keydown', (e) => { if (e.key === "Escape") closeModal(); });
    });
    </script>
</body>
</html>

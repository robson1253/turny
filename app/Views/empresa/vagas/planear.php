<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || empty($_SESSION['company_id'])) {
    header('Location: /login'); exit();
}

if (!isset($stores)) $stores = [];
if (!isset($templates)) $templates = [];

$templatesByStore = [];
foreach ($templates as $template) {
    $templatesByStore[$template['store_id']][] = $template;
}

setlocale(LC_TIME, 'pt_BR.utf-8', 'pt_BR', 'Portuguese_Brazil');
$timezone = new DateTimeZone('America/Sao_Paulo');

$selectedStoreId = $_GET['store_id'] ?? '';
$startDateStr = $_GET['start_date'] ?? null;
$endDateStr = $_GET['end_date'] ?? null;

// --- LÓGICA DE MENSAGENS DE FEEDBACK ---
$successMessage = '';
$errorMessage = '';
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'success_batch') {
        $successMessage = 'Planeamento publicado e vagas criadas com sucesso!';
    }
    if ($_GET['status'] === 'nothing_to_publish') {
        $errorMessage = 'Nenhum turno foi planeado. Adicione turnos aos dias antes de publicar.';
    }
}
// --- FIM DA LÓGICA DE MENSAGENS ---

function validDate($dateStr) {
    $d = DateTime::createFromFormat('Y-m-d', $dateStr);
    return $d && $d->format('Y-m-d') === $dateStr;
}

if ($startDateStr && validDate($startDateStr)) {
    $startDate = new DateTime($startDateStr, $timezone);
} else {
    $startDate = new DateTime('now', $timezone);
}

if ($endDateStr && validDate($endDateStr) && new DateTime($endDateStr) >= $startDate) {
    $endDate = new DateTime($endDateStr, $timezone);
} else {
    $endDate = (clone $startDate)->modify('+6 days');
}

if ($endDate < $startDate) {
    $endDate = (clone $startDate)->modify('+6 days');
}

$days = [];
$currentDay = clone $startDate;
while ($currentDay <= $endDate) {
    $days[] = clone $currentDay;
    $currentDay->modify('+1 day');
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <title>Planeador de Vagas - TURNY</title>
    <link rel="stylesheet" href="/css/style.css" />
    <style>
        .planner-container { display: flex; gap: 30px; align-items: flex-start; }
        .planner-sidebar { flex: 0 0 300px; position: sticky; top: 100px; }
        .planner-main { flex-grow: 1; }
        .week-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 15px; }
        .day-card { background-color: #fff; border: 1px solid var(--cor-borda); border-radius: 8px; min-height: 200px; display: flex; flex-direction: column; }
        .day-header { background-color: #f8f9fa; padding: 10px; text-align: center; font-weight: bold; border-bottom: 1px solid var(--cor-borda); border-radius: 8px 8px 0 0; }
        .day-body { padding: 15px; flex-grow: 1; display: flex; flex-direction: column; justify-content: space-between; }
        .planned-shifts { margin-bottom: 15px; flex-grow: 1; }
        .shift-tag { background-color: #e9f2f9; color: var(--cor-primaria); padding: 5px 10px; border-radius: 15px; font-size: 12px; font-weight: bold; margin-bottom: 5px; display: block; text-align: center; }
        .template-row { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        .template-info { flex-grow: 1; }
        .error-message { background-color: #fce4e4; color: #c62828; border: 1px solid var(--cor-perigo); border-radius: 8px; padding: 15px; margin-bottom: 20px; font-weight: bold; }
        
        .store-selector-grid { display: flex; flex-wrap: wrap; gap: 8px; }
        .store-selector-box { border: 2px solid var(--cor-borda); padding: 5px 12px; border-radius: 20px; font-weight: 600; cursor: pointer; transition: all 0.2s; background-color: var(--cor-fundo); font-size: 14px; }
        .store-selector-box:hover { border-color: var(--cor-destaque); }
        .store-selector-box.selected { background-color: var(--cor-destaque); color: var(--cor-branco); border-color: var(--cor-destaque); }
        
        .quantity-selector-horizontal { display: flex; align-items: center; gap: 5px; }
        .quantity-selector-horizontal .icon-grid { display: flex; gap: 2px; margin-right: 10px; }
        .quantity-selector-horizontal .icon-box {
            width: 35px; height: 35px; font-size: 11px; padding: 2px; flex-shrink: 0;
            display: flex; justify-content: center; align-items: center;
            border: 1px solid #ccc; border-radius: 4px; cursor: pointer;
            transition: 0.2s ease;
        }
        .quantity-selector-horizontal .icon-box svg { width: 16px; height: 16px; margin-bottom: 1px; }
        .quantity-selector-horizontal .icon-box.selected {
            background-color: var(--cor-destaque); border-color: var(--cor-destaque);
            color: white; font-weight: bold; transform: scale(1.05); box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }
        .quantity-selector-horizontal .icon-box.digit-quantity-button { background: none; color: #555; }
        .quantity-selector-horizontal .icon-box.digit-quantity-button:hover { background-color: #eee; }
        
        .btn-publish-planner { width: 100%; padding: 15px; font-size: 1.1em; font-weight: bold; background-color: var(--cor-sucesso); color: var(--cor-branco); border: none; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.2s; }
        .btn-publish-planner:hover { opacity: 0.9; transform: translateY(-2px); box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4); }
        .btn-publish-planner svg { width: 20px; height: 20px; fill: var(--cor-branco); }
		.btn-add-shift {
    width: 100%;
    padding: 10px;
    font-size: 1em;
    font-weight: 600;
    background-color: var(--cor-sucesso);
    color: var(--cor-branco);
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 5px rgba(40, 167, 69, 0.6);
}

.btn-add-shift:hover {
    background-color: #128a2d;
    box-shadow: 0 4px 12px rgba(18, 138, 45, 0.8);
    transform: translateY(-2px);
}
    </style>
</head>
<body>
<div class="container" style="padding: 40px 0; max-width: 1400px;">
    <p><a href="/painel/empresa">&larr; Voltar para o Painel</a></p>
    <h1>Planeador de Vagas</h1>

    <?php if ($successMessage): ?>
        <div class="success-message"><?= htmlspecialchars($successMessage) ?></div>
    <?php endif; ?>
    <?php if ($errorMessage): ?>
        <div class="error-message"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>

    <form action="/painel/vagas/criar-lote-semanal" method="POST" id="week-planner-form">
        <div class="planner-container">
            <aside class="planner-sidebar">
                <div class="form-panel">
                    <fieldset>
                        <legend>Configuração</legend>
                        <div class="form-group">
                            <label>Loja para o Planeamento:</label>
                            <div class="store-selector-grid" id="store-selector-grid">
                                <?php foreach ($stores as $store): ?>
                                    <div class="store-selector-box <?= ($store['id'] == $selectedStoreId) ? 'selected' : '' ?>" data-store-id="<?= $store['id'] ?>">
                                        <?= htmlspecialchars($store['name']) ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <input type="hidden" name="store_id" id="planner_store_id" value="<?= htmlspecialchars($selectedStoreId) ?>" required>
                        
                        <div class="form-group">
                            <label for="start_date">Data Inicial:</label>
                            <input type="date" id="start_date" name="start_date" value="<?= $startDate->format('Y-m-d') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="end_date">Data Final:</label>
                            <input type="date" id="end_date" name="end_date" value="<?= $endDate->format('Y-m-d') ?>" required>
                        </div>
                        <button type="submit" class="btn-publish-planner">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M2,21L23,12L2,3V10L17,12L2,14V21Z" /></svg>
                            Publicar Planeamento
                        </button>
                    </fieldset>
                </div>
            </aside>

            <main class="planner-main">
                <div class="week-grid">
                    <?php
                    $formatter = new IntlDateFormatter('pt_BR', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'America/Sao_Paulo', IntlDateFormatter::GREGORIAN, 'EEEE');
                    foreach ($days as $day):
                    ?>
                        <div class="day-card">
                            <div class="day-header">
                                <?= ucfirst($formatter->format($day)) ?><br>
                                <small><?= $day->format('d/m') ?></small>
                            </div>
                            <div class="day-body">
                                <div class="planned-shifts" id="planned-shifts-<?= $day->format('Y-m-d') ?>"></div>
                                <button type="button" class="btn btn-add-shift" data-date="<?= $day->format('Y-m-d') ?>" style="width: 100%; background-color: var(--cor-sucesso);">+ Adicionar Turnos</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </main>
        </div>
    </form>
</div>

<div class="modal-overlay" id="planner-modal-overlay"></div>
<div class="modal-content" id="planner-modal-content" role="dialog" aria-modal="true" aria-labelledby="planner-modal-title">
    <div class="modal-header">
        <h2 id="planner-modal-title">Adicionar Turnos</h2>
        <button type="button" class="modal-close-btn" id="close-planner-modal" aria-label="Fechar modal">&times;</button>
    </div>
    <div class="modal-body">
        <div id="templates-list"></div>
        <div class="form-group" style="margin-top: 20px;">
            <button type="button" id="confirm-day-shifts" class="btn" style="width: 100%;">Confirmar para este dia</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const templatesByStore = <?= json_encode($templatesByStore) ?>;
    let weeklyPlan = {};

    const storeSelectorGrid = document.getElementById('store-selector-grid');
    const formStoreIdInput = document.getElementById('planner_store_id');
    const plannerMain = document.querySelector('.planner-main');
    const modalOverlay = document.getElementById('planner-modal-overlay');
    const modalContent = document.getElementById('planner-modal-content');
    const closeModalBtn = document.getElementById('close-planner-modal');
    const modalTitle = document.getElementById('planner-modal-title');
    const templatesListDiv = document.getElementById('templates-list');
    const confirmDayShiftsBtn = document.getElementById('confirm-day-shifts');
    const weekPlannerForm = document.getElementById('week-planner-form');
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');

    let currentDate = null;

    function openModal() { document.body.classList.add('modal-open'); }
    function closeModal() { document.body.classList.remove('modal-open'); }

    function updateURL() {
        const url = new URL(window.location);
        url.searchParams.set('start_date', startDateInput.value);
        url.searchParams.set('end_date', endDateInput.value);
        if (formStoreIdInput.value) {
            url.searchParams.set('store_id', formStoreIdInput.value);
        } else {
            url.searchParams.delete('store_id');
        }
        window.location.href = url.href;
    }

    startDateInput.addEventListener('change', updateURL);
    endDateInput.addEventListener('change', updateURL);
    storeSelectorGrid.addEventListener('click', function (e) {
        const clickedBox = e.target.closest('.store-selector-box');
        if (clickedBox) {
            const newStoreId = clickedBox.dataset.storeId;
            if (formStoreIdInput.value !== newStoreId) {
                formStoreIdInput.value = newStoreId;
                updateURL();
            }
        }
    });

    if (plannerMain) {
        plannerMain.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('btn-add-shift')) {
                const button = e.target;
                const selectedStoreId = formStoreIdInput.value;
                currentDate = button.dataset.date;
                
                if (!selectedStoreId) {
                    alert('Por favor, selecione uma loja no filtro à esquerda para começar a planear.');
                    return;
                }
                
                const storeTemplates = templatesByStore[selectedStoreId] || [];
                const selectedStoreName = document.querySelector(`.store-selector-box.selected`)?.textContent.trim();
                const formattedDate = new Date(currentDate + 'T12:00:00').toLocaleDateString('pt-BR', {day: '2-digit', month: '2-digit', year: 'numeric'});
                
                modalTitle.textContent = `Adicionar Turnos para ${selectedStoreName} - ${formattedDate}`;
                templatesListDiv.innerHTML = '<legend>Selecione os Turnos Padrão</legend>';

                if (storeTemplates.length === 0) {
                    templatesListDiv.innerHTML += '<p>Nenhum turno padrão encontrado para esta loja. <a href="/painel/vagas/templates">Crie um aqui</a>.</p>';
                } else {
                    storeTemplates.forEach((template) => {
                        const plannedNum = (weeklyPlan[currentDate] || []).find(p => p.templateId == template.id)?.numPositions || 0;
                        templatesListDiv.innerHTML += `
                            <div class="template-row">
                                <div class="template-info">
                                    <strong>${template.title}</strong><br>
                                    <small>${template.start_time.substring(0, 5)} - ${template.end_time.substring(0, 5)}</small>
                                </div>
                                <div class="form-group quantity-selector-horizontal" style="margin-bottom: 0;">
                                    <div class="icon-grid">
                                        ${[1,2,3,4,5].map(i => `<div class="icon-box ${plannedNum == i ? 'selected' : ''}" data-vaga-id="${i}" tabindex="0"><svg viewBox="0 0 24 24"><path d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z" /></svg>${i}</div>`).join('')}
                                        <div class="icon-box digit-quantity-button" tabindex="0"><svg viewBox="0 0 24 24" style="width:16px; height:16px;"><path d="M3 17.25V19.25H21V17.25H3M3 11.25V13.25H21V11.25H3M3 5.25V7.25H21V5.25H3Z"></path></svg></div>
                                    </div>
                                    <input type="number" class="quantity-input-field" style="display:none; width: 60px;" min="0">
                                    <input type="hidden" class="num-positions-hidden" data-template-id="${template.id}" value="${plannedNum}">
                                </div>
                            </div>
                        `;
                    });
                    setupQuantitySelectors();
                }
                openModal();
            }
        });
    }

    function setupQuantitySelectors() {
        templatesListDiv.querySelectorAll('.quantity-selector-horizontal').forEach(container => {
            const grid = container.querySelector('.icon-grid');
            const digitButton = grid.querySelector('.digit-quantity-button');
            const numberInput = container.querySelector('.quantity-input-field');
            const hiddenInput = container.querySelector('.num-positions-hidden');
            const iconBoxes = grid.querySelectorAll('.icon-box:not(.digit-quantity-button)');

            grid.addEventListener('click', function(e) {
                const clickedBox = e.target.closest('.icon-box');
                if (!clickedBox) return;
                numberInput.style.display = 'none';
                grid.style.display = 'flex';
                if (clickedBox === digitButton) {
                    grid.style.display = 'none';
                    numberInput.style.display = 'block';
                    numberInput.value = hiddenInput.value > 0 ? hiddenInput.value : '';
                    numberInput.focus();
                } else {
                    const num = parseInt(clickedBox.dataset.vagaId);
                    hiddenInput.value = num;
                    iconBoxes.forEach(b => b.classList.remove('selected'));
                    for(let i=0; i<num; i++) {
                        if (iconBoxes.item(i)) iconBoxes.item(i).classList.add('selected');
                    }
                }
            });
            numberInput.addEventListener('input', function() {
                hiddenInput.value = this.value;
                iconBoxes.forEach(b => b.classList.remove('selected'));
            });
        });
    }

    confirmDayShiftsBtn.addEventListener('click', function () {
        if (!currentDate) return;
        weeklyPlan[currentDate] = [];
        const plannedShiftsContainer = document.getElementById(`planned-shifts-${currentDate}`);
        plannedShiftsContainer.innerHTML = '';

        templatesListDiv.querySelectorAll('.num-positions-hidden').forEach(input => {
            const numPositions = parseInt(input.value, 10);
            if (numPositions > 0) {
                const templateId = input.dataset.templateId;
                const template = templatesByStore[formStoreIdInput.value].find(t => t.id == templateId);
                weeklyPlan[currentDate].push({ templateId: templateId, numPositions: numPositions });
                const tag = document.createElement('div');
                tag.className = 'shift-tag';
                tag.textContent = `${template.title}: ${numPositions} vaga(s)`;
                plannedShiftsContainer.appendChild(tag);
            }
        });
        closeModal();
    });

    weekPlannerForm.addEventListener('submit', function (e) {
        e.preventDefault();
        this.querySelectorAll('input.planner-hidden-input').forEach(input => input.remove());
        for (const date in weeklyPlan) {
            if (weeklyPlan[date].length > 0) {
                weeklyPlan[date].forEach(shift => {
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.className = 'planner-hidden-input';
                    hiddenInput.name = `plan[${date}][${shift.templateId}]`;
                    hiddenInput.value = shift.numPositions;
                    this.appendChild(hiddenInput);
                });
            }
        }
        this.submit();
    });

    if (closeModalBtn) closeModalBtn.addEventListener('click', closeModal);
    if (modalOverlay) modalOverlay.addEventListener('click', closeModal);
});
</script>
</body>
</html>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Vaga - TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
    <style>
        /* Seus estilos originais para o Wizard */
#job-type-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

#job-type-grid .icon-box {
    padding: 6px 12px;
    font-size: 14px;
    height: 44px;
    min-width: 130px;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-direction: row;
}

#job-type-grid .icon-box svg {
    width: 26px;
    height: 26px;
    margin-right: 6px;
}
        /* Estilos para mensagens flash */
        .flash-message { border-radius: 8px; padding: 15px; margin-bottom: 20px; font-size: 14px; font-weight: bold; }
        .flash-message.error { background-color: #fce4e4; color: #c62828; border: 1px solid #c62828; }
        .flash-message.success { background-color: #e8f5e9; color: #2e7d32; border: 1px solid #2e7d32; }
    </style>
    </style>
</head>
<body>
    <div class="container" style="padding: 40px 0; max-width: 800px;">
        <p style="margin-bottom: 30px;"><a href="/painel/vagas/dia?store_id=<?= htmlspecialchars($shift['store_id']) ?>&date=<?= htmlspecialchars($shift['shift_date']) ?>">&larr; Voltar para as vagas do dia</a></p>
        
        <form action="/painel/vagas/atualizar" method="POST" class="form-panel" id="vaga-form">
            <h1>Editar Vaga/Turno</h1>

            <?php 
            display_flash_message(); 
            csrf_field(); 
            ?>

            <input type="hidden" name="id" value="<?= htmlspecialchars($shift['id']) ?>">
            
            <fieldset>
                <legend>Detalhes da Vaga</legend>
                
                <div class="form-group">
                    <label>Loja da Vaga (Editável)</label>
                    <div class="icon-grid" id="store-selection-grid">
                        <?php foreach ($stores as $store): ?>
                            <div class="icon-box <?= ($store['id'] == $shift['store_id']) ? 'selected' : '' ?>" data-value="<?= $store['id'] ?>" tabindex="0">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 3L2 12h3v8h14v-8h3L12 3zm0 2.83L15.17 9H8.83L12 5.83zM7 18v-6h10v6H7z"/></svg>
                                <?= htmlspecialchars($store['name']) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="store_id" id="store_id_hidden" value="<?= htmlspecialchars($shift['store_id']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Função da Vaga (Não editável)</label>
                    <div class="icon-grid disabled-grid">
                         <?php foreach ($jobFunctions as $function): ?>
                             <div class="icon-box <?= ($function['id'] == $shift['job_function_id']) ? 'selected' : '' ?>" data-value="<?= $function['id'] ?>">
                                 <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z" /></svg>
                                 <?= htmlspecialchars($function['name']) ?>
                             </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="job_function_id" value="<?= htmlspecialchars($shift['job_function_id']) ?>">
                </div>

                <div class="form-group">
                    <label>Data do Turno (Não editável)</label>
                    <input type="date" name="shift_date" required value="<?= htmlspecialchars($shift['shift_date']) ?>" readonly>
                </div>
                
                <div style="display: flex; gap: 20px;">
                    <div class="form-group" style="flex: 1;"><label>Hora de Início (Editável)</label><input type="time" name="start_time" required value="<?= htmlspecialchars($shift['start_time']) ?>"></div>
                    <div class="form-group" style="flex: 1;"><label>Hora de Fim (Editável)</label><input type="time" name="end_time" required value="<?= htmlspecialchars($shift['end_time']) ?>"></div>
                </div>

                <!-- SELEÇÃO DE QUANTIDADE RESTAURADA -->
                <div class="form-group">
                    <label>Quantidade de Vagas (<span id="vaga-count"><?= htmlspecialchars($shift['num_positions']) ?></span> selecionada(s))</label>
                    <div class="icon-grid" id="vaga-selection-grid">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <div class="icon-box" data-vaga-id="<?= $i ?>" tabindex="0"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z" /></svg><?= $i ?> Vaga<?= ($i > 1) ? 's' : '' ?></div>
                        <?php endfor; ?>
                         <div class="icon-box digit-quantity-button" id="digit-quantity-button" tabindex="0"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="width:24px; height:24px;"><path d="M3 17.25V19.25H21V17.25H3M3 11.25V13.25H21V11.25H3M3 5.25V7.25H21V5.25H3Z"></path></svg>Digitar</div>
                    </div>
                    <div class="form-group" id="quantity-input-group" style="display: none; margin-top: 15px;">
                         <label for="quantity-input">Digite a quantidade desejada:</label>
                         <input type="number" id="quantity-input" min="1" placeholder="Ex: 8">
                    </div>
                    <input type="hidden" name="num_positions" id="num_positions_hidden" value="<?= htmlspecialchars($shift['num_positions']) ?>" required>
                </div>

                <div class="form-group">
                    <input type="checkbox" id="is_holiday" name="is_holiday_display" value="1" <?= $shift['is_holiday'] ? 'checked' : '' ?> disabled style="margin-right: 5px;">
                    <label for="is_holiday" style="display: inline; font-weight: normal;">Marcar como Feriado (Não editável)</label>
                    <?php if ($shift['is_holiday']): ?>
                        <input type="hidden" name="is_holiday" value="1">
                    <?php endif; ?>
                </div>
            </fieldset>

             <div class="info-box">
                <p><strong>Valor do Turno:</strong> O valor será recalculado automaticamente com base na nova duração do turno.</p>
             </div>

            <div class="form-group" style="margin-top: 30px;">
                <button type="submit">Atualizar Vaga</button>
            </div>
        </form>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('vaga-form');
        if (!form) return;

        initVisualSelector('store-selection-grid', 'store_id_hidden', 'value');
        initQuantitySelector();
        initEnterNavigation();

        function initVisualSelector(gridId, hiddenInputId, dataAttribute) {
            const grid = document.getElementById(gridId);
            const hiddenInput = document.getElementById(hiddenInputId);
            if (!grid || !hiddenInput) return;
            grid.addEventListener('click', function(e) {
                const clickedBox = e.target.closest('.icon-box');
                if (clickedBox) {
                    grid.querySelectorAll('.icon-box').forEach(box => box.classList.remove('selected'));
                    clickedBox.classList.add('selected');
                    hiddenInput.value = clickedBox.dataset[dataAttribute];
                }
            });
        }

        function initQuantitySelector() {
            const vagaGrid = document.getElementById('vaga-selection-grid');
            if (!vagaGrid) return;
            const vagaBoxes = vagaGrid.querySelectorAll('.icon-box:not(#digit-quantity-button)');
            const numPositionsHiddenInput = document.getElementById('num_positions_hidden');
            const vagaCountSpan = document.getElementById('vaga-count');
            const digitQuantityButton = document.getElementById('digit-quantity-button');
            const quantityInputGroup = document.getElementById('quantity-input-group');
            const quantityInput = document.getElementById('quantity-input');
            
            function updateCount(count) {
                numPositionsHiddenInput.value = count;
                vagaCountSpan.textContent = count;
            }

            // Lógica de pré-seleção para edição
            const initialCount = parseInt(numPositionsHiddenInput.value) || 0;
            if (initialCount > 0 && initialCount <= 5) {
                vagaBoxes.forEach((box, index) => {
                    if ((index + 1) == initialCount) {
                        box.classList.add('selected');
                    }
                });
            } else if (initialCount > 5) {
                digitQuantityButton.classList.add('selected');
                quantityInputGroup.style.display = 'block';
                quantityInput.value = initialCount;
            }

            vagaBoxes.forEach(box => {
                box.addEventListener('click', function() {
                    digitQuantityButton.classList.remove('selected');
                    quantityInputGroup.style.display = 'none';
                    quantityInput.value = '';
                    const clickedId = parseInt(this.dataset.vagaId);
                    vagaBoxes.forEach(b => b.classList.remove('selected'));
                    this.classList.add('selected');
                    updateCount(clickedId);
                });
            });

            if (digitQuantityButton) {
                digitQuantityButton.addEventListener('click', function() {
                    vagaBoxes.forEach(b => b.classList.remove('selected'));
                    this.classList.add('selected');
                    quantityInputGroup.style.display = 'block';
                    quantityInput.focus();
                    updateCount(parseInt(quantityInput.value) || 0);
                });
            }

            if (quantityInput) {
                quantityInput.addEventListener('input', function() {
                    updateCount(parseInt(this.value) || 0);
                });
            }
        }

        function initEnterNavigation() {
            const focusableElements = Array.from(
                form.querySelectorAll('input:not([type="hidden"]):not([readonly]):not([disabled]), select:not([disabled]), [tabindex="0"]')
            );
            form.addEventListener('keydown', function(event) {
                if (event.key === 'Enter' && event.target.tagName !== 'TEXTAREA') {
                    event.preventDefault();
                    const currentElement = document.activeElement;
                    const currentIndex = focusableElements.indexOf(currentElement);
                    if (currentIndex > -1 && (currentIndex + 1) < focusableElements.length) {
                        const nextElement = focusableElements[currentIndex + 1];
                        if (nextElement) nextElement.focus();
                    } else {
                        form.querySelector('button[type="submit"]').click();
                    }
                }
            });
        }
    });
    </script>
</body>
</html>
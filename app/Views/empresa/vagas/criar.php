<?php
// --- LÓGICA PARA REORDENAR AS FUNÇÕES ---
// Garante que "Operador de Caixa" seja a primeira opção e pré-selecionada.
$operadorDeCaixa = null;
$outrasFuncoes = [];
if (isset($jobFunctions)) {
    foreach ($jobFunctions as $funcao) {
        if ($funcao['name'] === 'Operador de Caixa') {
            $operadorDeCaixa = $funcao;
        } else {
            $outrasFuncoes[] = $funcao;
        }
    }
}
if ($operadorDeCaixa) {
    array_unshift($outrasFuncoes, $operadorDeCaixa);
}
$jobFunctionsOrdenado = $outrasFuncoes;
// --- FIM DA LÓGICA DE REORDENAÇÃO ---
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Publicar Nova Vaga - TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
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
</head>
<body>
    <div class="container" style="padding: 40px 0; max-width: 800px;">
        <p style="margin-bottom: 30px;"><a href="/painel/empresa">&larr; Voltar para o Painel</a></p>
        
        <form action="/painel/vagas/criar" method="POST" class="form-panel" id="vaga-form" enctype="multipart/form-data">
            <h1>Publicar Nova Vaga/Turno</h1>
            
            <?php
            display_flash_message();
            csrf_field();
            ?>

            <fieldset>
                <legend>Detalhes da Vaga</legend>
                
                <div class="form-group">
                    <label>Para qual Loja é esta Vaga?</label>
                    <div class="icon-grid" id="store-selection-grid">
                        <?php if (empty($stores)): ?>
                            <div class="info-box" style="grid-column: 1 / -1;"><p>Nenhuma loja encontrada.</p></div>
                        <?php else: ?>
                            <?php foreach ($stores as $index => $store): ?>
                                <div class="icon-box <?= $index === 0 ? 'selected' : '' ?>" data-store-id="<?= $store['id'] ?>" tabindex="0">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 3L2 12h3v8h14v-8h3L12 3zm0 2.83L15.17 9H8.83L12 5.83zM7 18v-6h10v6H7z"/></svg>
                                    <?= htmlspecialchars($store['name']) ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <input type="hidden" name="store_id" id="store_id_hidden" value="<?= !empty($stores) ? htmlspecialchars($stores[0]['id']) : '' ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Função da Vaga</label>
                    <div class="icon-grid" id="job-type-grid">
                         <?php if (empty($jobFunctionsOrdenado)): ?>
                            <div class="info-box">Nenhuma função encontrada.</div>
                         <?php else: ?>
                            <?php foreach ($jobFunctionsOrdenado as $index => $function): ?>
                                 <div class="icon-box <?= $index === 0 ? 'selected' : '' ?>" data-job-id="<?= $function['id'] ?>" tabindex="0">
                                     <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z" /></svg>
                                     <?= htmlspecialchars($function['name']) ?>
                                 </div>
                            <?php endforeach; ?>
                         <?php endif; ?>
                    </div>
                    <input type="hidden" name="job_function_id" id="job_function_id_hidden" value="<?= !empty($jobFunctionsOrdenado) ? htmlspecialchars($jobFunctionsOrdenado[0]['id']) : '' ?>" required>
                </div>

                <div class="form-group">
                    <label>Data do Turno</label>
                    <input type="date" name="shift_date" required class="form-control">
                </div>
                
                <div style="display: flex; gap: 20px;">
                    <div class="form-group" style="flex: 1;"><label>Hora de Início</label><input type="time" name="start_time" required></div>
                    <div class="form-group" style="flex: 1;"><label>Hora de Fim</label><input type="time" name="end_time" required></div>
                </div>

                <div class="form-group">
                    <label>Quantidade de Vagas (<span id="vaga-count">1</span> selecionada(s))</label>
                    <div class="icon-grid" id="vaga-selection-grid">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <div class="icon-box <?= $i === 1 ? 'selected' : '' ?>" data-vaga-id="<?= $i ?>" tabindex="0"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z" /></svg><?= $i ?> Vaga<?= ($i > 1) ? 's' : '' ?></div>
                        <?php endfor; ?>
                         <div class="icon-box digit-quantity-button" id="digit-quantity-button" tabindex="0"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="width:24px; height:24px;"><path d="M3 17.25V19.25H21V17.25H3M3 11.25V13.25H21V11.25H3M3 5.25V7.25H21V5.25H3Z"></path></svg>Digitar</div>
                    </div>
                    <div class="form-group" id="quantity-input-group" style="display: none; margin-top: 15px;">
                         <label for="quantity-input">Digite a quantidade desejada:</label>
                         <input type="number" id="quantity-input" min="1" placeholder="Ex: 8">
                    </div>
                    <input type="hidden" name="num_positions" id="num_positions_hidden" value="1" required>
                </div>

                <div class="form-group">
                    <input type="checkbox" id="is_holiday" name="is_holiday" value="1" style="margin-right: 5px;">
                    <label for="is_holiday" style="display: inline; font-weight: normal;">Marcar como Feriado (adiciona bónus ao valor)</label>
                </div>
            </fieldset>

             <div class="info-box">
                <p><strong>Valor do Turno:</strong> O valor será calculado automaticamente com base na função, duração e se é feriado.</p>
             </div>

            <div class="form-group" style="margin-top: 30px;">
                <button type="submit">Publicar Vaga</button>
            </div>
        </form>
    </div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('vaga-form');
        if (!form) return;

        initStoreSelector();
        initJobTypeSelector(); // Esta função será corrigida
        initQuantitySelector();
        initSmartDate();
        initEnterNavigation();
        initFormValidation();

        function initStoreSelector() {
            const storeGrid = document.getElementById('store-selection-grid');
            const storeHiddenInput = document.getElementById('store_id_hidden');
            if (storeGrid && storeHiddenInput) {
                storeGrid.addEventListener('click', function(e) {
                    const clickedBox = e.target.closest('.icon-box');
                    if (clickedBox && !clickedBox.classList.contains('disabled')) {
                        storeGrid.querySelectorAll('.icon-box').forEach(box => box.classList.remove('selected'));
                        clickedBox.classList.add('selected');
                        storeHiddenInput.value = clickedBox.dataset.storeId;
                    }
                });
            }
        }
        
        // ==========================================================
        // INÍCIO DA CORREÇÃO
        // ==========================================================
        function initJobTypeSelector() {
            const jobTypeGrid = document.getElementById('job-type-grid');
            // CORREÇÃO 1: Usando o ID correto do input hidden
            const jobTypeHiddenInput = document.getElementById('job_function_id_hidden'); 
            
            if(jobTypeGrid && jobTypeHiddenInput) {
                jobTypeGrid.addEventListener('click', function(e) {
                    const clickedBox = e.target.closest('.icon-box');
                    if (clickedBox && !clickedBox.classList.contains('disabled')) {
                        jobTypeGrid.querySelectorAll('.icon-box').forEach(box => box.classList.remove('selected'));
                        clickedBox.classList.add('selected');
                        // CORREÇÃO 2: Usando o nome correto do dataset (jobId em vez de jobType)
                        jobTypeHiddenInput.value = clickedBox.dataset.jobId; 
                    }
                });
            }
        }
        // ==========================================================
        // FIM DA CORREÇÃO
        // ==========================================================

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

            vagaBoxes.forEach(box => {
                box.addEventListener('click', function() {
                    quantityInputGroup.style.display = 'none';
                    quantityInput.value = '';
                    const clickedId = parseInt(this.dataset.vagaId);
                    const isCurrentlySelected = this.classList.contains('selected');
                    // Correção na lógica de seleção de múltiplos para maior clareza
                    vagaBoxes.forEach(b => b.classList.remove('selected'));
                    for (let i = 0; i < clickedId; i++) {
                        vagaBoxes[i].classList.add('selected');
                    }
                    updateCount(clickedId);
                });
            });

            if (digitQuantityButton) {
                digitQuantityButton.addEventListener('click', function() {
                    vagaBoxes.forEach(b => b.classList.remove('selected'));
                    this.classList.add('selected'); // Adiciona a classe 'selected' ao botão "Digitar"
                    quantityInputGroup.style.display = 'block';
                    quantityInput.focus();
                    updateCount(parseInt(quantityInput.value) || 0);
                });
            }

            if (quantityInput) {
                quantityInput.addEventListener('input', function() {
                    // Garante que o botão "Digitar" permaneça selecionado
                    vagaBoxes.forEach(b => b.classList.remove('selected'));
                    if (digitQuantityButton) digitQuantityButton.classList.add('selected');
                    updateCount(parseInt(this.value) || 0);
                });
            }
        }

        function initSmartDate() {
            const dateInput = document.querySelector('input[type="date"]');
            if(dateInput) {
                const today = new Date();
                const todayString = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0') + '-' + String(today.getDate()).padStart(2, '0');
                dateInput.setAttribute('min', todayString);
            }
        }

        function initEnterNavigation() {
            // Esta função pode ser complexa, a original está mantida
            const focusableElements = Array.from(form.querySelectorAll('input:not([type="hidden"]), select, [tabindex="0"]'));
            form.addEventListener('keydown', function(event) {
                if (event.key === 'Enter') {
                    event.preventDefault(); 
                    const currentElement = document.activeElement;
                    const currentIndex = focusableElements.indexOf(currentElement);
                    if (currentIndex > -1 && (currentIndex + 1) < focusableElements.length) {
                        const nextElement = focusableElements[currentIndex + 1];
                        if(nextElement) nextElement.focus();
                    } else if (currentIndex === focusableElements.length - 1) {
                        form.querySelector('button[type="submit"]').click();
                    }
                }
            });
        }
        
        function initFormValidation() {
            // A sua validação original
            form.addEventListener('submit', function(event) {
                const storeId = document.getElementById('store_id_hidden').value;
                const numPositions = document.getElementById('num_positions_hidden').value;

                if (!storeId || storeId === '0') {
                    alert('Por favor, selecione uma loja para a vaga.');
                    event.preventDefault();
                    return;
                }
                if (!numPositions || parseInt(numPositions, 10) <= 0) {
                    alert('Por favor, selecione ou digite a quantidade de vagas.');
                    event.preventDefault();
                    return;
                }
            });
        }
    });
</script>
</body>
</html>
<?php
if (!isset($_SESSION['user_id']) || empty($_SESSION['company_id'])) {
    header('Location: /login'); exit();
}
// Garante que a variável $stores existe, vinda do PainelEmpresaController, para evitar erros.
if (!isset($stores)) { 
    $stores = []; 
}

// Verifica se há uma mensagem de sucesso na URL para mostrar o bloco de feedback.
$successMessage = isset($_GET['status']) && $_GET['status'] === 'success';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Publicar Nova Vaga - TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
	
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

    </style>
</head>
<body>
    <div class="container" style="padding: 40px 0; max-width: 800px;">
        <p style="margin-bottom: 30px;"><a href="/painel/empresa">&larr; Voltar para o Painel</a></p>
        
        <?php if ($successMessage): ?>
            <div class="success-message">
                Vaga publicada com sucesso! O que deseja fazer agora?
                <div>
                    <a href="/painel/vagas/criar" class="btn-secondary">Lançar Outra Vaga</a>
                    <a href="/painel/vagas" class="btn-primary">Ver Todas as Vagas</a>
                </div>
            </div>
        <?php endif; ?>

        <form action="/painel/vagas/criar" method="POST" class="form-panel" id="vaga-form" style="<?= $successMessage ? 'display:none;' : '' ?>">
            <h1>Publicar Nova Vaga/Turno</h1>
            
            <fieldset>
                <legend>Detalhes da Vaga</legend>
                
                <div class="form-group">
                    <label>Para qual Loja é esta Vaga?</label>
                    <div class="icon-grid" id="store-selection-grid">
                        <?php if (empty($stores)): ?>
                            <div class="info-box" style="grid-column: 1 / -1;">
                                <p>Nenhuma loja encontrada. O Dono da Plataforma precisa de adicionar lojas à sua empresa primeiro.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($stores as $store): ?>
                                <div class="icon-box" data-store-id="<?= $store['id'] ?>" tabindex="0">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 3L2 12h3v8h14v-8h3L12 3zm0 2.83L15.17 9H8.83L12 5.83zM7 18v-6h10v6H7z"/></svg>
                                    <?= htmlspecialchars($store['name']) ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <input type="hidden" name="store_id" id="store_id_hidden" value="" required>
                </div>
                
                <div class="form-group">
                    <label>Tipo de Vaga</label>
                    <div class="icon-grid" id="job-type-grid">
                        <div class="icon-box selected" data-job-type="Operador de Caixa" tabindex="0"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M5 21a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5zm1-2h12V9H6v10zm8-12h-4v2h4V7zM5 7V5h14v2H5z"/><path d="M11 11h2v6h-2v-6zm-4 0h2v6H7v-6zm8 0h2v6h-2v-6z"/></svg>Operador de Caixa</div>
                        <div class="icon-box" data-job-type="Repositor" tabindex="0"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M11 2.05V5.05C11 5.33 11.22 5.55 11.5 5.55C11.78 5.55 12 5.33 12 5.05V2.05C12 1.92 11.95 1.79 11.85 1.7L11.71 1.57C11.58 1.45 11.42 1.45 11.29 1.57L11.15 1.7C11.05 1.79 11 1.92 11 2.05M5.5 11H3V22H5.5V11M21 11H18.5V22H21V11M14.5 11H9.5V16H14.5V11M17.5 14H15.5V16H17.5V14M8.5 14H6.5V16H8.5V14Z" /></svg>Repositor</div>
                        <div class="icon-box" data-job-type="Embalador" tabindex="0"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M16,5H14.5L18.5,2H15.5L11.5,5H9.5L13.5,2H10.5L6.5,5H5A2,2 0 0,0 3,7V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V7A2,2 0 0,0 19,5H16M19,19H5V7H19V19Z" /></svg>Embalador</div>
                        <div class="icon-box" data-job-type="Balconista de Açougue" tabindex="0"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M16.83,13.44L15.41,12L16.83,10.56C17.3,10.09 17.65,9.22 17.53,8.47C17.22,6.54 15.46,5.15 13.5,5.15H11V7.15H13.5C14.41,7.15 15.2,7.82 15.39,8.7C15.47,9.08 15.36,9.5 15.09,9.78L13.68,11.2L12.27,9.78C12,9.5 11.58,9.39 11.2,9.47C10.32,9.66 9.65,10.45 9.65,11.36V13.86C9.65,14.77 10.32,15.56 11.2,15.75C11.58,15.83 12,15.72 12.27,15.44L13.68,14L15.09,15.44C15.36,15.72 15.78,15.83 16.16,15.75C17.05,15.56 17.72,14.77 17.72,13.86V13.11C17.72,13.11 17.4,13.23 16.83,13.44M11,3H13.5C16.5,3 19,5.5 19,8.5C19,10.27 18.11,11.83 16.83,12.69V13.11C16.83,15.39 15.22,17.27 13.16,17.63L18.71,23.18L17.29,24.59L11.5,18.8V21H9.5V3H11Z" /></svg>Balconista de Açougue</div>
                    </div>
                    <input type="hidden" name="title" id="job-type-hidden" value="Operador de Caixa" required>
                </div>

                <div class="form-group">
                    <label>Data do Turno</label>
                    <input type="text" id="shift_date" name="shift_date" required placeholder="DD/MM/AAAA" maxlength="10">
                </div>
                
                <div style="display: flex; gap: 20px;">
                    <div class="form-group" style="flex: 1;"><label>Hora de Início</label><input type="time" id="start_time" name="start_time" required></div>
                    <div class="form-group" style="flex: 1;"><label>Hora de Fim</label><input type="time" id="end_time" name="end_time" required></div>
                </div>

                <div class="form-group">
                    <label>Quantidade de Vagas (<span id="vaga-count">0</span> selecionada(s))</label>
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
                    <input type="hidden" name="num_positions" id="num_positions_hidden" value="0" required>
                </div>

                <div class="form-group">
                    <input type="checkbox" id="is_holiday" name="is_holiday" value="1" style="margin-right: 5px;">
                    <label for="is_holiday" style="display: inline; font-weight: normal;">Marcar como Feriado</label>
                </div>
            </fieldset>

             <div class="info-box">
                <p><strong>Descrição Padrão da Vaga:</strong> O operador será responsável pela operação do caixa, incluindo registo de produtos, recebimento de pagamentos e atendimento ao cliente.</p>
                <p style="margin-top: 10px;"><strong>Valor do Turno:</strong> O valor será calculado automaticamente com base nas configurações da plataforma.</p>
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
            initJobTypeSelector();
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
            function initJobTypeSelector() {
                const jobTypeGrid = document.getElementById('job-type-grid');
                const jobTypeHiddenInput = document.getElementById('job-type-hidden');
                if(jobTypeGrid && jobTypeHiddenInput) {
                    jobTypeGrid.addEventListener('click', function(e) {
                        const clickedBox = e.target.closest('.icon-box');
                        if (clickedBox && !clickedBox.classList.contains('disabled')) {
                            jobTypeGrid.querySelectorAll('.icon-box').forEach(box => box.classList.remove('selected'));
                            clickedBox.classList.add('selected');
                            jobTypeHiddenInput.value = clickedBox.dataset.jobType;
                        }
                    });
                }
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
                vagaBoxes.forEach(box => {
                    box.addEventListener('click', function() {
                        quantityInputGroup.style.display = 'none';
                        quantityInput.value = '';
                        const clickedId = parseInt(this.dataset.vagaId);
                        const isCurrentlySelected = this.classList.contains('selected');
                        const highestSelectedId = document.querySelectorAll('#vaga-selection-grid .icon-box.selected').length;
                        vagaBoxes.forEach(b => b.classList.remove('selected'));
                        if (!(isCurrentlySelected && clickedId === highestSelectedId)) {
                            for (let i = 0; i < clickedId; i++) { vagaBoxes[i].classList.add('selected'); }
                        }
                        updateCount(document.querySelectorAll('#vaga-selection-grid .icon-box.selected').length);
                    });
                });
                if (digitQuantityButton) {
                    digitQuantityButton.addEventListener('click', function() {
                        vagaBoxes.forEach(b => b.classList.remove('selected'));
                        quantityInputGroup.style.display = 'block';
                        quantityInput.focus();
                        updateCount(parseInt(quantityInput.value) || 0);
                    });
                }
                if (quantityInput) {
                    quantityInput.addEventListener('input', function() { updateCount(parseInt(this.value) || 0); });
                }
            }
            function initSmartDate() {
                const shiftDateInput = document.getElementById('shift_date');
                if (shiftDateInput) {
                    shiftDateInput.addEventListener('input', function(e) {
                        let value = e.target.value.replace(/\D/g, '');
                        if (value.length > 2) value = value.substring(0, 2) + '/' + value.substring(2);
                        if (value.length > 5) value = value.substring(0, 5) + '/' + value.substring(5, 9);
                        e.target.value = value;
                    });
                }
            }
            function initEnterNavigation() {
                const focusableElements = Array.from(form.querySelectorAll('input:not([type="hidden"]), select, [tabindex="0"]'));
                form.addEventListener('keydown', function(event) {
                    if (event.key === 'Enter') {
                        event.preventDefault(); 
                        const currentElement = document.activeElement;
                        const shiftDateInput = document.getElementById('shift_date');
                        if (currentElement.id === 'shift_date' && /^\d{2}\/\d{2}$/.test(shiftDateInput.value)) {
                            shiftDateInput.value = shiftDateInput.value + '/' + new Date().getFullYear();
                        }
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
                form.addEventListener('submit', function(event) {
                    const storeId = document.getElementById('store_id_hidden').value;
                    const numPositions = document.getElementById('num_positions_hidden').value;
                    const shiftDateInput = document.getElementById('shift_date');

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
                    
                    const dateParts = shiftDateInput.value.split('/');
                    if (dateParts.length === 3) {
                        const year = parseInt(dateParts[2], 10);
                        const currentYear = new Date().getFullYear();
                        const maxYear = currentYear + 5;

                        if (year < currentYear || year > maxYear) {
                            alert(`Ano inválido. Por favor, insira um ano entre ${currentYear} e ${maxYear}.`);
                            event.preventDefault();
                            return;
                        }
                    } else {
                        alert('Formato de data inválido. Use DD/MM/AAAA.');
                        event.preventDefault();
                        return;
                    }
                });
            }
        });
    </script>
</body>
</html>
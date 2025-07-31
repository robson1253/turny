<?php
if (!isset($_SESSION['user_id']) || empty($_SESSION['company_id']) || !isset($shift)) {
    header('Location: /login'); exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Vaga - TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
    <style> /* Estilos idênticos ao do criar.php */ </style>
</head>
<body>
    <div class="container" style="padding: 40px 0; max-width: 800px;">
        <p style="margin-bottom: 30px;"><a href="/painel/vagas">&larr; Voltar para a Lista de Vagas</a></p>
        
        <form action="/painel/vagas/atualizar" method="POST" class="form-panel">
            <h1>Editar Vaga/Turno</h1>

            <input type="hidden" name="id" value="<?= $shift['id'] ?>">
            
            <fieldset>
                <legend>Detalhes da Vaga</legend>
                
                <div class="form-group">
                    <label>Tipo de Vaga</label>
                    <div class="icon-grid" id="job-type-grid">
                        <div class="icon-box <?= $shift['title'] === 'Operador de Caixa' ? 'selected' : '' ?>" data-job-type="Operador de Caixa" tabindex="0">
                            Operador de Caixa
                        </div>
                        <div class="icon-box disabled" data-job-type="Embalador">
                            Embalador
                        </div>
                        <div class="icon-box disabled" data-job-type="Repositor">
                            Repositor
                        </div>
                    </div>
                    <input type="hidden" name="title" id="job-type-hidden" value="<?= htmlspecialchars($shift['title']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Data do Turno</label>
                    <input type="date" id="shift_date" name="shift_date" required value="<?= htmlspecialchars($shift['shift_date']) ?>">
                </div>
                
                <div style="display: flex; gap: 20px;">
                    <div class="form-group" style="flex: 1;">
                        <label>Hora de Início</label>
                        <input type="time" id="start_time" name="start_time" required value="<?= htmlspecialchars($shift['start_time']) ?>">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Hora de Fim</label>
                        <input type="time" id="end_time" name="end_time" required value="<?= htmlspecialchars($shift['end_time']) ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Quantidade de Vagas (<span id="vaga-count"><?= htmlspecialchars($shift['num_positions']) ?></span> selecionada(s))</label>
                    <div class="icon-grid" id="vaga-selection-grid">
                         </div>
                    <input type="hidden" name="num_positions" id="num_positions_hidden" value="<?= htmlspecialchars($shift['num_positions']) ?>" required>
                </div>

                <div class="form-group">
                    <input type="checkbox" id="is_holiday" name="is_holiday" value="1" <?= $shift['is_holiday'] ? 'checked' : '' ?> style="margin-right: 5px;">
                    <label for="is_holiday" style="display: inline; font-weight: normal;">Marcar como Feriado</label>
                </div>
            </fieldset>

            <div class="form-group" style="margin-top: 30px;">
                <button type="submit">Atualizar Vaga</button>
            </div>
        </form>
    </div>

    <script>
        // O JavaScript é idêntico ao do criar.php, mas vamos pré-selecionar as vagas
        document.addEventListener('DOMContentLoaded', function() {
            // ... (lógica de seleção de tipo de vaga) ...

            const vagaBoxes = document.querySelectorAll('#vaga-selection-grid .icon-box:not(#digit-quantity-button)');
            const numPositionsHiddenInput = document.getElementById('num_positions_hidden');
            
            // Pré-seleciona a quantidade de vagas vinda da base de dados
            const initialCount = parseInt(numPositionsHiddenInput.value) || 0;
            if (initialCount > 0 && initialCount <= vagaBoxes.length) {
                for (let i = 0; i < initialCount; i++) {
                    vagaBoxes[i].classList.add('selected');
                }
            }
            
            // ... (resto do JavaScript idêntico ao do criar.php) ...
        });
    </script>
</body>
</html>
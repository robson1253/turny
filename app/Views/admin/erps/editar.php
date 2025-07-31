<?php
if (!isset($_SESSION['user_id']) || empty($_SESSION['company_id'])) {
    header('Location: /login'); exit();
}
// Garante que as variáveis essenciais existem
if (!isset($shift) || !isset($stores) || !isset($settings)) {
    die('Erro fatal: Dados essenciais para a edição da vaga não foram carregados.');
}

// CORREÇÃO DO FUSO HORÁRIO PARA EXIBIÇÃO
$timezone = new DateTimeZone('America/Sao_Paulo');
$dateObj = new DateTime($shift['shift_date'], $timezone);
$formattedDateForInput = $dateObj->format('Y-m-d');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Vaga - TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="container" style="padding: 40px 0; max-width: 800px;">
        <p><a href="/painel/vagas/dia?store_id=<?= $shift['store_id'] ?>&date=<?= $shift['shift_date'] ?>">&larr; Voltar para as vagas do dia</a></p>
        
        <form action="/painel/vagas/atualizar" method="POST" class="form-panel" id="vaga-form">
            <h1>Editar Vaga/Turno</h1>
            <input type="hidden" name="id" value="<?= $shift['id'] ?>">
            
            <fieldset>
                <legend>Detalhes da Vaga</legend>
                
                <div class="form-group">
                    <label>Loja da Vaga</label>
                    <select name="store_id" required>
                        <?php foreach ($stores as $store): ?>
                            <option value="<?= $store['id'] ?>" <?= ($store['id'] == $shift['store_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($store['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Tipo de Vaga</label>
                    <select name="title" required>
                        <option value="Operador de Caixa" <?= ($shift['title'] == 'Operador de Caixa') ? 'selected' : '' ?>>Operador de Caixa</option>
                        </select>
                </div>

                <div class="form-group">
                    <label>Data do Turno</label>
                    <input type="date" name="shift_date" required value="<?= htmlspecialchars($formattedDateForInput) ?>">
                </div>
                
                <div style="display: flex; gap: 20px;">
                    <div class="form-group" style="flex: 1;"><label>Hora de Início</label><input type="time" name="start_time" required value="<?= htmlspecialchars($shift['start_time']) ?>"></div>
                    <div class="form-group" style="flex: 1;"><label>Hora de Fim</label><input type="time" name="end_time" required value="<?= htmlspecialchars($shift['end_time']) ?>"></div>
                </div>

                <div class="form-group">
                    <label>Quantidade de Vagas</label>
                    <input type="number" name="num_positions" required min="1" value="<?= htmlspecialchars($shift['num_positions']) ?>">
                </div>

                <div class="form-group">
                    <input type="checkbox" id="is_holiday" name="is_holiday" value="1" <?= $shift['is_holiday'] ? 'checked' : '' ?> style="margin-right: 5px;">
                    <label for="is_holiday" style="display: inline; font-weight: normal;">Marcar como Feriado (adiciona bónus ao valor)</label>
                </div>
            </fieldset>

            <div class="info-box">
                <p><strong>Valor do Turno:</strong> O valor é calculado automaticamente. O valor atual para esta vaga é R$ <?= htmlspecialchars(number_format($shift['operator_payment'], 2, ',', '.')) ?>.</p>
            </div>

            <div class="form-group" style="margin-top: 30px;">
                <button type="submit">Atualizar Vaga</button>
            </div>
        </form>
    </div>
</body>
</html>
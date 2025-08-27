<?php
// Prepara as variáveis para serem exibidas no formulário, com valores padrão.
$settings = $settings ?? []; // Garante que a variável exista
$jobFunctions = $jobFunctions ?? []; // Garante que a variável exista

$taxa_servico = $settings['taxa_servico_fixa'] ?? '5.00';
$bonus_feriado = $settings['bonus_feriado'] ?? '25.00';
$limite_horas_turno = $settings['limite_horas_turno'] ?? '7.00'; // Pegando o limite global se existir

// Formata os valores monetários para o padrão brasileiro (vírgula) para exibição
$taxa_formatada = number_format((float)$taxa_servico, 2, ',', '.');
$bonus_formatado = number_format((float)$bonus_feriado, 2, ',', '.');
$limite_formatado = number_format((float)$limite_horas_turno, 2, ',', '.');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Configurações Gerais - TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .form-group { margin-bottom: 20px; }
        .form-group label { font-weight: bold; margin-bottom: 5px; display: block; }
        .flex-row { display: flex; gap: 15px; align-items: center; flex-wrap: wrap; }
        .flex-row input { width: 120px; }
    </style>
</head>
<body>
    <div class="container" style="padding: 40px 0; max-width: 800px;">
        <h1>Configurações Gerais da Plataforma</h1>
        <p><a href="/dashboard">&larr; Voltar ao Dashboard</a></p>
        
        <?php \display_flash_message(); ?>

        <form action="/admin/settings/update" method="POST" class="form-panel" id="settings-form" style="margin-top: 20px;">
            <?php \csrf_field(); ?>
            
            <fieldset>
                <legend>Valores e Regras de Negócio Globais</legend>
                
                <div class="form-group">
                    <label for="taxa_servico_fixa">Sua Taxa de Serviço (R$)</label>
                    <input type="text" id="taxa_servico_fixa" name="settings[taxa_servico_fixa]" value="<?= htmlspecialchars($taxa_formatada) ?>" placeholder="Ex: 5,00">
                </div>

                <div class="form-group">
                    <label for="bonus_feriado">Bónus por Feriado (R$)</label>
                    <input type="text" id="bonus_feriado" name="settings[bonus_feriado]" value="<?= htmlspecialchars($bonus_formatado) ?>" placeholder="Ex: 25,00">
                </div>
            </fieldset>

            <fieldset>
                <legend>Valores por Função (Taxa/Hora e Máx. Horas)</legend>
                
                <?php if (empty($jobFunctions)): ?>
                    <p>Nenhuma função de trabalho encontrada. <a href="/admin/funcoes/criar">Crie uma aqui</a>.</p>
                <?php else: ?>
                    <?php foreach ($jobFunctions as $function): ?>
                        <div class="form-group flex-row">
                            <label style="min-width: 180px; flex-grow: 1;"><?= htmlspecialchars($function['name']) ?></label>
                            
                            <div>
                                <label for="function_<?= $function['id'] ?>_rate" style="font-size: 0.8em; font-weight: normal;">Valor/Hora (R$)</label>
                                <input type="text" 
                                       id="function_<?= $function['id'] ?>_rate" 
                                       name="functions[<?= $function['id'] ?>][hourly_rate]" 
                                       value="<?= htmlspecialchars(number_format((float)($function['hourly_rate'] ?? 0), 2, ',', '.')) ?>" 
                                       placeholder="Ex: 12,50">
                            </div>

                            <div>
                                <label for="function_<?= $function['id'] ?>_max" style="font-size: 0.8em; font-weight: normal;">Horas Máx.</label>
                                <input type="number" step="0.25" min="1" max="24"
                                       id="function_<?= $function['id'] ?>_max" 
                                       name="functions[<?= $function['id'] ?>][max_hours]" 
                                       value="<?= htmlspecialchars($function['max_hours'] ?? 7) ?>" 
                                       placeholder="Máx horas">
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </fieldset>
            
            <div class="form-group" style="margin-top: 30px;">
                <button type="submit">Guardar Todas as Configurações</button>
            </div>
        </form>
    </div>
</body>
</html>
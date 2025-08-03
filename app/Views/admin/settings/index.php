<?php
// Prepara as variáveis para serem exibidas no formulário, com valores padrão.
$taxa_servico = $settings['taxa_servico_fixa'] ?? '5.00';
$bonus_feriado = $settings['bonus_feriado'] ?? '25.00';

// Formata os valores monetários para o padrão brasileiro (vírgula) para exibição
$taxa_formatada = number_format((float)$taxa_servico, 2, ',', '.');
$bonus_formatado = number_format((float)$bonus_feriado, 2, ',', '.');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Configurações Gerais - TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="container" style="padding: 40px 0; max-width: 700px;">
        <h1>Configurações Gerais da Plataforma</h1>
        <p><a href="/dashboard">&larr; Voltar ao Dashboard</a></p>
        
        <?php display_flash_message(); ?>

        <form action="/admin/settings/update" method="POST" class="form-panel" id="settings-form" style="margin-top: 20px;">
            <?php csrf_field(); ?>
            
            <fieldset>
                <legend>Valores e Regras de Negócio Globais</legend>
                
                <div class="form-group">
                    <label for="taxa_servico_fixa">Sua Taxa de Serviço (Valor Fixo)</label>
                    <input type="text" id="taxa_servico_fixa" name="settings[taxa_servico_fixa]" value="<?= htmlspecialchars($taxa_formatada) ?>" placeholder="Ex: 5,00">
                    <small style="color: #777;">Use vírgula para os centavos. Este valor fixo é adicionado ao custo total do turno para a empresa.</small>
                </div>

                <div class="form-group">
                    <label for="bonus_feriado">Bónus por Feriado (valor fixo)</label>
                    <input type="text" id="bonus_feriado" name="settings[bonus_feriado]" value="<?= htmlspecialchars($bonus_formatado) ?>" placeholder="Ex: 25,00">
                    <small style="color: #777;">Use vírgula para os centavos. Este valor é somado ao pagamento do operador se o turno for marcado como feriado.</small>
                </div>
            </fieldset>

            <fieldset>
                <legend>Valores por Função (Taxa por Hora)</legend>
                
                <?php if (empty($jobFunctions)): ?>
                    <p>Nenhuma função de trabalho encontrada. <a href="/admin/funcoes/criar">Crie uma aqui</a>.</p>
                <?php else: ?>
                    <?php foreach ($jobFunctions as $function): ?>
                        <div class="form-group">
                            <label for="function_<?= $function['id'] ?>"><?= htmlspecialchars($function['name']) ?></label>
                            <input type="text" 
                                   id="function_<?= $function['id'] ?>" 
                                   name="functions[<?= $function['id'] ?>]" 
                                   value="<?= htmlspecialchars(number_format((float)$function['hourly_rate'], 2, ',', '.')) ?>" 
                                   placeholder="Ex: 12,50">
                        </div>
                    <?php endforeach; ?>
                    <small style="color: #777;">Use vírgula para os centavos. Este é o valor que o operador recebe por cada hora de trabalho.</small>
                <?php endif; ?>

            </fieldset>
            
            <div class="form-group" style="margin-top: 30px;">
                <button type="submit">Guardar Todas as Configurações</button>
            </div>
        </form>
    </div>
</body>
</html>
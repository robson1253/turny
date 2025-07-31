<?php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    die('Acesso negado.');
}

// Prepara as variáveis para serem exibidas no formulário, com valores padrão
$valor_minimo = $settings['valor_minimo_turno_6h'] ?? '50.00';
$taxa_servico = $settings['taxa_servico_percentual'] ?? '8.33';

// Formata o valor monetário para o padrão brasileiro (vírgula)
$valor_formatado = number_format((float)$valor_minimo, 2, ',', '.');
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
        
        <form action="/admin/settings/update" method="POST" class="form-panel" id="settings-form" style="margin-top: 20px;">
            <fieldset>
                <legend>Valores e Regras de Negócio</legend>
                
                <div class="form-group">
                    <label for="valor_minimo_turno_6h">Valor Mínimo do Turno (pago ao operador)</label>
                    <input type="text" id="valor_minimo_turno_6h" name="settings[valor_minimo_turno_6h]" value="<?= htmlspecialchars($valor_formatado) ?>" placeholder="Ex: 60,00">
                    <small style="color: #777;">Use vírgula para os centavos. Este é o valor base que o operador recebe.</small>
                </div>
                
                <div class="form-group">
                    <label for="taxa_servico_percentual">Sua Taxa de Serviço (%)</label>
                    <input type="text" id="taxa_servico_percentual" name="settings[taxa_servico_percentual]" value="<?= htmlspecialchars($taxa_servico) ?>" placeholder="Ex: 8.33">
                    <small style="color: #777;">Use ponto para os decimais. Esta taxa é adicionada ao valor pago pela empresa.</small>
                </div>

            </fieldset>
            
            <div class="form-group" style="margin-top: 30px;">
                <button type="submit">Guardar Configurações</button>
            </div>
        </form>
    </div>
</body>
</html>
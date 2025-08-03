<?php
// --- LÓGICA PARA REORDENAR E PRÉ-SELECIONAR A FUNÇÃO ---
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
// Se "Operador de Caixa" foi encontrado, coloca-o no início do array.
if ($operadorDeCaixa) {
    array_unshift($outrasFuncoes, $operadorDeCaixa);
}
$jobFunctionsOrdenado = $outrasFuncoes;
// --- FIM DA LÓGICA ---
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerir Turnos Padrão - TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .btn-link-danger {
            background: none;
            border: none;
            padding: 0;
            margin: 0;
            font-family: inherit;
            font-size: 1em;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
            color: var(--cor-perigo, #dc3545);
        }
        .btn-link-danger:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container" style="padding: 40px 0; max-width: 1000px; display: flex; gap: 40px; align-items: flex-start;">
    
        <div style="flex: 1;">
            <h2>Turnos Padrão Criados</h2>
			<p><a href="/painel/empresa">&larr; Voltar para o Painel</a></p>
            <p>Estes são os seus modelos de turno. Eles irão aparecer como sugestões no seu Planeador Semanal.</p>
            
            <?php display_flash_message(); ?>

            <table class="table" style="margin-top: 20px;">
                <thead>
                    <tr>
                        <th>Loja</th>
                        <th>Função do Turno</th>
                        <th>Horário</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($templates)): ?>
                        <tr><td colspan="4" style="text-align: center;">Nenhum turno padrão criado. Adicione um ao lado.</td></tr>
                    <?php else: ?>
                        <?php foreach ($templates as $template): ?>
                            <tr>
                                <td><?= htmlspecialchars($template['store_name']) ?></td>
                                <td><?= htmlspecialchars($template['function_name'] ?? 'Função Apagada') ?></td>
                                <td><?= htmlspecialchars(substr($template['start_time'], 0, 5)) ?> - <?= htmlspecialchars(substr($template['end_time'], 0, 5)) ?></td>
                                <td class="actions">
                                    <form action="/painel/vagas/templates/apagar" method="POST" style="display:inline;" onsubmit="return confirm('Tem a certeza que deseja apagar este modelo de turno?');">
                                        <?php csrf_field(); ?>
                                        <input type="hidden" name="id" value="<?= $template['id'] ?>">
                                        <button type="submit" class="btn-link-danger">Apagar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div style="flex: 0 0 350px;">
            <form action="/painel/vagas/templates/criar" method="POST" class="form-panel">
                <?php csrf_field(); ?>
                <fieldset>
                    <legend>Criar Novo Turno Padrão</legend>
                    <div class="form-group">
                        <label for="store_id">Para qual Loja?</label>
                        <select name="store_id" id="store_id" required>
                            <option value="">Selecione uma loja...</option>
                            <?php foreach ($stores as $store): ?>
                                <option value="<?= $store['id'] ?>"><?= htmlspecialchars($store['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="job_function_id">Função do Turno</label>
                        <select name="job_function_id" id="job_function_id" required>
                            <option value="">Selecione uma função...</option>
                            <?php foreach ($jobFunctionsOrdenado as $function): ?>
                                <!-- Adiciona o atributo 'selected' se o nome for "Operador de Caixa" -->
                                <option value="<?= $function['id'] ?>" <?= ($function['name'] === 'Operador de Caixa') ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($function['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="start_time">Hora de Início</label>
                        <input type="time" name="start_time" id="start_time" required>
                    </div>
                    <div class="form-group">
                        <label for="end_time">Hora de Fim</label>
                        <input type="time" name="end_time" id="end_time" required>
                    </div>
                </fieldset>
                <div class="form-group">
                    <button type="submit">Guardar Turno Padrão</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
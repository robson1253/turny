<?php
if (!isset($_SESSION['user_id']) || empty($_SESSION['company_id'])) {
    header('Location: /login'); exit();
}
if (!isset($stores)) $stores = [];
if (!isset($templates)) $templates = [];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerir Turnos Padrão - TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="container" style="padding: 40px 0; max-width: 1000px; display: flex; gap: 40px;">
	<a href="/painel/empresa">← Voltar para o Painel</a>
        <div style="flex: 1;">
            <h2>Turnos Padrão Criados</h2>
            <p>Estes são os seus modelos de turno. Eles irão aparecer como sugestões no seu novo Planeador Semanal.</p>
            <table class="table">
                <thead>
                    <tr>
                        <th>Loja</th>
                        <th>Título do Turno</th>
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
                                <td><?= htmlspecialchars($template['title']) ?></td>
                                <td><?= htmlspecialchars(substr($template['start_time'], 0, 5)) ?> - <?= htmlspecialchars(substr($template['end_time'], 0, 5)) ?></td>
                                <td class="actions">
                                    <a href="/painel/vagas/templates/apagar?id=<?= $template['id'] ?>" class="disable" onclick="return confirm('Tem a certeza que deseja apagar este modelo de turno?');">Apagar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div style="flex: 0 0 350px;">
            <form action="/painel/vagas/templates/criar" method="POST" class="form-panel">
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
                        <label for="title">Título do Turno</label>
                        <input type="text" name="title" id="title" required placeholder="Ex: Turno da Manhã">
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
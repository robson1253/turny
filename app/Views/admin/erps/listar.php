<?php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403); die('Acesso negado.');
}
if (!isset($erpSystems)) {
    $erpSystems = [];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerir Sistemas ERP - TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="container" style="padding: 40px 0; max-width: 800px;">
        <h1>Gerir Sistemas ERP</h1>
        <p><a href="/dashboard">&larr; Voltar ao Dashboard</a> | <a href="/admin/erps/criar">Adicionar Novo Sistema ERP</a></p>
        
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome do Sistema</th>
                    <th style="width: 180px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($erpSystems)): ?>
                    <tr><td colspan="3">Nenhum sistema ERP registado.</td></tr>
                <?php else: ?>
                    <?php foreach ($erpSystems as $system): ?>
                        <tr>
                            <td><?= htmlspecialchars($system['id']) ?></td>
                            <td><?= htmlspecialchars($system['name']) ?></td>
                            <td class="actions">
                                <a href="/admin/erps/editar?id=<?= $system['id'] ?>">Editar</a>
                                <a href="/admin/erps/apagar?id=<?= $system['id'] ?>" class="disable" onclick="return confirm('Tem a certeza que quer apagar este sistema? Esta ação não pode ser desfeita e irá desassociá-lo de todas as lojas que o usam.')">Apagar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerir Sistemas ERP - Admin</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="container" style="max-width: 800px;">
        <h1>Gerir Sistemas ERP</h1>
        <p><a href="/dashboard">&larr; Voltar ao Dashboard</a> | <a href="/admin/erps/criar" class="btn">Adicionar Novo Sistema</a></p>
        
        <?php display_flash_message(); ?>

        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome do Sistema</th>
                    <th style="width: 200px;">Ações</th>
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
                                <a href="/admin/erps/editar?id=<?= $system['id'] ?>" class="btn edit-btn">Editar</a>
                                <form action="/admin/erps/apagar" method="POST" style="display:inline;" onsubmit="return confirm('Tem a certeza? Apagar um sistema pode causar problemas se ele estiver em uso por alguma loja.');">
                                    <?php csrf_field(); ?>
                                    <input type="hidden" name="id" value="<?= $system['id'] ?>">
                                    <button type="submit" class="btn cancel-btn">Apagar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Sistema ERP - Admin</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="container" style="max-width: 600px;">
        <h1>Editar Sistema ERP</h1>
        <p><a href="/admin/erps">&larr; Voltar para a Lista</a></p>

        <form action="/admin/erps/atualizar" method="POST" class="form-panel">
            <?php display_flash_message(); ?>
            <?php csrf_field(); ?>
            
            <input type="hidden" name="id" value="<?= htmlspecialchars($erpSystem['id']) ?>">
            
            <div class="form-group">
                <label for="name">Nome do Sistema</label>
                <input type="text" id="name" name="name" required value="<?= htmlspecialchars($erpSystem['name']) ?>">
            </div>
            <div class="form-group">
                <button type="submit">Atualizar Sistema</button>
            </div>
        </form>
    </div>
</body>
</html>
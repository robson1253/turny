<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Criar Sistema ERP - Admin</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="container" style="max-width: 600px;">
        <h1>Adicionar Novo Sistema ERP</h1>
        <p><a href="/admin/erps">&larr; Voltar para a Lista</a></p>

        <form action="/admin/erps/criar" method="POST" class="form-panel">
            <?php display_flash_message(); ?>
            <?php csrf_field(); ?>
            
            <div class="form-group">
                <label for="name">Nome do Sistema (Ex: Linx, Totvs, SAP)</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <button type="submit">Guardar Sistema</button>
            </div>
        </form>
    </div>
</body>
</html>
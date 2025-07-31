<?php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403); die('Acesso negado.');
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Adicionar Sistema ERP - TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="container" style="padding: 40px 0; max-width: 600px;">
        <h1>Adicionar Novo Sistema ERP</h1>
        <p><a href="/admin/erps">&larr; Voltar para a Lista</a></p>
        
        <form action="/admin/erps/criar" method="POST" class="form-panel" style="margin-top: 20px;">
            <div class="form-group">
                <label for="name">Nome do Sistema (Ex: Linx, Totvs, SAP)</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group" style="margin-top: 30px;">
                <button type="submit">Guardar Sistema</button>
            </div>
        </form>
    </div>
</body>
</html>
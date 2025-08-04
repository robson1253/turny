<?php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403); die('Acesso negado.');
}
if (!isset($company)) {
    die('Erro: Dados da empresa não foram carregados.');
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Empresa - <?= htmlspecialchars($company['nome_fantasia']) ?></title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="container" style="padding: 40px 0; max-width: 800px;">
        <h1>Editar Conta da Empresa</h1>
        <p><a href="/admin/empresas">&larr; Voltar para a Lista de Empresas</a></p>
        
        <form action="/admin/empresas/atualizar" method="POST" class="form-panel" style="margin-top: 20px;">
            
            <?php 
            // Exibe qualquer mensagem flash (de erro, por exemplo)
            display_flash_message(); 

            // Adiciona o campo CSRF oculto e obrigatório
            csrf_field(); 
            ?>
            
            <input type="hidden" name="id" value="<?= htmlspecialchars($company['id']) ?>">

            <fieldset>
                <legend>Dados da Empresa-Mãe</legend>
                <div class="form-group">
                    <label for="razao_social">Razão Social</label>
                    <input type="text" id="razao_social" name="razao_social" required value="<?= htmlspecialchars($company['razao_social']) ?>">
                </div>
                <div class="form-group">
                    <label for="nome_fantasia">Nome Fantasia</label>
                    <input type="text" id="nome_fantasia" name="nome_fantasia" required value="<?= htmlspecialchars($company['nome_fantasia']) ?>">
                </div>
                <div class="form-group">
                    <label for="telefone">Telefone Principal</label>
                    <input type="text" id="telefone" name="telefone" required value="<?= htmlspecialchars($company['telefone']) ?>">
                </div>
                <div class="form-group">
                    <label for="contact_email">E-mail de Contacto Principal</label>
                    <input type="email" id="contact_email" name="contact_email" required value="<?= htmlspecialchars($company['contact_email']) ?>">
                </div>
            </fieldset>

            <div class="form-group" style="margin-top: 30px;">
                <button type="submit">Atualizar Empresa</button>
            </div>
        </form>
    </div>
</body>
</html>
<?php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403); die('Acesso negado.');
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Adicionar Novo Operador - TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="container" style="padding: 40px 0; max-width: 800px;">
        <h1>Adicionar Novo Operador</h1>
        <p><a href="/admin/operadores">&larr; Voltar para a Lista de Operadores</a></p>
        
        <form action="/admin/operadores/criar" method="POST" style="margin-top: 20px;">
            
            <fieldset style="border: 1px solid #ccc; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
                <legend style="font-size: 1.2em; font-weight: bold;">Dados Pessoais e de Contacto</legend>
                <div class="form-group">
                    <label for="name">Nome Completo</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="cpf">CPF</label>
                    <input type="text" id="cpf" name="cpf" required>
                </div>
                <div class="form-group">
                    <label for="phone">Telefone</label>
                    <input type="text" id="phone" name="phone">
                </div>
            </fieldset>

            <fieldset style="border: 1px solid #ccc; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
                <legend style="font-size: 1.2em; font-weight: bold;">Endereço</legend>
                <div class="form-group"><label for="cep">CEP</label><input type="text" id="cep" name="cep"></div>
                <div class="form-group"><label for="endereco">Endereço</label><input type="text" id="endereco" name="endereco"></div>
                <div class="form-group"><label for="cidade">Cidade</label><input type="text" id="cidade" name="cidade"></div>
                <div class="form-group"><label for="estado">Estado (UF)</label><input type="text" id="estado" name="estado" maxlength="2"></div>
            </fieldset>

            <fieldset style="border: 1px solid #ccc; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
                <legend style="font-size: 1.2em; font-weight: bold;">Dados de Acesso (App do Operador)</legend>
                <div class="form-group">
                    <label for="email">E-mail (será o login)</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Senha</label>
                    <input type="password" id="password" name="password" required>
                </div>
            </fieldset>

            <div class="form-group" style="margin-top: 30px;">
                <button type="submit">Guardar Operador</button>
            </div>
        </form>
    </div>
	
	<script src="/js/cep.js"></script>
	<script src="/js/validators.js"></script>
</body>
</html>
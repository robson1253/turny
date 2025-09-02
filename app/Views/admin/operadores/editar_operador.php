<?php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403); die('Acesso negado.');
}
if (!isset($operator)) {
    die('Erro: Dados do operador não foram carregados.');
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Operador - <?= htmlspecialchars($operator['name']) ?></title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="container" style="padding: 40px 0; max-width: 800px;">
        <h1>Editar Operador</h1>
        <p><a href="/admin/operadores">&larr; Voltar para a Lista de Operadores</a></p>
        
        <form action="/admin/operadores/atualizar" method="POST" style="margin-top: 20px;">
		
		    <?php \csrf_field(); ?>
            
    <input type="hidden" name="id" value="<?= $operator['id'] ?>">
            
            <input type="hidden" name="id" value="<?= $operator['id'] ?>">

            <fieldset style="border: 1px solid #ccc; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
                <legend>Dados Pessoais e de Contacto</legend>
                <div class="form-group">
                    <label for="name">Nome Completo</label>
                    <input type="text" id="name" name="name" required value="<?= htmlspecialchars($operator['name']) ?>">
                </div>
                <div class="form-group">
                    <label for="cpf">CPF</label>
                    <input type="text" id="cpf" name="cpf" required value="<?= htmlspecialchars($operator['cpf']) ?>">
                </div>
                <div class="form-group">
                    <label for="phone">Telefone</label>
                    <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($operator['phone']) ?>">
                </div>
            </fieldset>

            <fieldset style="border: 1px solid #ccc; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
                <legend>Endereço</legend>
                <div class="form-group"><label for="cep">CEP</label><input type="text" id="cep" name="cep" value="<?= htmlspecialchars($operator['cep']) ?>"></div>
                <div class="form-group"><label for="endereco">Endereço</label><input type="text" id="endereco" name="endereco" value="<?= htmlspecialchars($operator['endereco']) ?>"></div>
                <div class="form-group"><label for="cidade">Cidade</label><input type="text" id="cidade" name="cidade" value="<?= htmlspecialchars($operator['cidade']) ?>"></div>
                <div class="form-group"><label for="estado">Estado (UF)</label><input type="text" id="estado" name="estado" maxlength="2" value="<?= htmlspecialchars($operator['estado']) ?>"></div>
            </fieldset>

            <fieldset style="border: 1px solid #ccc; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
                <legend>Dados de Acesso e Status</legend>
                <div class="form-group">
                    <label for="email">E-mail (login)</label>
                    <input type="email" id="email" name="email" required value="<?= htmlspecialchars($operator['email']) ?>">
                </div>
                <div class="form-group">
                    <label for="password">Nova Senha</label>
                    <input type="password" id="password" name="password">
                    <small>Deixe em branco para não alterar.</small>
                </div>
                 <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <option value="ativo" <?= $operator['status'] === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                        <option value="inativo" <?= $operator['status'] === 'inativo' ? 'selected' : '' ?>>Inativo</option>
                        <option value="em_treinamento" <?= $operator['status'] === 'em_treinamento' ? 'selected' : '' ?>>Em Treinamento</option>
                        <option value="bloqueado" <?= $operator['status'] === 'bloqueado' ? 'selected' : '' ?>>Bloqueado</option>
                    </select>
                </div>
            </fieldset>

            <div class="form-group" style="margin-top: 30px;">
                <button type="submit">Atualizar Operador</button>
            </div>
        </form>
        <script src="/js/cep.js"></script>
        <script src="/js/validators.js"></script>
    </div>
</body>
</html>
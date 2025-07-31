<?php
// Proteção básica da página
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403); die('Acesso negado.');
}
if (!isset($user)) {
    die('Erro: Dados do utilizador não foram carregados.');
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Utilizador - <?= htmlspecialchars($user['name']) ?></title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="container" style="padding: 40px 0; max-width: 600px;">
        <h1>Editar Utilizador</h1>
        <p><a href="javascript:history.back()">&larr; Voltar para a Lista de Utilizadores</a></p>
        
        <form action="/admin/utilizadores/atualizar" method="POST" style="margin-top: 20px;">
            
            <input type="hidden" name="id" value="<?= $user['id'] ?>">
			<input type="hidden" name="company_id" value="<?= $user['company_id'] ?>">
            <div class="form-group">
                <label for="name">Nome do Utilizador</label>
                <input type="text" id="name" name="name" required value="<?= htmlspecialchars($user['name']) ?>">
            </div>
            <div class="form-group">
                <label for="email">E-mail (Login)</label>
                <input type="email" id="email" name="email" required value="<?= htmlspecialchars($user['email']) ?>">
            </div>
            <div class="form-group">
                <label for="role">Perfil (Role)</label>
                <select id="role" name="role" required>
                    <option value="administrador" <?= $user['role'] === 'administrador' ? 'selected' : '' ?>>Administrador</option>
                    <option value="gerente" <?= $user['role'] === 'gerente' ? 'selected' : '' ?>>Gerente</option>
                    <option value="recepcionista" <?= $user['role'] === 'recepcionista' ? 'selected' : '' ?>>Recepcionista</option>
                </select>
            </div>
            <hr style="margin: 20px 0;">
            <div class="form-group">
                <label for="password">Nova Senha</label>
                <input type="password" id="password" name="password">
                <small>Deixe em branco para não alterar a senha atual.</small>
            </div>
            
            <div class="form-group" style="margin-top: 30px;">
                <button type="submit">Atualizar Utilizador</button>
            </div>
        </form>
    </div>
</body>
</html>
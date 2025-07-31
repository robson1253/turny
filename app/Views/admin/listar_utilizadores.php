<?php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403); die('Acesso negado.');
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Utilizadores da Empresa - TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th, .table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        .table th { background-color: #f2f2f2; }
        .table tr:nth-child(even){ background-color: #f9f9f9; }
        .actions a { display: inline-block; margin-right: 10px; font-weight: bold; margin-bottom: 5px; }
        .actions a.disable { color: #dc3545; }
        .actions a.enable { color: #28a745; }
    </style>
</head>
<body>
    <div class="container" style="padding: 40px 0; max-width: 1000px;">
        <h1>Utilizadores da Empresa: <?= htmlspecialchars($companyName ?? 'Empresa') ?></h1>
        <p><a href="/admin/empresas">&larr; Voltar para a Lista de Empresas</a></p>
        
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>E-mail (Login)</th>
                    <th>Perfil (Role)</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="6">Nenhum utilizador encontrado para esta empresa.</td></tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['id']) ?></td>
                            <td><?= htmlspecialchars($user['name']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars(ucfirst($user['role'])) ?></td>
                            <td>
                                <?php if ($user['status'] == 1): ?>
                                    <span style="color: green; font-weight: bold;">Ativo</span>
                                <?php else: ?>
                                    <span style="color: red; font-weight: bold;">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <a href="/admin/utilizadores/editar?id=<?= $user['id'] ?>">Editar</a>
                                <?php if ($user['status'] == 1): ?>
                                    <a href="/admin/utilizadores/toggle-status?id=<?= $user['id'] ?>" class="disable" onclick="return confirm('Tem a certeza de que quer desabilitar este utilizador?')">Desabilitar</a>
                                <?php else: ?>
                                    <a href="/admin/utilizadores/toggle-status?id=<?= $user['id'] ?>" class="enable" onclick="return confirm('Tem a certeza de que quer habilitar este utilizador?')">Habilitar</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
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
        .actions a, .actions button { display: inline-block; margin-right: 10px; font-weight: bold; margin-bottom: 5px; }
    </style>
</head>
<body>
    <div class="container" style="padding: 40px 0; max-width: 1000px;">
        <h1>Utilizadores da Empresa: <?= htmlspecialchars($companyName ?? 'Empresa') ?></h1>
        <p><a href="/admin/empresas">&larr; Voltar para a Lista de Empresas</a></p>
        
        <?php display_flash_message(); ?>
        
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
                                <span class="status-badge status-<?= $user['status'] ? 'ativo' : 'inativo' ?>">
                                    <?= $user['status'] ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>
                            <td class="actions">
                                <a href="/admin/utilizadores/editar?id=<?= $user['id'] ?>" class="btn edit-btn">Editar</a>
                                
                                <!-- Ação de toggle status agora é um formulário POST seguro -->
                                <form action="/admin/utilizadores/toggle-status" method="POST" style="display:inline;" onsubmit="return confirm('Tem a certeza?')">
                                    <?php csrf_field(); ?>
                                    <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                    <button type="submit" class="btn <?= $user['status'] ? 'cancel-btn' : 'success-btn' ?>">
                                        <?= $user['status'] ? 'Desabilitar' : 'Habilitar' ?>
                                    </button>
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
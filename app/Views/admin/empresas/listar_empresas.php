<?php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403); die('Acesso negado.');
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Listar Empresas - TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
    <style> /* Estilos da tabela e ações */
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
    <div class="container" style="padding-top: 40px; max-width: 1200px;">
        <h1>Empresas Registadas</h1>
        <p><a href="/dashboard">&larr; Voltar ao Dashboard</a> | <a href="/admin/empresas/criar">Adicionar Nova Empresa</a></p>
        
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Razão Social</th>
                    <th>Nome Fantasia</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($companies)): ?>
                    <tr><td colspan="5">Nenhuma empresa registada.</td></tr>
                <?php else: ?>
                    <?php foreach ($companies as $company): ?>
                        <tr>
                            <td><?= htmlspecialchars($company['id']) ?></td>
                            <td><?= htmlspecialchars($company['razao_social']) ?></td>
                            <td><?= htmlspecialchars($company['nome_fantasia']) ?></td>
                            <td>
                                <?php if ($company['status'] == 1): ?>
                                    <span style="color: green; font-weight: bold;">Ativo</span>
                                <?php else: ?>
                                    <span style="color: red; font-weight: bold;">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <a href="/admin/empresas/editar?id=<?= $company['id'] ?>">Editar Empresa</a>
                                <a href="/admin/utilizadores?company_id=<?= $company['id'] ?>">Ver Utilizadores</a>
                                <a href="/admin/stores?company_id=<?= $company['id'] ?>" style="color: #6f42c1;">Gerir Lojas</a> <?php if ($company['status'] == 1): ?>
                                    <a href="/admin/empresas/toggle-status?id=<?= $company['id'] ?>" class="disable" onclick="return confirm('Tem a certeza?')">Desabilitar</a>
                                <?php else: ?>
                                    <a href="/admin/empresas/toggle-status?id=<?= $company['id'] ?>" class="enable" onclick="return confirm('Tem a certeza?')">Habilitar</a>
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
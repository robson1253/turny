<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerir Empresas - Admin</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
.table { 
    width: 100%; 
    border-collapse: collapse; 
    margin-top: 20px; 
}

.table th, .table td { 
    border: 1px solid #ddd; 
    padding: 12px; 
    text-align: left; 
    vertical-align: middle; 
}

.table th { 
    background-color: #f2f2f2; 
}

.table tr:nth-child(even){ 
    background-color: #f9f9f9; 
}

.actions .btn { 
    display: inline-block; 
    margin-right: 5px; 
    margin-bottom: 5px; 
    text-decoration: none; 
}

.actions form { 
    margin-bottom: 5px; 
}

.actions .btn {
    display: inline-block;
    padding: 8px 10px;
    border: 1px solid transparent;
    border-radius: 5px;
    font-size: 14px;
    font-weight: bold;
    text-align: center;
    text-decoration: none;
    cursor: pointer;
    transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out, opacity 0.2s;
    line-height: 1.5; /* Garante alinhamento vertical consistente */
}

/* Estilo para o botão de ATIVAR (sucesso) */
.btn.success-btn {
    background-color: #28a745; /* Verde sucesso */
    color: #ffffff;
    border-color: #28a745;
}

.btn.success-btn:hover {
    background-color: #218838; /* Verde mais escuro no hover */
    border-color: #1e7e34;
}

/* Estilo para o botão de INATIVAR (perigo/cancelar) */
.btn.cancel-btn {
    background-color: #dc3545; /* Vermelho perigo */
    color: #ffffff;
    border-color: #dc3545;
}

.btn.cancel-btn:hover {
    background-color: #c82333; /* Vermelho mais escuro no hover */
    border-color: #bd2130;
}

    </style>
</head>
<body>
    <div class="container" style="padding-top: 40px; max-width: 1200px;">
        <h1>Empresas Registadas</h1>
        <p><a href="/dashboard">&larr; Voltar ao Dashboard</a> | <a href="/admin/empresas/criar">Adicionar Nova Empresa</a></p>
        
        <?php 
        // Exibe mensagens de sucesso ou erro vindas do controller
        display_flash_message(); 
        ?>
        
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
                                <span class="status-badge status-<?= $company['status'] ? 'ativo' : 'inativo' ?>">
                                    <?= $company['status'] ? 'Ativo' : 'Inativo' ?>
                                </span>
                            </td>
                            <td class="actions">
                                <a href="/admin/empresas/editar?id=<?= $company['id'] ?>" class="btn edit-btn">Editar</a>
                                <a href="/admin/utilizadores?company_id=<?= $company['id'] ?>" class="btn">Utilizadores</a>
                                <a href="/admin/stores?company_id=<?= $company['id'] ?>" class="btn" style="background-color: #6f42c1;">Gerir Lojas</a>
                                
                                <form action="/admin/empresas/toggle-status" method="POST" style="display:inline;" onsubmit="return confirm('Tem a certeza que deseja alterar o status?')">
                                    <?php csrf_field(); ?>
                                    <input type="hidden" name="id" value="<?= $company['id'] ?>">
                                    <button type="submit" class="btn <?= $company['status'] ? 'cancel-btn' : 'success-btn' ?>">
    <?= $company['status'] ? 'Inativar' : 'Ativar' ?>
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
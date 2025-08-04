<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerir Lojas - <?= htmlspecialchars($company['nome_fantasia']) ?></title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th, .table td { border: 1px solid #ddd; padding: 12px; text-align: left; vertical-align: middle; }
        .table th { background-color: #f2f2f2; }
        .table tr:nth-child(even){ background-color: #f9f9f9; }
        .actions .btn { display: inline-block; margin-right: 5px; margin-bottom: 5px; text-decoration: none; }
        .actions form { margin-bottom: 5px; }
    </style>
</head>
<body>
    <div class="container" style="padding: 40px 0; max-width: 1000px;">
        <h1>Lojas de: <span style="color: var(--cor-primaria);"><?= htmlspecialchars($company['nome_fantasia']) ?></span></h1>
        <p>
            <a href="/admin/empresas">&larr; Voltar para a Lista de Empresas</a> | 
            <a href="/admin/stores/criar?company_id=<?= htmlspecialchars($company['id']) ?>" class="btn-login" style="padding: 8px 15px; font-size: 14px;">Adicionar Nova Loja</a>
        </p>
        
        <?php display_flash_message(); ?>
        
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome da Loja</th>
                    <th>CNPJ</th>
                    <th>Sistema ERP</th>
                    <th>Status</th>
                    <th style="width: 220px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($stores)): ?>
                    <tr><td colspan="6" style="text-align: center;">Nenhuma loja registada para esta empresa.</td></tr>
                <?php else: ?>
                    <?php foreach ($stores as $store): ?>
                        <tr>
                            <td><?= htmlspecialchars($store['id']) ?></td>
                            <td><?= htmlspecialchars($store['name']) ?></td>
                            <td><?= htmlspecialchars($store['cnpj'] ?? 'N/D') ?></td>
                            <td><?= htmlspecialchars($store['erp_system_name'] ?? 'Não definido') ?></td>
                            <td>
                                <span class="status-badge status-<?= $store['status'] ? 'ativo' : 'inativo' ?>">
                                    <?= $store['status'] ? 'Ativa' : 'Inativa' ?>
                                </span>
                            </td>
                            <td class="actions">
                                <a href="/admin/stores/editar?id=<?= $store['id'] ?>" class="btn edit-btn">Editar</a>
                                <form action="/admin/stores/toggle-status" method="POST" style="display:inline;" onsubmit="return confirm('Tem a certeza que deseja alterar o status desta loja?')">
                                    <?php csrf_field(); ?>
                                    <input type="hidden" name="id" value="<?= $store['id'] ?>">
                                    <button type="submit" class="btn <?= $store['status'] ? 'cancel-btn' : 'success-btn' ?>">
                                        <?= $store['status'] ? 'Inativar' : 'Ativar' ?>
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
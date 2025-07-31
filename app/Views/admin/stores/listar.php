<?php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403); die('Acesso negado.');
}
if (!isset($company)) die('Erro: Dados da empresa não foram carregados.');
if (!isset($stores)) $stores = [];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerir Lojas - <?= htmlspecialchars($company['nome_fantasia']) ?></title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="container" style="padding: 40px 0; max-width: 1000px;">
        <h1>Lojas de: <span style="color: var(--cor-primaria);"><?= htmlspecialchars($company['nome_fantasia']) ?></span></h1>
        <p>
            <a href="/admin/empresas">&larr; Voltar para a Lista de Empresas</a> | 
            <a href="/admin/stores/criar?company_id=<?= htmlspecialchars($company['id']) ?>" class="btn-login" style="padding: 8px 15px; font-size: 14px;">Adicionar Nova Loja</a>
        </p>
        
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
                                <?php if ($store['status'] == 1): ?>
                                    <span class="status-badge" style="background-color: var(--cor-sucesso);">Ativa</span>
                                <?php else: ?>
                                    <span class="status-badge" style="background-color: var(--cor-perigo);">Inativa</span>
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <a href="/admin/stores/editar?id=<?= $store['id'] ?>">Editar</a>
                                <?php if ($store['status'] == 1): ?>
                                    <a href="/admin/stores/toggle-status?id=<?= $store['id'] ?>" class="disable" onclick="return confirm('Tem a certeza que quer desabilitar esta loja?')">Desabilitar</a>
                                <?php else: ?>
                                    <a href="/admin/stores/toggle-status?id=<?= $store['id'] ?>" class="enable" onclick="return confirm('Tem a certeza que quer habilitar esta loja?')">Habilitar</a>
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
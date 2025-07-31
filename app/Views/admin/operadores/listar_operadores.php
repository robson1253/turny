<?php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403); die('Acesso negado.');
}
if(!isset($operators)) {
    $operators = [];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Listar Operadores - TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        /* Estilos para as etiquetas de status (movidos para o style.css principal) */
        /* Mantemos aqui por referência, mas o ideal é que esteja no ficheiro central */
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            color: #fff;
            white-space: nowrap;
        }
        .status-ativo { background-color: var(--cor-sucesso); }
        .status-pendente_verificacao { background-color: #fd7e14; } /* Laranja */
        .status-documentos_aprovados { background-color: #0d6efd; } /* Azul */
        .status-inativo { background-color: #6c757d; } /* Cinza */
        .status-bloqueado, .status-rejeitado { background-color: var(--cor-perigo); }
    </style>
</head>
<body>
    <div class="container" style="padding: 40px 0; max-width: 1200px;">
        <h1>Operadores Registados</h1>
        <p><a href="/dashboard">&larr; Voltar ao Dashboard</a> | <a href="/admin/operadores/criar">Adicionar Novo Operador</a></p>
        
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>CPF</th>
                    <th>E-mail</th>
                    <th>Status</th>
                    <th>Pontuação</th>
                    <th style="width: 220px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($operators)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center;">Nenhum operador registado.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($operators as $operator): ?>
                        <tr>
                            <td><?= htmlspecialchars($operator['id']) ?></td>
                            <td><?= htmlspecialchars($operator['name']) ?></td>
                            <td><?= htmlspecialchars($operator['cpf']) ?></td>
                            <td><?= htmlspecialchars($operator['email']) ?></td>
                            <td>
                                <span class="status-badge status-<?= str_replace(' ', '_', htmlspecialchars($operator['status'])) ?>">
                                    <?= str_replace('_', ' ', htmlspecialchars($operator['status'])) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars(number_format($operator['pontuacao'], 2, ',', '.')) ?></td>
                            <td class="actions">
                                <?php if ($operator['status'] === 'pendente_verificacao'): ?>
                                    <a href="/admin/operadores/verificar?id=<?= $operator['id'] ?>" style="color: #fd7e14;">Analisar Registo</a>
                                <?php else: ?>
                                    <a href="/admin/operadores/editar?id=<?= $operator['id'] ?>">Editar</a>
                                    <?php if ($operator['status'] === 'ativo'): ?>
                                        <a href="/admin/operadores/toggle-status?id=<?= $operator['id'] ?>" class="disable" onclick="return confirm('Tem a certeza que quer desabilitar este operador?')">Desabilitar</a>
                                    <?php else: ?>
                                        <a href="/admin/operadores/toggle-status?id=<?= $operator['id'] ?>" class="enable" onclick="return confirm('Tem a certeza que quer habilitar este operador?')">Habilitar</a>
                                    <?php endif; ?>
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
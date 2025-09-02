<?php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403); die('Acesso negado.');
}
// Garante que a variável $operators exista para evitar erros na View
if (!isset($operators)) {
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
        /* Estilos para as etiquetas de status */
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
        .status-pendente_verificacao { background-color: #fd7e14; }
        .status-documentos_aprovados { background-color: #0d6efd; }
        .status-inativo { background-color: #6c757d; }
        .status-bloqueado, .status-rejeitado { background-color: var(--cor-perigo); }

        /* Estilo para o botão de formulário parecer um link (necessário para a correção CSRF) */
        .link-button { 
            background: none; 
            border: none; 
            padding: 0; 
            color: #0d6efd; 
            text-decoration: underline; 
            cursor: pointer; 
            font-size: inherit; 
            font-family: inherit; 
        }
        .link-button.disable { 
            color: var(--cor-perigo); 
        }
        .link-button:hover {
            text-decoration: none;
        }
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
                                <span class="status-badge status-<?= str_replace(' ', '_', htmlspecialchars($operator['status'] ?? '')) ?>">
                                    <?= str_replace('_', ' ', htmlspecialchars($operator['status'] ?? '')) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars(number_format($operator['pontuacao'] ?? 0, 2, ',', '.')) ?></td>
                            <td class="actions">
                                <?php if (($operator['status'] ?? '') === 'pendente_verificacao'): ?>
                                    <a href="/admin/operadores/verificar?id=<?= urlencode($operator['id']) ?>" style="color: #fd7e14;">Analisar Registo</a>
                                <?php else: ?>
                                    <a href="/admin/operadores/editar?id=<?= urlencode($operator['id']) ?>">Editar</a>
                                    
                                    <form action="/admin/operadores/toggle-status" method="POST" style="display: inline-block; margin-left: 10px;" onsubmit="return confirm('Tem a certeza?');">
                                        <?php csrf_field(); ?>
                                        <input type="hidden" name="id" value="<?= htmlspecialchars($operator['id']) ?>">
                                        
                                        <?php if (($operator['status'] ?? '') === 'ativo'): ?>
                                            <button type="submit" class="link-button disable">Desabilitar</button>
                                        <?php else: ?>
                                            <button type="submit" class="link-button">Habilitar</button>
                                        <?php endif; ?>
                                    </form>
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
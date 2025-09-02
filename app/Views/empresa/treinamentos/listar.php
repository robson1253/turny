<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Solicitações de Treinamento - TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        /* Estilos para fazer os botões parecerem links, mantendo a aparência original */
        .actions form {
            display: inline;
            margin-right: 10px;
        }
        .btn-link-action {
            background: none;
            border: none;
            padding: 0;
            margin: 0;
            font-family: inherit;
            font-size: 1em; /* Usa o tamanho de fonte da tabela */
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
        }
        .btn-link-action.enable {
            color: var(--cor-sucesso, #28a745);
        }
        .btn-link-action.disable {
            color: var(--cor-perigo, #dc3545);
        }
        .btn-link-action:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container" style="padding: 40px 0; max-width: 1200px;">
        <h1>Solicitações de Treinamento</h1>
        <p><a href="/painel/empresa">&larr; Voltar para o Painel</a></p>
        
        <?php display_flash_message(); ?>
        
        <table class="table">
            <thead>
                <tr>
                    <th>Operador</th>
                    <th>Contacto</th>
                    <th>Loja Escolhida</th>
                    <th>Data/Período Agendado</th>
                    <th>Status da Solicitação</th>
                    <th style="width: 220px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                    <tr><td colspan="6" style="text-align: center;">Nenhuma solicitação de treinamento encontrada.</td></tr>
                <?php else: ?>
                    <?php foreach ($requests as $request): ?>
                        <tr>
                            <td><?= htmlspecialchars($request['operator_name']) ?></td>
                            <td>
                                Email: <?= htmlspecialchars($request['operator_email']) ?><br>
                                Tel: <?= htmlspecialchars($request['operator_phone']) ?>
                            </td>
                            <td><?= htmlspecialchars($request['store_name']) ?></td>
                            <td>
                                <?php if($request['training_date']): ?>
                                    <?= htmlspecialchars(date('d/m/Y', strtotime($request['training_date']))) ?>
                                    <strong>(<?= htmlspecialchars(ucfirst($request['training_slot'])) ?>)</strong>
                                <?php else: ?>
                                    Ainda não agendado
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?= htmlspecialchars($request['training_status']) ?>" style="text-transform: capitalize;">
                                    <?= str_replace('_', ' ', htmlspecialchars($request['training_status'])) ?>
                                </span>
                            </td>
                            <td class="actions">
                                <?php if(in_array($request['training_status'], ['solicitado', 'agendado'])): ?>
                                    <!-- Formulário seguro para Aprovar -->
                                    <form action="/painel/treinamentos/processar" method="POST" onsubmit="return confirm('Confirma a APROVAÇÃO deste operador no treinamento?');">
                                        <?php csrf_field(); ?>
                                        <input type="hidden" name="id" value="<?= $request['training_id'] ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn-link-action enable">Aprovar</button>
                                    </form>
                                    <!-- Formulário seguro para Reprovar -->
                                    <form action="/painel/treinamentos/processar" method="POST" onsubmit="return confirm('Tem a certeza que quer REPROVAR este operador?');">
                                        <?php csrf_field(); ?>
                                        <input type="hidden" name="id" value="<?= $request['training_id'] ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn-link-action disable">Reprovar</button>
                                    </form>
                                <?php else: ?>
                                    <span style="color: #777;">Já Processado</span>
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
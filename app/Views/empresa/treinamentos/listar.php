<?php
if (!isset($_SESSION['user_id']) || empty($_SESSION['company_id'])) {
    header('Location: /login'); exit();
}
if (!isset($requests)) $requests = [];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Solicitações de Treinamento - TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="container" style="padding: 40px 0; max-width: 1200px;">
        <h1>Solicitações de Treinamento</h1>
        <p><a href="/painel/empresa">&larr; Voltar para o Painel</a></p>
        
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
                                    <a href="/painel/treinamentos/processar?id=<?= $request['training_id'] ?>&action=approve" class="enable" onclick="return confirm('Confirma a APROVAÇÃO deste operador no treinamento?');">Aprovar</a>
                                    <a href="/painel/treinamentos/processar?id=<?= $request['training_id'] ?>&action=reject" class="disable" onclick="return confirm('Tem a certeza que quer REPROVAR este operador?');">Reprovar</a>
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
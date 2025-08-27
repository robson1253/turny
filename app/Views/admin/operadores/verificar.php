<?php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403); die('Acesso negado.');
}
if (!isset($operator)) {
    die('Erro: Dados do operador não foram carregados.');
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Verificar Operador - <?= htmlspecialchars($operator['name'] ?? 'N/D') ?></title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .verification-container { display: flex; gap: 30px; flex-wrap: wrap; }
        .data-column { flex: 1; min-width: 300px; }
        .image-column { flex: 1; min-width: 300px; }
        .image-column img { width: 100%; border-radius: 8px; margin-bottom: 15px; border: 1px solid var(--cor-borda); }
        .data-item { margin-bottom: 15px; font-size: 1.1em; }
        .data-item label { font-weight: bold; color: var(--cor-destaque); display: block; font-size: 0.9em; text-transform: uppercase; }
    </style>
</head>
<body>
    <div class="container" style="padding: 40px 0; max-width: 1000px;">
        <h1>Análise de Registo de Operador</h1>
        <p><a href="/admin/operadores">&larr; Voltar para a Lista de Operadores</a></p>

        <div class="form-panel" style="margin-top: 20px;">
            <div class="verification-container">
                <div class="data-column">
                    <h3>Dados Fornecidos</h3>
                    <div class="data-item"><label>Nome:</label> <?= htmlspecialchars($operator['name'] ?? 'N/D') ?></div>
                    <div class="data-item"><label>CPF:</label> <?= htmlspecialchars($operator['cpf'] ?? 'N/D') ?></div>
                    <div class="data-item"><label>E-mail:</label> <?= htmlspecialchars($operator['email'] ?? 'N/D') ?></div>
                    <div class="data-item"><label>Telefone:</label> <?= htmlspecialchars($operator['phone'] ?? 'N/D') ?></div>
                    <hr>
                    <div class="data-item">
                        <label>Endereço:</label>
                        <?= htmlspecialchars($operator['endereco'] ?? 'N/D') ?>, <?= htmlspecialchars($operator['numero'] ?? 's/n') ?>,
                        <?= htmlspecialchars($operator['bairro'] ?? 'N/D') ?>, 
                        <?= htmlspecialchars($operator['cidade'] ?? 'N/D') ?> - 
                        <?= htmlspecialchars($operator['estado'] ?? 'N/D') ?>
                    </div>
                    <div class="data-item"><label>CEP:</label> <?= htmlspecialchars($operator['cep'] ?? 'N/D') ?></div>
                    <hr>
                    <div class="data-item"><label>Chave PIX:</label> (<?= htmlspecialchars($operator['pix_key_type'] ?? 'N/D') ?>) <?= htmlspecialchars($operator['pix_key'] ?? 'N/D') ?></div>
                </div>

                <div class="image-column">
                    <h3>Documentos Enviados</h3>
                    <div class="data-item">
                        <label>Selfie:</label>
                        <a href="<?= htmlspecialchars($operator['path_selfie'] ?? '#') ?>" target="_blank"><img src="<?= htmlspecialchars($operator['path_selfie'] ?? '/img/placeholder.png') ?>" alt="Selfie do Operador"></a>
                    </div>
                    <div class="data-item">
                        <label>Frente do Documento:</label>
                        <a href="<?= htmlspecialchars($operator['path_documento_frente'] ?? '#') ?>" target="_blank"><img src="<?= htmlspecialchars($operator['path_documento_frente'] ?? '/img/placeholder.png') ?>" alt="Frente do Documento"></a>
                    </div>
                    <?php if (!empty($operator['path_documento_verso'])): ?>
                        <div class="data-item">
                            <label>Verso do Documento:</label>
                            <a href="<?= htmlspecialchars($operator['path_documento_verso']) ?>" target="_blank"><img src="<?= htmlspecialchars($operator['path_documento_verso']) ?>" alt="Verso do Documento"></a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <hr style="margin: 30px 0;">

            <form action="/admin/operadores/processar-verificacao" method="POST">
                <?php csrf_field(); // <-- CORREÇÃO: TOKEN CSRF ADICIONADO ?>
                <input type="hidden" name="operator_id" value="<?= htmlspecialchars($operator['id'] ?? 0) ?>">
                
                <div class="form-group">
                    <label for="verification_notes">Notas Internas (visível apenas para si, opcional)</label>
                    <textarea id="verification_notes" name="verification_notes" rows="3"><?= htmlspecialchars($operator['verification_notes'] ?? '') ?></textarea>
                </div>
                <div class="form-navigation">
                    <button type="submit" name="action" value="reject" class="nav-btn" style="background-color: var(--cor-perigo); color: #fff;">Rejeitar Registo</button>
                    <button type="submit" name="action" value="approve" class="nav-btn btn-next">Aprovar Registo</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
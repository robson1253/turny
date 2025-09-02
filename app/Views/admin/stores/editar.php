<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Loja - TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="container" style="padding: 40px 0; max-width: 800px;">
        <h1>Editar Loja</h1>
        <p><a href="/admin/stores?company_id=<?= htmlspecialchars($store['company_id']) ?>">&larr; Voltar para a Lista de Lojas</a></p>
        
        <form action="/admin/stores/atualizar" method="POST" class="form-panel" style="margin-top: 20px;">
            
            <?php display_flash_message(); ?>
            <?php csrf_field(); ?>

            <input type="hidden" name="id" value="<?= $store['id'] ?>">
            <input type="hidden" name="company_id" value="<?= $store['company_id'] ?>">
            
            <fieldset>
                <legend>Dados da Loja</legend>
                <div class="form-group">
                    <label for="name">Nome da Loja</label>
                    <input type="text" id="name" name="name" required value="<?= htmlspecialchars($store['name']) ?>">
                </div>
                <div class="form-group">
                    <label for="erp_system_id">Sistema ERP utilizado</label>
                    <select id="erp_system_id" name="erp_system_id" required>
                        <option value="">-- Selecione um sistema --</option>
                        <?php foreach ($erpSystems as $system): ?>
                            <option value="<?= $system['id'] ?>" <?= ($store['erp_system_id'] == $system['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($system['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="cnpj">CNPJ da Loja</label>
                    <input type="text" id="cnpj" name="cnpj" required value="<?= htmlspecialchars($store['cnpj']) ?>">
                </div>
                <div class="form-group">
                    <label for="inscricao_estadual">Inscrição Estadual (IE)</label>
                    <input type="text" id="inscricao_estadual" name="inscricao_estadual" value="<?= htmlspecialchars($store['inscricao_estadual']) ?>">
                </div>
            </fieldset>

            <fieldset>
                <legend>Endereço da Loja</legend>
                <div class="form-group"><label for="cep">CEP</label><input type="text" id="cep" name="cep" required value="<?= htmlspecialchars($store['cep']) ?>"></div>
                <div class="form-group"><label for="endereco">Endereço</label><input type="text" id="endereco" name="endereco" required value="<?= htmlspecialchars($store['endereco']) ?>"></div>
                <div class="form-group"><label for="numero">Número</label><input type="text" id="numero" name="numero" required value="<?= htmlspecialchars($store['numero']) ?>"></div>
                <div class="form-group"><label for="bairro">Bairro</label><input type="text" id="bairro" name="bairro" required value="<?= htmlspecialchars($store['bairro']) ?>"></div>
                <div class="form-group"><label for="cidade">Cidade</label><input type="text" id="cidade" name="cidade" required value="<?= htmlspecialchars($store['cidade']) ?>"></div>
                <div class="form-group"><label for="estado">Estado (UF)</label><input type="text" id="estado" name="estado" maxlength="2" required value="<?= htmlspecialchars($store['estado']) ?>"></div>
            </fieldset>

            <div class="form-group" style="margin-top: 30px;">
                <button type="submit">Atualizar Loja</button>
            </div>
        </form>
    </div>

    <script src="/js/cep.js"></script>
    <script src="/js/validators.js"></script>
</body>
</html>
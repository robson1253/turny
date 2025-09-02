<?php
if (!isset($_SESSION['user_id']) || empty($_SESSION['company_id'])) {
    header('Location: /login'); exit();
}
if (!isset($storesWithShifts)) $storesWithShifts = [];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Selecionar Loja - Gestão de Vagas - TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="container" style="padding: 40px 20px;">
        <div class="wizard-header">
            <h1>Gestão de Vagas</h1>
            <div class="breadcrumb">Passo 1: Selecione a Loja</div>
        </div>
        
        <p style="margin-top: 30px;"><a href="/painel/empresa">&larr; Voltar para o Painel Principal</a></p>
        
        <div class="form-panel" style="background: none; border: none; box-shadow: none; padding: 0; margin-top: 20px;">
            <p style="font-size: 1.1em;">Escolha uma loja abaixo para gerir as vagas.</p>
            
            <div class="selection-grid">
                <?php if (empty($storesWithShifts)): ?>
                    <div class="info-box" style="grid-column: 1 / -1;">
                        <p>Nenhuma das suas lojas tem vagas publicadas no momento. <a href="/painel/empresa">Clique aqui</a> para voltar ao painel e publicar uma nova vaga.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($storesWithShifts as $store):
                        // --- LÓGICA DE ATALHO PARA O FRENTE DE LOJA ---
                        $link = '';
                        if ($_SESSION['user_role'] === 'recepcionista') {
                            // Se for recepcionista, o link vai direto para as vagas do dia atual.
                            $today = date('Y-m-d');
                            $link = "/painel/vagas/dia?store_id={$store['id']}&date={$today}";
                        } else {
                            // Se for gerente, o link vai para a seleção de dia.
                            $link = "/painel/vagas/dias?store_id={$store['id']}";
                        }
                    ?>
                        <a href="<?= $link ?>" class="selection-card">
                            <div class="icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 3L2 12h3v8h14v-8h3L12 3zm0 2.83L15.17 9H8.83L12 5.83zM7 18v-6h10v6H7z"/></svg>
                            </div>
                            <div class="text"><?= htmlspecialchars($store['name']) ?></div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
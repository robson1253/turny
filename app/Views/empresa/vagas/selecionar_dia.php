<?php
if (!isset($_SESSION['user_id']) || empty($_SESSION['company_id'])) {
    header('Location: /login'); exit();
}
// Garante que as variáveis essenciais existem. $dates pode ser um array vazio.
if (!isset($store)) {
    die('Erro: Dados essenciais da página (loja) não foram carregados. Contacte o suporte.');
}
if (!isset($dates)) {
    $dates = [];
}
setlocale(LC_TIME, 'pt_BR.utf-8', 'pt_BR', 'Portuguese_Brazil');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Selecionar Dia - <?= htmlspecialchars($store['name']) ?></title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="container" style="padding: 40px 20px;">
        <div class="wizard-header">
            <h1>Gestão de Vagas</h1>
            <div class="breadcrumb">
                <a href="/painel/vagas">Seleção de Loja</a>
                <span>&gt;</span>
                <?= htmlspecialchars($store['name']) ?>
            </div>
        </div>
        
        <p style="margin-top: 30px;"><a href="/painel/empresa">&larr; Voltar para o Painel Principal</a> | <a href="/painel/vagas">Trocar de Loja</a></p>

        <div class="form-panel" style="background: none; border: none; box-shadow: none; padding: 0; margin-top: 20px;">
             <h3 style="margin-top:0; color: var(--cor-destaque);">Passo 2: Selecione o Dia</h3>
            <p>Escolha um dia para ver as vagas disponíveis.</p>
            
            <div class="selection-grid">
                <?php if (empty($dates)): ?>
                    <div class="info-box" style="grid-column: 1 / -1;"><p>Nenhuma vaga aberta encontrada para esta loja.</p></div>
                <?php else: ?>
                    <?php foreach ($dates as $date): 
                        $timezone = new DateTimeZone('America/Sao_Paulo');
                        $dateObj = new DateTime($date, $timezone);
                        $formatter = new IntlDateFormatter('pt_BR', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'America/Sao_Paulo', null, 'EEEE, d \'de\' MMMM \'de\' yyyy');
                    ?>
                        <a href="/painel/vagas/dia?store_id=<?= $store['id'] ?>&date=<?= $date ?>" class="selection-card">
                             <div class="icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19,19H5V8H19M16,1V3H8V1H6V3H5C3.89,3 3,3.89 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5C21,3.89 20.1,3 19,3H18V1M17,12H12V17H17V12Z" /></svg></div>
                            <div class="text" style="font-size: 1.1em;"><?= htmlspecialchars(ucfirst($formatter->format($dateObj))) ?></div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
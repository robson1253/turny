<?php
// Pega os dados do operador da sessão para o cabeçalho
$operatorName = $_SESSION['user_name'] ?? 'Operador';
$operatorThumb = $_SESSION['operator_thumb'] ?? '/images/default-avatar.png';
?>
<header class="operador-header">
    <div class="logo">Turn<span>y</span></div>
    <div class="header-user-profile">
        <span><?= htmlspecialchars($operatorName) ?></span>
        <img src="<?= htmlspecialchars($operatorThumb) ?>" alt="Foto do Operador">
        <a href="/logout" style="margin-left: 10px;">Sair</a>
    </div>
</header>
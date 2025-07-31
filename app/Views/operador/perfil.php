<?php
if (!isset($_SESSION['operator_id'])) {
    header('Location: /login'); exit();
}
if (!isset($operator)) die('Erro: Dados do operador não carregados.');
if (!isset($qualifications)) $qualifications = [];
if (!isset($pendingOffers)) $pendingOffers = 0;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .profile-header { text-align: center; margin-bottom: 30px; }
        .profile-avatar { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid var(--cor-branco); box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .profile-name { font-size: 1.8em; font-weight: bold; margin: 10px 0 5px 0; color: var(--cor-destaque); }
        .profile-section { margin-bottom: 30px; }
        .profile-section h3 { border-bottom: 2px solid var(--cor-primaria); padding-bottom: 10px; margin-bottom: 15px; font-size: 1.2em; }
        .data-item { margin-bottom: 15px; font-size: 1.1em; line-height: 1.4; }
        .data-item label { font-weight: bold; color: var(--cor-texto); display: block; font-size: 0.9em; opacity: 0.7; text-transform: uppercase; margin-bottom: 2px;}
        .qualifications-grid { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
        .notification-badge { position: absolute; top: 0; right: 5px; background-color: var(--cor-perigo); color: var(--cor-branco); border-radius: 50%; height: 20px; width: 20px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold; }
        
        /* Estilo atualizado do emblema */
        .qualification-badge { 
            background-color: #e9f2f9;
            color: var(--cor-primaria);
            padding: 6px 15px; 
            border-radius: 20px; 
            font-size: 14px; 
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid #cce0f1;
        }
        .qualification-badge .icon {
            width: 16px;
            height: 16px;
            fill: var(--cor-primaria);
        }
    </style>
</head>
<body class="operador-body">
    <div class="operador-container">
        <header class="operador-header">
            <div class="logo">Turn<span>y</span></div>
            <div class="user-info"><a href="/logout">Sair</a></div>
        </header>

        <main class="operador-content">
            <div class="profile-header">
                <img src="<?= htmlspecialchars($operator['path_selfie']) ?>" alt="Foto de Perfil" class="profile-avatar">
                <h2 class="profile-name"><?= htmlspecialchars($operator['name']) ?></h2>
                <p>@<?= htmlspecialchars($operator['username']) ?></p>
                <p style="margin-top: -10px;">Pontuação: <strong><?= htmlspecialchars(number_format($operator['pontuacao'], 2)) ?> / 5.00</strong></p>
            </div>

            <div class="profile-section">
                <h3>Meus Emblemas (Sistemas Qualificados)</h3>
               <center> <div class="qualifications-grid">
                    <?php if (empty($qualifications)): ?>
                        <p>Você ainda não possui nenhuma qualificação. <a href="/painel/operador/qualificacoes">Clique aqui</a> para solicitar.</p>
                    <?php else: ?>
                        <?php foreach ($qualifications as $qual): ?>
                            <span class="qualification-badge">
                                <span class="icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M11,16.5L6.5,12L7.91,10.59L11,13.67L16.59,8.09L18,9.5L11,16.5Z"></path></svg>
                                </span>
                                <?= htmlspecialchars($qual) ?>
                            </span>
                        <?php endforeach; ?>
                        <br><br>
                        
                    <?php endif; ?>
                </div>
				<p><a href="/painel/operador/qualificacoes">Gerir ou solicitar novas qualificações.</a></p>
            </div></center>
            
            <div class="profile-section">
                <h3>Meus Dados</h3>
                <div class="data-item"><label>E-mail:</label> <?= htmlspecialchars($operator['email']) ?></div>
                <div class="data-item"><label>Telefone:</label> <?= htmlspecialchars($operator['phone']) ?></div>
                <div class="data-item"><label>CPF:</label> <?= htmlspecialchars($operator['cpf']) ?></div>
                <div class="data-item"><label>Endereço:</label> <?= htmlspecialchars($operator['endereco'] ?? '') ?>, <?= htmlspecialchars($operator['numero'] ?? 's/n') ?> - <?= htmlspecialchars($operator['bairro'] ?? '') ?>, <?= htmlspecialchars($operator['cidade'] ?? '') ?>/<?= htmlspecialchars($operator['estado'] ?? '') ?></div>
                <div class="data-item"><label>PIX (<?= htmlspecialchars($operator['pix_key_type']) ?>):</label> <?= htmlspecialchars($operator['pix_key']) ?></div>
            </div>
        </main>

        <footer class="operador-footer">
            <a href="/painel/operador" class="footer-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M10,20V14H14V20H19V12H22L12,3L2,12H5V20H10Z" /></svg>
                Vagas
            </a>
            <a href="/painel/operador/meus-turnos" class="footer-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19,19H5V8H19M16,1V3H8V1H6V3H5C3.89,3 3,3.89 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5C21,3.89 20.1,3 19,3H18V1M17,12H12V17H17V12Z" /></svg>
                Meus Turnos
            </a>
            <a href="/painel/operador/ofertas" class="footer-icon" style="position: relative;">
                <?php if (isset($pendingOffers) && $pendingOffers > 0): ?>
                    <span class="notification-badge"><?= $pendingOffers ?></span>
                <?php endif; ?>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M22 17H20V12H4V17H2V12C2 10.9 2.9 10 4 10H20C21.1 10 22 10.9 22 12V17M15.5 2H8.5L7.3 5H16.7L15.5 2M18 5H6L5 7V9H19V7L18 5M12 13C13.1 13 14 13.9 14 15S13.1 17 12 17 10 16.1 10 15 10.9 13 12 13Z" /></svg>
                Ofertas
            </a>
            <a href="/painel/operador/perfil" class="footer-icon active">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z" /></svg>
                Perfil
            </a>
        </footer>
    </div>
	

	
</body>
</html>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
	    <style>
        .profile-header { text-align: center; margin-bottom: 30px; }
        .profile-avatar { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid var(--cor-branco); box-shadow: 0 4px 10px rgba(0,0,0,0.1); cursor: pointer; transition: transform 0.2s; }
        .profile-avatar:hover { transform: scale(1.05); }
        .profile-name { font-size: 1.8em; font-weight: bold; margin: 10px 0 5px 0; color: var(--cor-destaque); }
        .profile-section { margin-bottom: 30px; }
        .profile-section h3 { border-bottom: 2px solid var(--cor-primaria); padding-bottom: 10px; margin-bottom: 15px; font-size: 1.2em; }
        .data-item { margin-bottom: 15px; font-size: 1.1em; line-height: 1.4; }
        .data-item label { font-weight: bold; color: var(--cor-texto); display: block; font-size: 0.9em; opacity: 0.7; text-transform: uppercase; margin-bottom: 2px;}
        .qualifications-grid { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: center; }
        .qualification-badge { background-color: #e9f2f9; color: var(--cor-primaria); padding: 6px 15px; border-radius: 20px; font-size: 14px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; border: 1px solid #cce0f1; }
        .qualification-badge .icon { width: 16px; height: 16px; fill: var(--cor-primaria); }

        /* --- INÍCIO DA CORREÇÃO DEFINITIVA DO MODAL --- */
.modal-container {
    display: none; /* mantido */
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.85);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    padding: 0;
}
        .modal-dialog {
            background: #fff;
            padding: 15px;
            border-radius: 10px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
            animation: fadeInModal 0.3s ease-out;
            position: relative;
        }
        @keyframes fadeInModal {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        .modal-close-btn {
            position: absolute;
            top: 5px;
            right: 15px;
            background: transparent;
            border: none;
            font-size: 35px;
            cursor: pointer;
            color: #aaa;
            line-height: 1;
        }
        .modal-close-btn:hover { color: #333; }

        #image-modal-dialog img {
            width: 100%;
            height: auto;
            border-radius: 8px;
            display: block;
        }
        #image-modal-dialog #legendaDoModal {
            text-align: center;
            color: #666;
            padding-top: 10px;
            font-weight: bold;
        }
        /* --- FIM DA CORREÇÃO DEFINITIVA DO MODAL --- */
    </style>
</head>
<body class="operador-body">
    <div class="operador-container">
        <header class="operador-header">
            <div class="logo">Turn<span>y</span></div>
            <div class="user-info"><a href="/logout">Sair</a></div>
        </header>

        <main class="operador-content">
            <?php display_flash_message(); ?>

            <div class="profile-header">
                <img src="<?= htmlspecialchars($operator['path_selfie_thumb'] ?? $operator['path_selfie']) ?>" 
                     alt="Foto de Perfil de <?= htmlspecialchars($operator['name']) ?>" 
                     class="profile-avatar imagem-clicavel"
                     data-full-image="<?= htmlspecialchars($operator['path_selfie']) ?>">
                
                <h2 class="profile-name"><?= htmlspecialchars($operator['name']) ?></h2>
                <p>@<?= htmlspecialchars($operator['username']) ?></p>
                <p style="margin-top: -10px;">Pontuação: <strong><?= htmlspecialchars(number_format($operator['pontuacao'], 2)) ?> / 5.00</strong></p>
            </div>

            <div class="profile-section">
                <h3>Minhas Qualificações</h3>
                


                <!-- Secção para Sistemas ERP -->
                <h4 style="margin-top: 20px; font-size: 1em; color: #555;">Sistemas (ERP)</h4>
                <div class="qualifications-grid">
                    <?php if (empty($erpQualifications)): ?>
                        <p>Nenhuma qualificação de sistema. <a href="/painel/operador/qualificacoes">Solicite uma aqui</a>.</p>
                    <?php else: ?>
                        <?php foreach ($erpQualifications as $qual): ?>
                            <span class="qualification-badge">
                                <span class="icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M11,16.5L6.5,12L7.91,10.59L11,13.67L16.59,8.09L18,9.5L11,16.5Z"></path></svg></span>
                                <?= htmlspecialchars($qual) ?>
                            </span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <p style="text-align: center; margin-top: 15px;"><a href="/painel/operador/qualificacoes">Gerir ou solicitar novas qualificações.</a></p>
            </div>
            
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
                     <span class="notification-badge"><?= htmlspecialchars($pendingOffers) ?></span>
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

    <!-- Estrutura do Modal de Imagem -->
    <div id="image-modal-container" class="modal-container">
        <div class="modal-dialog" id="image-modal-dialog">
            <button class="modal-close-btn" id="close-image-modal">&times;</button>
            <img src="" id="imagemDoModal" alt="Foto do Operador">
            <div id="legendaDoModal"></div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const imageModalContainer = document.getElementById("image-modal-container");
        if (imageModalContainer) {
            const modalImg = document.getElementById("imagemDoModal");
            const legenda = document.getElementById("legendaDoModal");
            const imagensClicaveis = document.querySelectorAll('.imagem-clicavel');
            const fecharImg = document.getElementById("close-image-modal");

            imagensClicaveis.forEach(img => {
                img.addEventListener('click', function() {
                    const fullImageSrc = this.dataset.fullImage || this.src;
                    imageModalContainer.style.display = "flex"; // Usa flex para mostrar e centralizar
                    modalImg.src = fullImageSrc;
                    legenda.textContent = this.alt || '';
                });
            });

            function closeImageModal() {
                imageModalContainer.style.display = "none";
            }

            fecharImg.addEventListener('click', closeImageModal);
            imageModalContainer.addEventListener('click', (e) => {
                if (e.target === imageModalContainer) {
                    closeImageModal();
                }
            });
            document.addEventListener('keydown', (e) => { 
                if (e.key === "Escape" && imageModalContainer.style.display === 'flex') {
                    closeImageModal();
                }
            });
        }
    });
    </script>
</body>
</html>

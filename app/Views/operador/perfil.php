<?php
// Preparação segura e completa das variáveis vindas do controlador
$operator = $operator ?? [];
$erpQualifications = $erpQualifications ?? [];
$pendingOffers = $pendingOffers ?? 0;

// Extrai e sanitiza todas as variáveis no início para um código mais limpo
$name = htmlspecialchars($operator['name'] ?? 'Operador');
$username = htmlspecialchars($operator['username'] ?? 'N/A');
$score = htmlspecialchars(number_format($operator['pontuacao'] ?? 0, 2));
$balance = htmlspecialchars(number_format($operator['balance'] ?? 0, 2, ',', '.'));

$defaultAvatar = '/images/default-avatar.png';
$selfieUrl = htmlspecialchars($operator['path_selfie'] ?? $defaultAvatar);
$thumbUrl = htmlspecialchars($operator['path_selfie_thumb'] ?? $selfieUrl);

$email = htmlspecialchars($operator['email'] ?? 'Não informado');
$phone = htmlspecialchars($operator['phone'] ?? 'Não informado');
$cpf = htmlspecialchars($operator['cpf'] ?? 'Não informado');
$pixKeyType = htmlspecialchars($operator['pix_key_type'] ?? 'PIX');
$pixKey = htmlspecialchars($operator['pix_key'] ?? 'Não informado');

$addressParts = [
    htmlspecialchars($operator['endereco'] ?? ''),
    htmlspecialchars($operator['numero'] ?? 's/n'),
    htmlspecialchars($operator['bairro'] ?? ''),
    htmlspecialchars($operator['cidade'] ?? ''),
    htmlspecialchars($operator['estado'] ?? '')
];
$fullAddress = implode(' - ', array_filter($addressParts));
$fullAddress = empty(trim($fullAddress, ' -')) ? 'Endereço não informado' : $fullAddress;
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

        /* Estilos do cabeçalho e saldo */
        .operador-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 10px 15px; 
            gap: 15px;
        }
        .saldo-mini { 
            background: #f8f9fa; 
            border: 1px solid #e0e0e0; 
            padding: 6px 12px; 
            border-radius: 8px; 
            font-size: 0.95em; 
            color: var(--cor-sucesso); 
            font-weight: bold; 
            text-align: right;
            min-width: 90px;
        }
        .saldo-mini label { 
            font-size: 0.75em; 
            color: #666; 
            display: block; 
            font-weight: normal; 
            margin-bottom: 2px; 
        }

        /* Modal de imagem centralizado */
        .modal-container {
            display: none; 
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.7);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-dialog {
            position: relative;
            background: #fff;
            padding: 15px;
            border-radius: 10px;
            max-width: 90%;
            max-height: 90%;
            display: flex;
            flex-direction: column;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.4);
        }
        .modal-dialog img {
            max-width: 100%;
            max-height: 80vh;
            border-radius: 8px;
        }
        .modal-close-btn {
            position: absolute;
            top: -10px;
            right: -10px;
            background: #ff4444;
            border: none;
            color: #fff;
            font-size: 1.4em;
            font-weight: bold;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
            transition: background 0.2s;
        }
        .modal-close-btn:hover {
            background: #cc0000;
        }
        #legendaDoModal {
            margin-top: 10px;
            font-size: 0.95em;
            color: #444;
            text-align: center;
        }
    </style>
</head>
<body class="operador-body">

    <div class="operador-container">
        <header class="operador-header">
            <div class="logo">Turn<span>y</span></div>

            <div class="saldo-mini">
                <label><center>Saldo</center></label>
                R$ <?= $balance ?>
            </div>

            <div class="user-info"><a href="/logout">Sair</a></div>
        </header>

        <main class="operador-content">
            <?php display_flash_message(); ?>

            <?php if (!empty($operator)): ?>
                <div class="profile-header">
                    <img src="<?= $thumbUrl ?>" alt="Foto de Perfil de <?= $name ?>" class="profile-avatar imagem-clicavel" data-full-image="<?= $selfieUrl ?>">
                    <h2 class="profile-name"><?= $name ?></h2>
                    <p>@<?= $username ?></p>
                    <p style="margin-top: -10px;">Pontuação: <strong><?= $score ?> / 5.00</strong></p>
                </div>

                <div class="profile-section">
                    <h3>Minhas Qualificações</h3>
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
                    <div class="data-item"><label>E-mail:</label> <?= $email ?></div>
                    <div class="data-item"><label>Telefone:</label> <?= $phone ?></div>
                    <div class="data-item"><label>CPF:</label> <?= $cpf ?></div>
                    <div class="data-item"><label>Endereço:</label> <?= $fullAddress ?></div>
                    <div class="data-item"><label>PIX (<?= $pixKeyType ?>):</label> <?= $pixKey ?></div>
                </div>
            <?php else: ?>
                <div class="info-box error" style="text-align: center;">
                    <p><strong>Erro ao carregar o perfil.</strong></p>
                    <p>Não foi possível encontrar os dados do operador. Por favor, tente novamente mais tarde.</p>
                </div>
            <?php endif; ?>
        </main>

				<?php require_once __DIR__ . '/../partials/operador_footer.php'; ?>
    </div>

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
                    imageModalContainer.style.display = "flex";
                    modalImg.src = fullImageSrc;
                    legenda.textContent = this.alt || '';
                });
            });

            function closeImageModal() {
                imageModalContainer.style.display = "none";
            }

            if (fecharImg) {
                fecharImg.addEventListener('click', closeImageModal);
            }
            
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

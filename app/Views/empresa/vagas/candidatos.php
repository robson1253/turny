<?php
if (!isset($_SESSION['user_id']) || empty($_SESSION['company_id'])) {
    header('Location: /login'); exit();
}
if (!isset($shift) || !isset($applicantsByStatus)) {
    die('Erro: Dados não carregados.');
}

$completionSuccess = isset($_GET['status']) && $_GET['status'] === 'completion_success';

// Formata a data da vaga para um formato amigável
setlocale(LC_TIME, 'pt_BR.utf-8', 'pt_BR', 'Portuguese_Brazil');
$timezone = new DateTimeZone('America/Sao_Paulo');
$dateObj = new DateTime($shift['shift_date'], $timezone);
$formatter = new IntlDateFormatter('pt_BR', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'America/Sao_Paulo', null, 'd \'de\' MMMM \'de\' yyyy');
$formattedDate = $formatter->format($dateObj);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Operadores da Vaga - TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .applicant-card { 
            display: flex; 
            align-items: center; 
            gap: 20px; 
            background: #fff; 
            padding: 15px; 
            border-radius: 8px; 
            margin-bottom: 15px; 
            border: 1px solid var(--cor-borda); 
        }
        .applicant-avatar img { 
            width: 80px; 
            height: 80px; 
            border-radius: 50%; 
            object-fit: cover; 
            border: 2px solid var(--cor-borda); 
            transition: transform 0.2s; 
            cursor: pointer; 
        }
        .applicant-avatar a:hover img { 
            transform: scale(1.1); 
        }
        .applicant-info { 
            flex-grow: 1; 
        }
        .applicant-info h4 { 
            margin: 0 0 5px 0; 
        }
        .applicant-actions {
            display: flex;
            gap: 10px;
        }
        .applicant-actions .btn { 
            padding: 8px 15px; 
            font-size: 14px;
        }
        .status-group h3 { 
            border-bottom: 2px solid var(--cor-borda); 
            padding-bottom: 10px; 
            margin-top: 40px; 
            color: var(--cor-destaque); 
        }
        
        /* CSS PARA AS ESTRELAS */
        .star-rating {
            display: flex;
            flex-direction: row-reverse; /* Inverte a ordem para a mágica do CSS funcionar */
            justify-content: center; /* Centraliza as estrelas */
            gap: 5px;
            margin-top: 10px;
        }
        .star-rating input[type="radio"] {
            display: none; /* Esconde os botões de rádio originais */
        }
        .star-rating label {
            font-size: 2.5em; /* Tamanho das estrelas */
            color: #ddd; /* Cor da estrela vazia */
            cursor: pointer;
            transition: color 0.2s;
        }
        /* Pinta as estrelas ao passar o rato por cima e na seleção */
        .star-rating:not(:hover) input[type="radio"]:checked ~ label,
        .star-rating:hover input[type="radio"] ~ label:hover ~ label,
        .star-rating:hover input[type="radio"] ~ label:hover {
            color: #ffc107; /* Cor da estrela cheia (amarelo) */
        }
    </style>
</head>
<body>
    <div class="container" style="padding: 40px 0; max-width: 900px;">
        <h1>Operadores para a Vaga</h1>
        <p><a href="/painel/vagas/dia?store_id=<?= $shift['store_id'] ?>&date=<?= $shift['shift_date'] ?>">&larr; Voltar para as vagas do dia</a></p>

        <?php if ($completionSuccess): ?>
            <div class="success-message">Turno finalizado e avaliado com sucesso!</div>
        <?php endif; ?>

        <div class="info-box">
            <p><strong>Vaga:</strong> <?= htmlspecialchars($shift['title']) ?> em <strong><?= htmlspecialchars($formattedDate) ?></strong> das <?= htmlspecialchars(substr($shift['start_time'], 0, 5)) ?> às <?= htmlspecialchars(substr($shift['end_time'], 0, 5)) ?></p>
        </div>

        <?php if (!empty($applicantsByStatus['em_turno'])): ?>
        <div class="status-group">
            <h3>Em Turno Agora</h3>
            <?php foreach ($applicantsByStatus['em_turno'] as $applicant): ?>
                <div class="applicant-card">
                    <div class="applicant-avatar"><a href="<?= htmlspecialchars($applicant['path_selfie']) ?>" class="open-image-modal"><img src="<?= htmlspecialchars($applicant['path_selfie']) ?>" alt="Foto"></a></div>
                    <div class="applicant-info">
                        <h4><?= htmlspecialchars($applicant['name']) ?></h4>
                        <p style="margin: 0;">Status: <span class="status-badge status-check_in">Check-in</span></p>
                    </div>
                    <div class="applicant-actions">
                        <button type="button" class="btn open-completion-modal" data-application-id="<?= $applicant['application_id'] ?>" style="background-color: var(--cor-destaque);">Concluir e Avaliar</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($applicantsByStatus['confirmados'])): ?>
        <div class="status-group">
            <h3>Confirmados (Aguardando Check-in)</h3>
            <?php foreach ($applicantsByStatus['confirmados'] as $applicant): ?>
                <div class="applicant-card">
                    <div class="applicant-avatar"><a href="<?= htmlspecialchars($applicant['path_selfie']) ?>" class="open-image-modal"><img src="<?= htmlspecialchars($applicant['path_selfie']) ?>" alt="Foto"></a></div>
                    <div class="applicant-info">
                        <h4><?= htmlspecialchars($applicant['name']) ?></h4>
                        <p style="margin: 0;">Status: <span class="status-badge status-aprovado">Aprovado</span></p>
                    </div>
                    <div class="applicant-actions">
                        <a href="/painel/vagas/candidatos/status?id=<?= $applicant['application_id'] ?>&action=no_show" class="btn disable" onclick="return confirm('Tem a certeza que deseja marcar FALTA para este operador?');">Marcar Falta</a>
                        <a href="/painel/vagas/candidatos/status?id=<?= $applicant['application_id'] ?>&action=check_in" class="btn enable" onclick="return confirm('Confirmar a chegada e o início do turno para este operador?');">Check-in</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($applicantsByStatus['concluidos']) || !empty($applicantsByStatus['faltas']) || !empty($applicantsByStatus['cancelados'])): ?>
        <div class="status-group">
            <h3>Histórico do Turno</h3>
            <?php foreach ($applicantsByStatus['concluidos'] as $applicant): ?>
                 <div class="applicant-card">
                    <div class="applicant-avatar"><a href="<?= htmlspecialchars($applicant['path_selfie']) ?>" class="open-image-modal"><img src="<?= htmlspecialchars($applicant['path_selfie']) ?>" alt="Foto"></a></div>
                    <div class="applicant-info">
                        <h4><?= htmlspecialchars($applicant['name']) ?></h4>
                        <p style="margin: 0;">Status: <span class="status-badge status-concluido">Concluído</span></p>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php foreach ($applicantsByStatus['faltas'] as $applicant): ?>
                 <div class="applicant-card" style="opacity: 0.7;">
                    <div class="applicant-avatar"><a href="<?= htmlspecialchars($applicant['path_selfie']) ?>" class="open-image-modal"><img src="<?= htmlspecialchars($applicant['path_selfie']) ?>" alt="Foto"></a></div>
                    <div class="applicant-info">
                        <h4><?= htmlspecialchars($applicant['name']) ?></h4>
                        <p style="margin: 0;">Status: <span class="status-badge status-no_show">Falta</span></p>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php foreach ($applicantsByStatus['cancelados'] as $applicant): ?>
                 <div class="applicant-card" style="opacity: 0.7;">
                    <div class="applicant-avatar"><a href="<?= htmlspecialchars($applicant['path_selfie']) ?>" class="open-image-modal"><img src="<?= htmlspecialchars($applicant['path_selfie']) ?>" alt="Foto"></a></div>
                    <div class="applicant-info">
                        <h4><?= htmlspecialchars($applicant['name']) ?></h4>
                        <p style="margin: 0;">Status: <span class="status-badge status-cancelado_operador">Cancelado Pelo Operador</span></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (empty(array_merge($applicantsByStatus['em_turno'], $applicantsByStatus['confirmados'], $applicantsByStatus['concluidos'], $applicantsByStatus['faltas'], $applicantsByStatus['cancelados']))): ?>
            <p>Nenhum operador associado a esta vaga.</p>
        <?php endif; ?>
    </div>
    
    <div class="modal-overlay" id="image-viewer-overlay"></div>
    <div class="modal-content" id="image-viewer-content">
        <button class="modal-close-btn" id="close-image-viewer">&times;</button>
        <img src="" id="modal-image" style="width: 100%; height: auto; display: block; border-radius: 8px;">
    </div>

    <div class="modal-overlay" id="completion-modal-overlay"></div>
    <div class="modal-content" id="completion-modal-content">
        <div class="modal-header">
            <h2>Finalizar e Avaliar Turno</h2>
            <button class="modal-close-btn" id="close-completion-modal">&times;</button>
        </div>
        <div class="modal-body">
            <form action="/painel/vagas/concluir" method="POST" id="completion-form">
                <input type="hidden" name="application_id" id="completion_application_id">
                <fieldset>
                    <legend>Avaliação do Operador</legend>
                    <div class="form-group">
                        <label>Nota</label>
                        <div class="star-rating">
                            <input type="radio" id="star5" name="rating" value="5" required/><label for="star5" title="5 estrelas">&#9733;</label>
                            <input type="radio" id="star4" name="rating" value="4" /><label for="star4" title="4 estrelas">&#9733;</label>
                            <input type="radio" id="star3" name="rating" value="3" /><label for="star3" title="3 estrelas">&#9733;</label>
                            <input type="radio" id="star2" name="rating" value="2" /><label for="star2" title="2 estrelas">&#9733;</label>
                            <input type="radio" id="star1" name="rating" value="1" /><label for="star1" title="1 estrela">&#9733;</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="cash_discrepancy">Quebra de Caixa (R$)</label>
                        <input type="text" name="cash_discrepancy" placeholder="Ex: 10,50 (deixe em branco se não houver)">
                    </div>
                    <div class="form-group">
                        <label for="comment">Comentário (opcional)</label>
                        <textarea name="comment" rows="3"></textarea>
                    </div>
                </fieldset>
                <div class="form-group" style="margin-top: 20px;">
                    <button type="submit">Confirmar Finalização</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Lógica para o modal de visualização de imagem
        const imageViewerOverlay = document.getElementById('image-viewer-overlay');
        const imageViewerContent = document.getElementById('image-viewer-content');
        const modalImage = document.getElementById('modal-image');
        const closeImageViewerBtn = document.getElementById('close-image-viewer');
        const openImageModalLinks = document.querySelectorAll('.open-image-modal');
        function openImageViewer(imageUrl) {
            modalImage.src = imageUrl;
            imageViewerOverlay.style.display = 'block';
            imageViewerContent.style.display = 'block';
            document.body.classList.add('modal-open');
        }
        function closeImageViewer() {
            imageViewerOverlay.style.display = 'none';
            imageViewerContent.style.display = 'none';
            if (!document.querySelector('#completion-modal-content[style*="display: block"]')) {
                document.body.classList.remove('modal-open');
            }
        }
        openImageModalLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                openImageViewer(this.href);
            });
        });
        if(closeImageViewerBtn) closeImageViewerBtn.addEventListener('click', closeImageViewer);
        if(imageViewerOverlay) imageViewerOverlay.addEventListener('click', closeImageViewer);

        // Lógica para o modal de finalização de turno
        const completionModalOverlay = document.getElementById('completion-modal-overlay');
        const completionModalContent = document.getElementById('completion-modal-content');
        const closeCompletionBtn = document.getElementById('close-completion-modal');
        const openCompletionBtns = document.querySelectorAll('.open-completion-modal');
        const completionApplicationIdInput = document.getElementById('completion_application_id');
        function openCompletionModal(applicationId) {
            completionApplicationIdInput.value = applicationId;
            completionModalOverlay.style.display = 'block';
            completionModalContent.style.display = 'block';
            document.body.classList.add('modal-open');
        }
        function closeCompletionModal() {
            completionModalOverlay.style.display = 'none';
            completionModalContent.style.display = 'none';
            if (!document.querySelector('#image-viewer-content[style*="display: block"]')) {
                document.body.classList.remove('modal-open');
            }
        }
        openCompletionBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                openCompletionModal(this.dataset.applicationId);
            });
        });
        if(closeCompletionBtn) closeCompletionBtn.addEventListener('click', closeCompletionModal);
        if(completionModalOverlay) completionModalOverlay.addEventListener('click', closeCompletionModal);
    });
    </script>
</body>
</html>
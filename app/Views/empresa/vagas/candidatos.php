<?php
// Formata a data da vaga para um formato amigável
$formatter = new IntlDateFormatter('pt_BR', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'America/Sao_Paulo', null, "d 'de' MMMM 'de' yyyy");
$formattedDate = $formatter->format(new DateTime($shift['shift_date']));
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Operadores da Vaga - TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .applicant-card { display: flex; align-items: center; gap: 20px; background: #fff; padding: 15px; border-radius: 8px; margin-bottom: 15px; border: 1px solid var(--cor-borda); }
        .applicant-avatar img { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 2px solid var(--cor-borda); transition: transform 0.2s; cursor: pointer; }
        .applicant-avatar img:hover { transform: scale(1.1); }
        .applicant-info { flex-grow: 1; }
        .applicant-info h4 { margin: 0 0 5px 0; }
        .applicant-actions { display: flex; flex-wrap: wrap; gap: 10px; }
        .applicant-actions .btn, .applicant-actions form button { padding: 8px 15px; font-size: 14px; }
        .status-group h3 { border-bottom: 2px solid var(--cor-borda); padding-bottom: 10px; margin-top: 40px; color: var(--cor-destaque); }
        .star-rating { display: flex; flex-direction: row-reverse; justify-content: center; gap: 5px; margin-top: 10px; }
        .star-rating input[type="radio"] { display: none; }
        .star-rating label { font-size: 2.5em; color: #ddd; cursor: pointer; transition: color 0.2s; }
        .star-rating input[type="radio"]:checked ~ label, .star-rating label:hover, .star-rating label:hover ~ label { color: #ffc107; }

        /* --- INÍCIO DA CORREÇÃO DEFINITIVA DO MODAL --- */
        .modal-container {
            display: none; /* Começa escondido */
            position: fixed;
            top: 0; left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            /* A chave da solução: centraliza o conteúdo vertical e horizontalmente */
            align-items: center;
            justify-content: center;
        }
        .modal-dialog {
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
            animation: fadeInModal 0.3s ease-out;
            position: relative; /* Necessário para o botão de fechar */
        }
        @keyframes fadeInModal {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .modal-header h2 { margin: 0; font-size: 1.5em; }
        .modal-close-btn {
            position: absolute;
            top: 10px;
            right: 15px;
            background: transparent;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #aaa;
            line-height: 1;
        }
        .modal-close-btn:hover { color: #333; }
        /* --- FIM DA CORREÇÃO DO MODAL --- */
		        /* Modal de Imagem */
        #image-modal-dialog img {
            width: 100%;
            height: auto;
            border-radius: 8px;
        }
        #image-modal-dialog #legendaDoModal {
            text-align: center;
            color: #666;
            padding-top: 10px;
            font-weight: bold;
        }
		        .btn.success-btn {
            background-color: #28a745;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 8px 18px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
            transition: background-color 0.3s, transform 0.2s;
        }
        .btn.success-btn:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }

        /* Botão Vermelho (Marcar Falta) */
        .btn.cancel-btn {
            background-color: #dc3545;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 8px 18px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
            transition: background-color 0.3s, transform 0.2s;
        }
        .btn.cancel-btn:hover {
            background-color: #c82333;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container" style="padding: 40px 0; max-width: 900px;">
        <h1>Operadores para a Vaga</h1>
        <p><a href="/painel/vagas/dia?store_id=<?= $shift['store_id'] ?>&date=<?= $shift['shift_date'] ?>">&larr; Voltar para as vagas do dia</a></p>

        <?php display_flash_message(); ?>

        <div class="info-box">
            <p><strong>Vaga:</strong> <?= htmlspecialchars($shift['title']) ?> em <strong><?= htmlspecialchars($formattedDate) ?></strong> das <?= htmlspecialchars(substr($shift['start_time'], 0, 5)) ?> às <?= htmlspecialchars(substr($shift['end_time'], 0, 5)) ?></p>
        </div>

        <?php
            $applicantsByStatus = ['em_turno' => [], 'confirmados' => [], 'concluidos' => [], 'faltas' => [], 'cancelados' => []];
            if(isset($allApplicants) && is_array($allApplicants)){
                foreach ($allApplicants as $applicant) {
                    switch ($applicant['application_status']) {
                        case 'check_in': $applicantsByStatus['em_turno'][] = $applicant; break;
                        case 'aprovado': $applicantsByStatus['confirmados'][] = $applicant; break;
                        case 'concluido': $applicantsByStatus['concluidos'][] = $applicant; break;
                        case 'no_show': $applicantsByStatus['faltas'][] = $applicant; break;
                        case 'cancelado_operador': $applicantsByStatus['cancelados'][] = $applicant; break;
                    }
                }
            }
        ?>
        
        <!-- Secção Em Turno Agora -->
        <?php if (!empty($applicantsByStatus['em_turno'])): ?>
        <div class="status-group">
            <h3>Em Turno Agora</h3>
            <?php foreach ($applicantsByStatus['em_turno'] as $applicant): ?>
                <div class="applicant-card">
                    <div class="applicant-avatar"><img src="<?= htmlspecialchars($applicant['path_selfie']) ?>" alt="Foto de <?= htmlspecialchars($applicant['name']) ?>" class="imagem-clicavel"></div>
                    <div class="applicant-info">
                        <h4><?= htmlspecialchars($applicant['name']) ?></h4>
                        <p style="margin: 0;">Status: <span class="status-badge status-check_in">Check-in</span></p>
                    </div>
                    <div class="applicant-actions">
                        <button type="button" class="btn success-btn open-completion-modal" data-application-id="<?= $applicant['application_id'] ?>">Concluir e Avaliar</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Secção Confirmados -->
        <?php if (!empty($applicantsByStatus['confirmados'])): ?>
        <div class="status-group">
            <h3>Confirmados (Aguardando Check-in)</h3>
            <?php foreach ($applicantsByStatus['confirmados'] as $applicant): ?>
                <div class="applicant-card">
                    <div class="applicant-avatar"><img src="<?= htmlspecialchars($applicant['path_selfie']) ?>" alt="Foto de <?= htmlspecialchars($applicant['name']) ?>" class="imagem-clicavel"></div>
                    <div class="applicant-info">
                        <h4><?= htmlspecialchars($applicant['name']) ?></h4>
                        <p style="margin: 0;">Status: <span class="status-badge status-aprovado">Aprovado</span></p>
                    </div>
                    <div class="applicant-actions">
                        <form action="/painel/vagas/candidatos/status" method="POST" onsubmit="return confirm('Tem a certeza que deseja marcar FALTA para este operador?');">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="application_id" value="<?= $applicant['application_id'] ?>">
                            <input type="hidden" name="action" value="no_show">
                            <button type="submit" class="btn cancel-btn">Marcar Falta</button>
                        </form>
                        <form action="/painel/vagas/candidatos/status" method="POST" onsubmit="return confirm('Confirmar a chegada e o início do turno para este operador?');">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="application_id" value="<?= $applicant['application_id'] ?>">
                            <input type="hidden" name="action" value="check_in">
                            <button type="submit" class="btn success-btn">Check-in</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Secção Histórico -->
        <?php if (!empty($applicantsByStatus['concluidos']) || !empty($applicantsByStatus['faltas']) || !empty($applicantsByStatus['cancelados'])): ?>
        <div class="status-group">
            <h3>Histórico do Turno</h3>
            <?php foreach ($applicantsByStatus['concluidos'] as $applicant): ?>
               <div class="applicant-card"><div class="applicant-avatar"><img src="<?= htmlspecialchars($applicant['path_selfie']) ?>" alt="Foto de <?= htmlspecialchars($applicant['name']) ?>" class="imagem-clicavel"></div><div class="applicant-info"><h4><?= htmlspecialchars($applicant['name']) ?></h4><p style="margin: 0;">Status: <span class="status-badge status-concluido">Concluído</span></p></div></div>
            <?php endforeach; ?>
            <?php foreach ($applicantsByStatus['faltas'] as $applicant): ?>
               <div class="applicant-card" style="opacity: 0.7;"><div class="applicant-avatar"><img src="<?= htmlspecialchars($applicant['path_selfie']) ?>" alt="Foto de <?= htmlspecialchars($applicant['name']) ?>" class="imagem-clicavel"></div><div class="applicant-info"><h4><?= htmlspecialchars($applicant['name']) ?></h4><p style="margin: 0;">Status: <span class="status-badge status-no_show">Falta</span></p></div></div>
            <?php endforeach; ?>
            <?php foreach ($applicantsByStatus['cancelados'] as $applicant): ?>
               <div class="applicant-card" style="opacity: 0.7;"><div class="applicant-avatar"><img src="<?= htmlspecialchars($applicant['path_selfie']) ?>" alt="Foto de <?= htmlspecialchars($applicant['name']) ?>" class="imagem-clicavel"></div><div class="applicant-info"><h4><?= htmlspecialchars($applicant['name']) ?></h4><p style="margin: 0;">Status: <span class="status-badge status-cancelado_operador">Cancelado Pelo Operador</span></p></div></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (empty($allApplicants)): ?>
            <p>Nenhum operador associado a esta vaga.</p>
        <?php endif; ?>
    </div>
    
    <!-- Estrutura do Modal de Finalização (Atualizada) -->
    <div id="completion-modal-container" class="modal-container">
        <div class="modal-dialog">
            <div class="modal-header">
                <h2>Finalizar e Avaliar Turno</h2>
                <button class="modal-close-btn" id="close-completion-modal">&times;</button>
            </div>
            <div class="modal-body">
                <form action="/painel/vagas/concluir" method="POST" id="completion-form">
                    <?php csrf_field(); ?>
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
                            <input type="text" name="cash_discrepancy" placeholder="Ex: 10,50">
                        </div>
                        <div class="form-group">
                            <label for="comment">Comentário (opcional)</label>
                            <textarea name="comment" rows="3"></textarea>
                        </div>
                    </fieldset>
                    <div class="form-group" style="margin-top: 20px;">
                        <button type="submit" class="btn success-btn">Confirmar Finalização</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Estrutura do Modal de Imagem (Atualizada) -->
    <div id="image-modal-container" class="modal-container">
        <div class="modal-dialog" id="image-modal-dialog">
            <button class="modal-close-btn" id="close-image-modal">&times;</button>
            <img src="" id="imagemDoModal" alt="Foto do Operador">
            <div id="legendaDoModal"></div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Lógica do Modal de Finalização
        const completionModalContainer = document.getElementById('completion-modal-container');
        if (completionModalContainer) {
            const closeCompletionBtn = document.getElementById('close-completion-modal');
            const openCompletionBtns = document.querySelectorAll('.open-completion-modal');
            const completionApplicationIdInput = document.getElementById('completion_application_id');

            openCompletionBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    completionApplicationIdInput.value = this.dataset.applicationId;
                    completionModalContainer.style.display = 'flex';
                });
            });

            function closeCompletionModal() {
                completionModalContainer.style.display = 'none';
            }
            
            closeCompletionBtn.addEventListener('click', closeCompletionModal);
            completionModalContainer.addEventListener('click', function(event) {
                if (event.target === completionModalContainer) {
                    closeCompletionModal();
                }
            });
        }
        
        // Lógica do Modal de Imagem
        const imageModalContainer = document.getElementById("image-modal-container");
        if (imageModalContainer) {
            const modalImg = document.getElementById("imagemDoModal");
            const legenda = document.getElementById("legendaDoModal");
            const imagensClicaveis = document.querySelectorAll('.imagem-clicavel');
            const fecharImg = document.getElementById("close-image-modal");

            imagensClicaveis.forEach(img => {
                img.addEventListener('click', function() {
                    imageModalContainer.style.display = "flex";
                    modalImg.src = this.src;
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
        }

        // Lógica para fechar qualquer modal com a tecla ESC
        document.addEventListener('keydown', (e) => { 
            if (e.key === "Escape") {
                if (completionModalContainer && completionModalContainer.style.display === 'flex') {
                    completionModalContainer.style.display = 'none';
                }
                if (imageModalContainer && imageModalContainer.style.display === 'flex') {
                    imageModalContainer.style.display = 'none';
                }
            }
        });
    });
    </script>
</body>
</html>
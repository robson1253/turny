<?php
// Preparação segura das variáveis vindas do controlador
$shift = $shift ?? [];
$allApplicants = $allApplicants ?? [];
$blockedOperatorIds = $blockedOperatorIds ?? [];

// Formata a data da vaga para um formato amigável
$formattedDate = 'Data não informada';
if (!empty($shift['shift_date'])) {
    // Usar try-catch para o caso de a data ser inválida
    try {
        $formatter = new IntlDateFormatter('pt_BR', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'America/Sao_Paulo', null, "d 'de' MMMM 'de' yyyy");
        $formattedDate = $formatter->format(new DateTime($shift['shift_date']));
    } catch (Exception $e) {
        // Deixa a data padrão em caso de erro
    }
}

// Organiza os candidatos por status para exibição
$applicantsByStatus = [
    'em_turno' => [], 
    'confirmados' => [], 
    'concluidos' => [], 
    'faltas' => [], 
    'cancelados' => []
];
foreach ($allApplicants as $applicant) {
    switch ($applicant['application_status']) {
        case 'check_in': $applicantsByStatus['em_turno'][] = $applicant; break;
        case 'aprovado': $applicantsByStatus['confirmados'][] = $applicant; break;
        case 'concluido': $applicantsByStatus['concluidos'][] = $applicant; break;
        case 'no_show': $applicantsByStatus['faltas'][] = $applicant; break;
        case 'cancelado_operador': $applicantsByStatus['cancelados'][] = $applicant; break;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Operadores da Vaga - TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .applicant-card { display: flex; align-items: center; gap: 20px; background: #fff; padding: 15px; border-radius: 8px; margin-bottom: 15px; border: 1px solid var(--cor-borda); }
        .applicant-avatar img { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; }
        .applicant-info { flex-grow: 1; }
        .applicant-info h4 { margin: 0 0 5px 0; }
        .applicant-actions { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
        .applicant-actions .btn, .applicant-actions form button { padding: 8px 15px; font-size: 14px; }
        .status-group h3 { border-bottom: 2px solid var(--cor-borda); padding-bottom: 10px; margin-top: 40px; color: var(--cor-destaque); }
        .modal-container { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); z-index: 1000; align-items: center; justify-content: center; }
        .modal-dialog { background: #fff; padding: 25px; border-radius: 10px; width: 90%; max-width: 500px; position: relative; }
        .modal-close-btn { position: absolute; top: 10px; right: 15px; background: transparent; border: none; font-size: 28px; cursor: pointer; color: #aaa; }
        .link-button { background:none; border:none; padding:0; text-decoration:underline; cursor:pointer; font-size: inherit; }
        .btn-danger-link { color: var(--cor-perigo); }
        .btn-success-link { color: var(--cor-sucesso); }
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
    </style>
</head>
<body>
    <div class="container" style="padding: 40px 0; max-width: 900px;">
        <h1>Operadores para a Vaga</h1>
        <p><a href="/painel/vagas/dia?store_id=<?= htmlspecialchars($shift['store_id'] ?? '') ?>&date=<?= htmlspecialchars($shift['shift_date'] ?? '') ?>">&larr; Voltar para as vagas do dia</a></p>

        <?php display_flash_message(); ?>

        <div class="info-box">
            <p><strong>Vaga:</strong> <?= htmlspecialchars($shift['title'] ?? 'N/A') ?> em <strong><?= htmlspecialchars($formattedDate) ?></strong> das <?= htmlspecialchars(substr($shift['start_time'] ?? '', 0, 5)) ?> às <?= htmlspecialchars(substr($shift['end_time'] ?? '', 0, 5)) ?></p>
        </div>

        <?php if (!empty($applicantsByStatus['em_turno'])): ?>
        <div class="status-group">
            <h3>Em Turno Agora</h3>
            <?php foreach ($applicantsByStatus['em_turno'] as $applicant): ?>
                <div class="applicant-card">
                    <div class="applicant-avatar"><img src="<?= htmlspecialchars($applicant['path_selfie'] ?? '/images/default-avatar.png') ?>" alt="Foto de <?= htmlspecialchars($applicant['name']) ?>"></div>
                    <div class="applicant-info">
                        <h4><?= htmlspecialchars($applicant['name']) ?></h4>
                        <p style="margin: 0;">Status: <span class="status-badge status-check_in">Check-in Realizado</span></p>
                    </div>
                    <div class="applicant-actions">
                        <button type="button" 
                                class="btn open-finalize-modal" 
                                data-application-id="<?= htmlspecialchars($applicant['application_id']) ?>"
                                data-operator-name="<?= htmlspecialchars($applicant['name']) ?>"
                                data-checkin-time="<?= htmlspecialchars($applicant['check_in_time'] ?? '') ?>">
                            Finalizar Turno
                        </button>
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
                    <div class="applicant-avatar"><img src="<?= htmlspecialchars($applicant['path_selfie'] ?? '/images/default-avatar.png') ?>" alt="Foto de <?= htmlspecialchars($applicant['name']) ?>"></div>
                    <div class="applicant-info">
                        <h4><?= htmlspecialchars($applicant['name']) ?></h4>
                        <p style="margin: 0;">Status: <span class="status-badge status-aprovado">Aprovado</span></p>
                    </div>
                    <div class="applicant-actions">
                        <form action="/painel/vagas/candidatos/status" method="POST" onsubmit="return confirm('Tem a certeza que deseja marcar FALTA para este operador?');">
                            <?php csrf_field(); ?>
                            <input type="hidden" name="application_id" value="<?= htmlspecialchars($applicant['application_id']) ?>">
                            <input type="hidden" name="action" value="no_show">
                            <button type="submit" class="btn btn-danger">Marcar Falta</button>
                        </form>
                        <button type="button" class="btn btn-success open-checkin-modal" data-application-id="<?= htmlspecialchars($applicant['application_id']) ?>" data-operator-name="<?= htmlspecialchars($applicant['name']) ?>">Check-in</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($applicantsByStatus['concluidos']) || !empty($applicantsByStatus['faltas']) || !empty($applicantsByStatus['cancelados'])): ?>
        <div class="status-group">
            <h3>Histórico do Turno</h3>
            <?php foreach ($applicantsByStatus['concluidos'] as $applicant): ?>
                <div class="applicant-card" style="opacity: 0.8;">
                    <div class="applicant-avatar"><img src="<?= htmlspecialchars($applicant['path_selfie'] ?? '/images/default-avatar.png') ?>" alt="Foto de <?= htmlspecialchars($applicant['name']) ?>"></div>
                    <div class="applicant-info"><h4><?= htmlspecialchars($applicant['name']) ?></h4><p style="margin: 0;">Status: <span class="status-badge status-concluido">Concluído</span></p></div>
                    <div class="applicant-actions">
                        <?php if (in_array($applicant['id'], $blockedOperatorIds)): ?>
                            <form action="/empresa/operadores/desbloquear" method="POST" onsubmit="return confirm('Desbloquear este operador?');"><input type="hidden" name="operator_id" value="<?= htmlspecialchars($applicant['id']) ?>"><?php csrf_field(); ?><button type="submit" class="link-button btn-success-link">Desbloquear</button></form>
                        <?php else: ?>
                            <form action="/empresa/operadores/bloquear" method="POST" onsubmit="return confirm('Bloquear este operador para TODAS as futuras vagas da sua empresa?');"><input type="hidden" name="operator_id" value="<?= htmlspecialchars($applicant['id']) ?>"><?php csrf_field(); ?><button type="submit" class="link-button btn-danger-link">Bloquear</button></form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
             <?php foreach ($applicantsByStatus['faltas'] as $applicant): ?>
                <div class="applicant-card" style="opacity: 0.6;"><div class="applicant-avatar"><img src="<?= htmlspecialchars($applicant['path_selfie'] ?? '/images/default-avatar.png') ?>" alt="Foto de <?= htmlspecialchars($applicant['name']) ?>"></div><div class="applicant-info"><h4><?= htmlspecialchars($applicant['name']) ?></h4><p style="margin: 0;">Status: <span class="status-badge status-no_show">Falta</span></p></div></div>
            <?php endforeach; ?>
            <?php foreach ($applicantsByStatus['cancelados'] as $applicant): ?>
                <div class="applicant-card" style="opacity: 0.6;"><div class="applicant-avatar"><img src="<?= htmlspecialchars($applicant['path_selfie'] ?? '/images/default-avatar.png') ?>" alt="Foto de <?= htmlspecialchars($applicant['name']) ?>"></div><div class="applicant-info"><h4><?= htmlspecialchars($applicant['name']) ?></h4><p style="margin: 0;">Status: <span class="status-badge status-cancelado_operador">Cancelado</span></p></div></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (empty($allApplicants)): ?>
            <p style="text-align: center; margin-top: 30px;">Nenhum operador associado a esta vaga ainda.</p>
        <?php endif; ?>
    </div>
    
    <div id="checkin-modal" class="modal-container">
        <div class="modal-dialog">
            <button class="modal-close-btn" data-modal-id="checkin-modal">&times;</button>
            <form action="/painel/vagas/candidatos/status" method="POST" class="form-panel">
                <?php csrf_field(); ?>
                <input type="hidden" name="application_id" id="checkin_application_id">
                <input type="hidden" name="action" value="check_in">
                <h3 style="margin-top:0;">Realizar Check-in de <span id="checkin_operator_name" style="color:var(--cor-primaria);"></span></h3>
                <p>Confirme o horário de entrada do operador.</p>
                <div class="form-group"><label for="checkin_time">Horário de Entrada</label><input type="time" id="checkin_time" name="check_in_time" required></div>
                <div class="form-group" style="margin-top: 20px;"><button type="submit" class="btn">Confirmar Check-in</button></div>
            </form>
        </div>
    </div>

    <div id="finalize-shift-modal" class="modal-container">
        <div class="modal-dialog">
            <button class="modal-close-btn" data-modal-id="finalize-shift-modal">&times;</button>
            <form action="/painel/vagas/concluir" method="POST" class="form-panel">
                <?php csrf_field(); ?><input type="hidden" name="application_id" id="modal_application_id">
                <h3 style="margin-top:0;">Finalizar Turno de <span id="modal_operator_name" style="color:var(--cor-primaria);"></span></h3>
                <div class="data-item" style="background: #f8f9fa; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                    <label>Horário de Entrada (registrado)</label>
                    <span id="modal_check_in_display" style="font-size: 1.2em; font-weight: bold; color: #333;">--:--</span>
                </div>
                <div class="form-group"><label for="modal_check_out_time">Horário de Saída (ajuste se necessário)</label><input type="time" id="modal_check_out_time" name="check_out_time" required></div>
                <div class="form-group"><label>Avaliação do Operador</label><select name="rating" required class="form-control"><option value="">Selecione uma nota</option><option value="5">⭐⭐⭐⭐⭐ (Excelente)</option><option value="4">⭐⭐⭐⭐ (Bom)</option><option value="3">⭐⭐⭐ (Regular)</option><option value="2">⭐⭐ (Ruim)</option><option value="1">⭐ (Péssimo)</option></select></div>
                <div class="form-group"><label for="modal_cash_discrepancy">Quebra de Caixa (R$)</label><input type="text" name="cash_discrepancy" placeholder="Ex: 15,50" class="form-control"></div>
                <div class="form-group"><label for="modal_comment">Comentário (opcional)</label><textarea name="comment" rows="3" class="form-control"></textarea></div>
                <div class="form-group" style="margin-top: 20px;"><button type="submit" class="btn">Confirmar Finalização</button></div>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- LÓGICA DO MODAL DE CHECK-IN ---
        const checkinModal = document.getElementById('checkin-modal');
        if (checkinModal) {
            const openCheckinBtns = document.querySelectorAll('.open-checkin-modal');
            const checkinAppIdInput = document.getElementById('checkin_application_id');
            const checkinOpNameSpan = document.getElementById('checkin_operator_name');
            const checkinTimeInput = document.getElementById('checkin_time');

            openCheckinBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    checkinAppIdInput.value = this.dataset.applicationId;
                    checkinOpNameSpan.textContent = this.dataset.operatorName;
                    
                    const now = new Date();
                    const hours = String(now.getHours()).padStart(2, '0');
                    const minutes = String(now.getMinutes()).padStart(2, '0');
                    checkinTimeInput.value = `${hours}:${minutes}`;

                    checkinModal.style.display = 'flex';
                });
            });
        }

        // --- LÓGICA DO MODAL DE FINALIZAÇÃO ---
        const finalizeModal = document.getElementById('finalize-shift-modal');
        if (finalizeModal) {
            const openFinalizeBtns = document.querySelectorAll('.open-finalize-modal');
            const finalizeAppIdInput = document.getElementById('modal_application_id');
            const finalizeOpNameSpan = document.getElementById('modal_operator_name');
            const finalizeCheckInDisplay = document.getElementById('modal_check_in_display');
            const finalizeCheckOutInput = document.getElementById('modal_check_out_time');

            openFinalizeBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    finalizeAppIdInput.value = this.dataset.applicationId;
                    finalizeOpNameSpan.textContent = this.dataset.operatorName;
                    const checkInTimeDb = this.dataset.checkinTime;
                    
                    if (checkInTimeDb) {
                        finalizeCheckInDisplay.textContent = checkInTimeDb.split(' ')[1].substring(0, 5);
                    } else {
                        finalizeCheckInDisplay.textContent = 'Não registrado';
                    }
                    
                    const now = new Date();
                    const hours = String(now.getHours()).padStart(2, '0');
                    const minutes = String(now.getMinutes()).padStart(2, '0');
                    finalizeCheckOutInput.value = `${hours}:${minutes}`;

                    finalizeModal.style.display = 'flex';
                });
            });
        }
        
        // --- LÓGICA GERAL PARA FECHAR QUALQUER MODAL ---
        const allCloseBtns = document.querySelectorAll('.modal-close-btn');
        const allModals = document.querySelectorAll('.modal-container');

        allCloseBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const modalId = this.getAttribute('data-modal-id');
                const modalToClose = document.getElementById(modalId);
                if (modalToClose) modalToClose.style.display = 'none';
            });
        });

        allModals.forEach(modal => {
            modal.addEventListener('click', function(event) {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        });

        document.addEventListener('keydown', (e) => { 
            if (e.key === "Escape") {
                allModals.forEach(modal => modal.style.display = 'none');
            }
        });
    });
    </script>
</body>
</html>
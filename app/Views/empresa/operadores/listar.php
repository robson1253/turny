<?php
// Proteção e preparação de variáveis
if (!isset($_SESSION['user_id']) || empty($_SESSION['company_id'])) {
    header('Location: /login'); exit();
}
$operators = $operators ?? [];
$blockedOperatorIds = $blockedOperatorIds ?? [];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Operadores Qualificados - TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        /* Estilos para o layout de cards (versão anterior) */
        .operator-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-top: 20px; }
        .operator-card { background-color: #fff; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.07); border: 1px solid var(--cor-borda); padding: 20px; display: flex; flex-direction: column; transition: all 0.2s ease-in-out; }
        .operator-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
        .operator-header { display: flex; align-items: center; gap: 15px; border-bottom: 1px solid var(--cor-borda); padding-bottom: 15px; margin-bottom: 15px; }
        .operator-avatar img { width: 70px; height: 70px; border-radius: 50%; object-fit: cover; border: 3px solid var(--cor-primaria); }
        .operator-info h3 { margin: 0; font-size: 1.2em; color: var(--cor-texto-escuro); }
        .operator-info p { margin: 4px 0 0 0; font-size: 0.9em; color: #777; word-break: break-all; }
        .operator-details { flex-grow: 1; }
        .detail-item { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; font-size: 0.95em; }
        .detail-item svg { width: 18px; height: 18px; fill: #888; flex-shrink: 0; }
        .operator-actions { margin-top: 15px; text-align: right; }
        .link-button { background:none; border:none; padding:0; text-decoration:underline; cursor:pointer; font-size: 0.9em; font-weight: bold; }
        .btn-danger-link { color: var(--cor-perigo); }
        .btn-success-link { color: var(--cor-sucesso); }

        /* Estilos para a busca e toast */
        .search-container { margin-top: 20px; margin-bottom: 20px; }
        .search-container input { width: 100%; max-width: 400px; padding: 10px; border-radius: 5px; border: 1px solid var(--cor-borda); font-size: 1em; }
        #toast-container { position: fixed; top: 20px; right: 20px; z-index: 9999; }
        .toast { background-color: #333; color: #fff; padding: 15px; border-radius: 5px; margin-bottom: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); opacity: 0; transition: opacity 0.3s, transform 0.3s; transform: translateX(100%); }
        .toast.show { opacity: 1; transform: translateX(0); }
        .toast.success { background-color: var(--cor-sucesso); }
        .toast.error { background-color: var(--cor-perigo); }
    </style>
</head>
<body>
    <div id="toast-container"></div>
    <div class="container" style="padding: 40px 0; max-width: 1200px;">
        <h1>Operadores Qualificados para a Sua Empresa</h1>
        <p>Esta é a lista de todos os operadores ativos na plataforma que possuem as qualificações necessárias para trabalhar em suas lojas.</p>
        <p><a href="/painel/empresa">&larr; Voltar ao Painel</a></p>
        
        <?php display_flash_message(); ?>

        <div class="search-container">
            <input type="text" id="search-operator" placeholder="🔎 Pesquisar por nome...">
        </div>

        <div class="operator-grid">
            <?php if (empty($operators)): ?>
                <div class="info-box" style="grid-column: 1 / -1;">
                    <p>Nenhum operador qualificado encontrado para as suas lojas no momento.</p>
                </div>
            <?php else: ?>
                <?php foreach ($operators as $operator): ?>
                    <div class="operator-card" data-name="<?= strtolower(htmlspecialchars($operator['name'])) ?>">
                        <div class="operator-header">
                            <div class="operator-avatar">
                                <img src="<?= htmlspecialchars($operator['path_selfie_thumb'] ?? '/images/default-avatar.png') ?>" alt="Foto de <?= htmlspecialchars($operator['name']) ?>">
                            </div>
                            <div class="operator-info">
                                <h3><?= htmlspecialchars($operator['name']) ?></h3>
                                <p><?= htmlspecialchars($operator['email']) ?></p>
                            </div>
                        </div>

                        <div class="operator-details">
                            <div class="detail-item">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M6.62,10.79C8.06,13.62 10.38,15.94 13.21,17.38L15.41,15.18C15.69,14.9 16.08,14.82 16.43,14.93C17.55,15.3 18.75,15.5 20,15.5A1,1 0 0,1 21,16.5V20A1,1 0 0,1 20,21A17,17 0 0,1 3,4A1,1 0 0,1 4,3H7.5A1,1 0 0,1 8.5,4C8.5,5.25 8.7,6.45 9.07,7.57C9.18,7.92 9.1,8.31 8.82,8.59L6.62,10.79Z" /></svg>
                                <span><?= htmlspecialchars($operator['phone'] ?? 'Não informado') ?></span>
                            </div>
                            <div class="detail-item">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12,17.27L18.18,21L17,14.64L22,9.24L15.81,8.62L12,3L8.19,8.62L2,9.24L7,14.64L5.82,21L12,17.27Z" /></svg>
                                <span>Pontuação: <strong><?= htmlspecialchars(number_format($operator['pontuacao'] ?? 0, 2)) ?></strong></span>
                            </div>
                        </div>

                        <div class="operator-actions">
                            <?php if (in_array($operator['id'], $blockedOperatorIds)): ?>
                                <form action="/empresa/operadores/desbloquear" method="POST" class="block-form" onsubmit="return confirm('Desbloquear este operador?');">
                                    <?php csrf_field(); ?>
                                    <input type="hidden" name="operator_id" value="<?= htmlspecialchars($operator['id']) ?>">
                                    <button type="submit" class="link-button btn-success-link">Desbloquear</button>
                                </form>
                            <?php else: ?>
                                <form action="/empresa/operadores/bloquear" method="POST" class="block-form" onsubmit="return confirm('Bloquear este operador para TODAS as futuras vagas da sua empresa?');">
                                    <?php csrf_field(); ?>
                                    <input type="hidden" name="operator_id" value="<?= htmlspecialchars($operator['id']) ?>">
                                    <button type="submit" class="link-button btn-danger-link">Bloquear</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                 <div id="no-results-message" class="info-box" style="display: none; grid-column: 1 / -1;">
                    <p>Nenhum operador encontrado com este nome.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    function initSearchFilter() {
        const searchInput = document.getElementById('search-operator');
        const operatorGrid = document.querySelector('.operator-grid');
        if (!operatorGrid) return;
        const allCards = operatorGrid.querySelectorAll('.operator-card');
        const noResultsMessage = document.getElementById('no-results-message');

        if (!searchInput) return;

        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase().trim();
            let visibleCards = 0;
            
            allCards.forEach(card => {
                const operatorName = card.dataset.name || '';
                if (operatorName.includes(searchTerm)) {
                    card.style.display = 'flex';
                    visibleCards++;
                } else {
                    card.style.display = 'none';
                }
            });

            if (noResultsMessage) {
                noResultsMessage.style.display = (visibleCards === 0) ? 'block' : 'none';
            }
        });
    }

    function showToast(message, type = 'success') {
        const container = document.getElementById('toast-container');
        if (!container) return;
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        container.appendChild(toast);

        setTimeout(() => { toast.classList.add('show'); }, 100);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => { toast.remove(); }, 300);
        }, 3000);
    }

    function initAjaxBlocking() {
        const forms = document.querySelectorAll('.block-form');

        function updateAllCsrfTokens(newToken) {
            const allCsrfInputs = document.querySelectorAll('input[name="csrf_token"]');
            allCsrfInputs.forEach(input => {
                input.value = newToken;
            });
        }

        forms.forEach(form => {
            form.addEventListener('submit', function(event) {
                event.preventDefault();
                
                const formData = new FormData(this);
                const actionUrl = this.action;
                const button = this.querySelector('button');
                const originalButtonText = button.textContent;

                button.disabled = true;
                button.textContent = 'Aguarde...';

                fetch(actionUrl, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => Promise.reject(err));
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.new_token) {
                        updateAllCsrfTokens(data.new_token);
                    }

                    if (data.status === 'success') {
                        showToast(data.message, 'success');
                        
                        // ==========================================================
                        // CORREÇÃO APLICADA AQUI
                        // ==========================================================
                        const isBlocking = actionUrl.endsWith('/bloquear'); // Usando endsWith para uma verificação exata

                        if (isBlocking) {
                            this.action = '/empresa/operadores/desbloquear';
                            button.textContent = 'Desbloquear';
                            button.classList.remove('btn-danger-link');
                            button.classList.add('btn-success-link');
                        } else {
                            this.action = '/empresa/operadores/bloquear';
                            button.textContent = 'Bloquear';
                            button.classList.remove('btn-success-link');
                            button.classList.add('btn-danger-link');
                        }
                    } else {
                        showToast(data.message || 'Ocorreu um erro.', 'error');
                        button.textContent = originalButtonText;
                    }
                })
                .catch(error => {
                    const errorMessage = error.message || 'Erro de conexão. Tente novamente.';
                    showToast(errorMessage, 'error');
                    button.textContent = originalButtonText;
                })
                .finally(() => {
                    button.disabled = false;
                });
            });
        });
    }

    // Inicializa todas as funcionalidades
    initSearchFilter();
    initAjaxBlocking();
});
</script>
</body>
</html>
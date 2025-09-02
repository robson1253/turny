<?php
// Proteção de acesso
if (!isset($_SESSION['user_id']) || empty($_SESSION['company_id'])) {
    header('Location: /login');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Receber com QR Code - TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .modal-container { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); z-index: 1000; align-items: center; justify-content: center; padding: 20px; }
        .modal-dialog { background: #fff; padding: 25px; border-radius: 10px; width: 90%; max-width: 400px; position: relative; text-align: center; animation: fadeInModal 0.3s ease-out; }
        @keyframes fadeInModal { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }
        .modal-close-btn { position: absolute; top: 10px; right: 15px; background: transparent; border: none; font-size: 28px; cursor: pointer; color: #aaa; }
        #qr-code-image { max-width: 100%; width: 100%; min-height: 300px; background-color: #f8f9fa; }
        #payment-status { font-weight: bold; font-size: 1.2em; margin-top: 15px; transition: color 0.3s; }
    </style>
</head>
<body>
    <div class="container" style="padding: 40px 0; max-width: 500px;">
        <h1>Receber Pagamento com Saldo TURNY</h1>
        <p><a href="/painel/empresa">&larr; Voltar ao Painel</a></p>
        
        <?php \display_flash_message(); ?>

        <form class="form-panel" id="generate-qr-form">
            <?php \csrf_field(); ?>
            <fieldset>
                <legend>Gerar QR Code de Pagamento</legend>
                <div class="form-group">
                    <label for="amount">Valor da Compra (R$)</label>
                    <input type="text" id="amount" name="amount" required placeholder="Ex: 30,50" class="form-control" style="font-size: 1.5em; text-align: center;">
                </div>
                <div class="form-group">
                    <button type="submit">Gerar QR Code</button>
                </div>
            </fieldset>
        </form>
    </div>

    <div id="qr-modal" class="modal-container">
        <div class="modal-dialog">
            <button class="modal-close-btn" id="close-qr-modal">&times;</button>
            <h3 id="qr-value-display"></h3>
            <img id="qr-code-image" src="" alt="QR Code de Pagamento">
            <p id="payment-status">Aguardando pagamento do operador...</p>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('generate-qr-form');
    const modal = document.getElementById('qr-modal');
    const closeModalBtn = document.getElementById('close-qr-modal');

    if (!form || !modal || !closeModalBtn) {
        console.error('Elementos essenciais do formulário ou modal não foram encontrados.');
        return;
    }

    let pollingInterval;
    let currentTransactionToken = null;

    function startPolling(transactionToken) {
        currentTransactionToken = transactionToken;
        
        if (pollingInterval) clearInterval(pollingInterval);

        pollingInterval = setInterval(() => {
            fetch(`/api/pagamentos/status?token=${transactionToken}`)
            .then(res => {
                if (!res.ok) throw new Error('Erro na resposta do servidor de status');
                return res.json();
            })
            .then(data => {
                if (currentTransactionToken !== transactionToken) {
                    clearInterval(pollingInterval);
                    return;
                }

                if (data.payment_status === 'completed') {
                    clearInterval(pollingInterval);
                    const statusEl = document.getElementById('payment-status');
                    const qrImageEl = document.getElementById('qr-code-image');
                    
                    if(statusEl && qrImageEl) {
                        statusEl.textContent = 'PAGAMENTO APROVADO!';
                        statusEl.style.color = 'var(--cor-sucesso)';
                        qrImageEl.style.opacity = '0.2';
                        
                        // --- LÓGICA DO COMPROVANTE ---
                        const receiptButton = document.createElement('a');
                        receiptButton.href = `/painel/empresa/pagamento/comprovante?token=${transactionToken}`;
                        receiptButton.className = 'btn';
                        receiptButton.textContent = 'Imprimir Comprovante';
                        receiptButton.style.marginTop = '15px';
                        receiptButton.target = '_blank';

                        const existingReceiptBtn = document.getElementById('receipt-btn');
                        if (!existingReceiptBtn) {
                            receiptButton.id = 'receipt-btn';
                            statusEl.parentNode.appendChild(receiptButton);
                        }
                        
                        // A LINHA ABAIXO FOI REMOVIDA PARA O MODAL NÃO FECHAR SOZINHO
                        // setTimeout(() => closeModal(), 3000); 
                    }
                }
            })
            .catch(err => {
                console.error("Erro no polling:", err);
                clearInterval(pollingInterval);
            });
        }, 3000);
    }
    
    // O resto do seu script (form.addEventListener, closeModal, etc.) continua exatamente o mesmo.
    // ...
    form.addEventListener('submit', function(event) {
        event.preventDefault();
        const button = form.querySelector('button');
        const amountInput = document.getElementById('amount');
        const qrImage = document.getElementById('qr-code-image');
        const qrValueDisplay = document.getElementById('qr-value-display');
        const paymentStatus = document.getElementById('payment-status');
        const csrfInput = form.querySelector('input[name="csrf_token"]');

        paymentStatus.textContent = 'Aguardando pagamento do operador...';
        paymentStatus.style.color = 'inherit';
        qrImage.style.opacity = '1';
        const existingReceiptBtn = document.getElementById('receipt-btn');
        if (existingReceiptBtn) {
            existingReceiptBtn.remove();
        }

        button.disabled = true;
        button.textContent = 'Gerando...';

        fetch('/api/pagamentos/gerar-qr', {
            method: 'POST',
            body: new FormData(form),
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => Promise.reject(err));
            }
            return response.json();
        })
        .then(data => {
            if (csrfInput && data.new_token) {
                csrfInput.value = data.new_token;
            }

            if (data.status === 'success') {
                qrImage.src = data.qr_code_data_uri;
                qrValueDisplay.textContent = 'Pagar R$ ' + amountInput.value.replace('.', ',');
                modal.style.display = 'flex';
                
                if (data.transaction_token) {
                    startPolling(data.transaction_token);
                }
            } else {
                alert('Erro: ' + (data.message || 'Ocorreu um erro desconhecido.'));
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            const errorMessage = error.message || 'Erro de conexão ou resposta inválida.';
            alert(errorMessage);
        })
        .finally(() => {
            button.disabled = false;
            button.textContent = 'Gerar QR Code';
        });
    });

    function closeModal() {
        if (pollingInterval) {
            clearInterval(pollingInterval);
        }
        modal.style.display = 'none';
    }

    closeModalBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', function(event) {
        if (event.target === modal) {
            closeModal();
        }
    });
    document.addEventListener('keydown', (e) => { 
        if (e.key === "Escape" && modal.style.display === 'flex') {
            closeModal();
        }
    });
});
</script>

</body>
</html>

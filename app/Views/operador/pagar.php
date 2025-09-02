<?php $activePage = 'carteira'; ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagar com QR Code - TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        body, html { margin: 0; padding: 0; width: 100%; height: 100%; overflow: hidden; background-color: #000; }
        #scanner-container { position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; flex-direction: column; justify-content: center; align-items: center; }
        #qr-video { width: 100%; height: 100%; object-fit: cover; }
        #scan-status { position: absolute; bottom: 30px; left: 50%; transform: translateX(-50%); background-color: rgba(0, 0, 0, 0.7); color: #fff; padding: 10px 20px; border-radius: 20px; font-weight: bold; z-index: 10; }
        #back-button { position: absolute; top: 20px; left: 20px; z-index: 10; padding: 10px; background-color: rgba(0, 0, 0, 0.5); color: #fff; text-decoration: none; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: bold; line-height: 1; }
        .modal-container { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); z-index: 1000; align-items: center; justify-content: center; }
        .modal-dialog { background: #fff; padding: 25px; border-radius: 10px; width: 90%; max-width: 500px; position: relative; }
    </style>
</head>
<body>
    <a href="/painel/operador/carteira" id="back-button">&larr;</a>

    <div id="scanner-container">
        <video id="qr-video"></video>
        <p id="scan-status">Aponte para o QR Code</p>
    </div>

    <div id="confirmation-modal" class="modal-container">
        <div class="modal-dialog">
             <form id="confirm-payment-form" action="/api/pagamentos/consumir-qr" method="POST" class="form-panel">
                <?php \csrf_field(); ?>
                <input type="hidden" name="qr_content" id="qr_content_hidden">
                <h3 id="confirmation-title">Confirmar Pagamento?</h3>
                <p id="confirmation-details" style="font-size:1.2em;"></p>

                <div class="form-group">
                    <label for="password">Senha da sua conta TURNY</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password" class="form-control">
                </div>

                <div class="form-group" style="display:flex; gap: 10px; margin-top: 20px;">
                    <button type="button" id="cancel-payment-btn" class="btn btn-secondary" style="flex:1;">Cancelar</button>
                    <button type="submit" class="btn btn-success" style="flex:1;">Sim, Pagar Agora</button>
                </div>
            </form>
        </div>
    </div>

    <script src="/js/qr-scanner.umd.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const videoElem = document.getElementById('qr-video');
        const scanStatus = document.getElementById('scan-status');
        const modal = document.getElementById('confirmation-modal');
        const confirmForm = document.getElementById('confirm-payment-form');
        const qrContentInput = document.getElementById('qr_content_hidden');
        const confirmationDetails = document.getElementById('confirmation-details');
        const cancelBtn = document.getElementById('cancel-payment-btn');

        const qrScanner = new QrScanner(videoElem, result => {
            qrScanner.stop();
            scanStatus.textContent = 'QR Code lido com sucesso! Verificando...';
            
            try {
                const qrData = JSON.parse(result.data);
                if (!qrData.payload || !qrData.payload.amount) {
                    throw new Error('QR Code com formato inválido.');
                }
                const payload = qrData.payload;
                const amount = parseFloat(payload.amount).toFixed(2).replace('.', ',');
                let confirmationText = `Valor: <strong>R$ ${amount}</strong>`;

                if (payload.payee_operator_id) {
                    confirmationText += `<br>Para o operador: <strong>${payload.operator_name || 'Desconhecido'}</strong>`;
                    confirmForm.action = '/api/operador/pagar-operador';
                } else {
                    confirmationText += `<br>Para: <strong>Loja</strong>`;
                    confirmForm.action = '/api/pagamentos/consumir-qr';
                }

                confirmationDetails.innerHTML = confirmationText;
                qrContentInput.value = result.data;
                modal.style.display = 'flex';
            } catch (e) {
                scanStatus.textContent = 'QR Code inválido. Tente novamente.';
                setTimeout(() => qrScanner.start(), 2000);
            }
        }, { highlightScanRegion: true, highlightCodeOutline: true });

        qrScanner.start().catch(err => {
            scanStatus.textContent = 'Não foi possível acessar a câmera. Autorize o acesso no seu navegador.';
            console.error(err);
        });
        
        cancelBtn.addEventListener('click', () => {
            modal.style.display = 'none';
            scanStatus.textContent = 'Aponte a câmera para o QR Code...';
            qrScanner.start();
        });

        // SCRIPT DE SUBMISSÃO ÚNICO E CORRIGIDO
        confirmForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const button = this.querySelector('button[type="submit"]');
            const csrfInput = document.querySelector('input[name="csrf_token"]');
            button.disabled = true;
            button.textContent = 'Processando...';

            fetch(this.action, {
                method: 'POST',
                body: new FormData(this),
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.new_token && csrfInput) {
                    csrfInput.value = data.new_token;
                }
                
                if(data.status === 'success') {
                    window.location.href = '/painel/operador/pagamento-sucesso';
                } else {
                    alert(data.message || 'Ocorreu um erro.');
                    modal.style.display = 'none';
                    qrScanner.start();
                }
            })
            .catch(err => {
                alert('Erro de conexão ou resposta inválida do servidor.');
                qrScanner.start();
            })
            .finally(() => {
                button.disabled = false;
                button.textContent = 'Sim, Pagar Agora';
            });
        });
    });
    </script>
</body>
</html>
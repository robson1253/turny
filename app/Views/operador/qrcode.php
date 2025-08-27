<?php
$activePage = 'carteira';
$action = $_GET['action'] ?? 'pagar'; // Define qual aba estará ativa
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Pagar ou Cobrar - TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .qr-tabs { display: flex; border-bottom: 2px solid var(--cor-borda); margin-bottom: 20px; }
        .qr-tabs a { padding: 10px 20px; text-decoration: none; color: #888; font-weight: bold; border-bottom: 3px solid transparent; }
        .qr-tabs a.active { color: var(--cor-primaria); border-bottom-color: var(--cor-primaria); }
        .qr-tab-content { display: none; }
        .qr-tab-content.active { display: block; }
        #scanner-container { text-align: center; }
        #qr-video { width: 100%; max-width: 400px; border-radius: 8px; border: 2px solid #ddd; }
        #scan-status { font-weight: bold; margin-top: 15px; font-size: 1.1em; }
    </style>
</head>
<body class="operador-body">
    <div class="operador-container">
        <?php require_once __DIR__ . '/../partials/operador_header.php'; ?>

        <main class="operador-content">
            <h1>Pagar / Cobrar</h1>
            <p><a href="/painel/operador/carteira">&larr; Voltar para a Carteira</a></p>

            <div class="qr-tabs">
                <a href="/painel/operador/qrcode?action=pagar" class="<?= $action === 'pagar' ? 'active' : '' ?>">Pagar (Escanear)</a>
                <a href="/painel/operador/qrcode?action=cobrar" class="<?= $action === 'cobrar' ? 'active' : '' ?>">Cobrar (Gerar QR)</a>
            </div>

            <div id="pagar-tab" class="qr-tab-content <?= $action === 'pagar' ? 'active' : '' ?>">
                <div id="scanner-container">
                    <video id="qr-video"></video>
                    <p id="scan-status">Aponte a câmera para o QR Code na tela do caixa.</p>
                </div>
            </div>

            <div id="cobrar-tab" class="qr-tab-content <?= $action === 'cobrar' ? 'active' : '' ?>">
                <p>Funcionalidade de gerar QR Code para cobrança (em desenvolvimento).</p>
            </div>
        </main>
        
        <?php require_once __DIR__ . '/../partials/operador_footer.php'; ?>
    </div>

    <div id="confirmation-modal" class="modal-container">
        <div class="modal-dialog">
             <form id="confirm-payment-form" action="/api/pagamentos/consumir-qr" method="POST" class="form-panel">
                <?php csrf_field(); ?>
                <input type="hidden" name="qr_content" id="qr_content_hidden">
                <h3 id="confirmation-title">Confirmar Pagamento?</h3>
                <p id="confirmation-details" style="font-size:1.2em;"></p>
                <div class="form-group" style="display:flex; gap: 10px; margin-top: 20px;">
                    <button type="button" id="cancel-payment-btn" class="btn btn-secondary" style="flex:1;">Cancelar</button>
                    <button type="submit" class="btn btn-success" style="flex:1;">Sim, Pagar Agora</button>
                </div>
            </form>
        </div>
    </div>

    <script type="module">
        import QrScanner from '/js/qr-scanner.umd.min.js';

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
                const amount = parseFloat(qrData.payload.amount).toFixed(2).replace('.', ',');
                // No futuro, você pode fazer uma chamada API aqui para buscar o nome da loja pelo store_id
                confirmationDetails.innerHTML = `Valor: <strong>R$ ${amount}</strong>`;
                qrContentInput.value = result.data;
                modal.style.display = 'flex';
            } catch (e) {
                scanStatus.textContent = 'QR Code inválido. Tente novamente.';
                setTimeout(() => qrScanner.start(), 2000);
            }
        }, { highlightScanRegion: true, highlightCodeOutline: true });

        // Inicia a câmera apenas se a aba "Pagar" estiver ativa
        if (document.getElementById('pagar-tab').classList.contains('active')) {
            qrScanner.start().catch(err => {
                scanStatus.textContent = 'Não foi possível acessar a câmera. Por favor, autorize o acesso no seu navegador.';
                console.error(err);
            });
        }
        
        cancelBtn.addEventListener('click', () => {
            modal.style.display = 'none';
            scanStatus.textContent = 'Aponte a câmera para o QR Code...';
            qrScanner.start();
        });

        // Lógica de envio do formulário de confirmação
        confirmForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const button = this.querySelector('button[type="submit"]');
            button.disabled = true;
            button.textContent = 'Processando...';

            fetch(this.action, {
                method: 'POST',
                body: new FormData(this),
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message); // Exibe a mensagem de sucesso ou erro
                if(data.status === 'success') {
                    window.location.href = '/painel/operador/carteira'; // Redireciona para a carteira
                } else {
                     modal.style.display = 'none';
                     qrScanner.start();
                }
            })
            .catch(err => {
                alert('Erro de conexão.');
            })
            .finally(() => {
                button.disabled = false;
                button.textContent = 'Sim, Pagar Agora';
            });
        });

    </script>
</body>
</html>
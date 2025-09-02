<?php
// Preparação segura de variáveis
$receipt = $receipt ?? [];

// Sanitiza todos os dados de uma vez para um código mais limpo
$store_name = htmlspecialchars($receipt['store_name'] ?? 'Loja não identificada');
$completed_at = isset($receipt['completed_at']) ? (new DateTime($receipt['completed_at']))->format('d/m/Y \à\s H:i:s') : 'N/A';
$operator_name = htmlspecialchars($receipt['operator_name'] ?? 'Operador não identificado');
$user_name = htmlspecialchars($receipt['user_name'] ?? 'Usuário não identificado');
$amount = htmlspecialchars(number_format($receipt['amount'] ?? 0, 2, ',', '.'));
$transaction_token = htmlspecialchars($receipt['transaction_token'] ?? 'N/A');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Comprovante de Pagamento (2 Vias) - TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        body { background-color: #f4f7f6; font-family: sans-serif; }
        .receipt-wrapper { max-width: 480px; margin: 30px auto; }
        
        .receipt-copy { 
            background: #fff;
            border: 1px solid #e0e0e0;
            padding: 20px; 
            margin-bottom: 25px;
        }
        .receipt-header { text-align: center; margin-bottom: 15px; }
        .receipt-header h2 { margin: 0; font-size: 1.3em; color: #333; }
        .receipt-header p { margin: 5px 0 0 0; color: #666; font-size: 0.9em;}
        .receipt-amount { text-align: center; margin: 20px 0; }
        .receipt-amount .amount-label { font-size: 0.9em; color: #555; margin: 0; }
        .receipt-amount .amount-value { font-size: 2.2em; font-weight: bold; color: var(--cor-primaria); margin: 0; }
        .receipt-details .detail-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f0f0f0; font-size: 0.9em; }
        .receipt-details .detail-item:last-child { border-bottom: none; }
        .receipt-details .detail-label { color: #666; }
        .receipt-details .detail-value { font-weight: bold; color: #333; }
        .receipt-footer { text-align: center; margin-top: 20px; font-size: 0.75em; color: #aaa; }
        .actions { text-align: center; margin-top: 20px; }
        
        /* --- ESTILOS PARA IMPRESSÃO --- */
@media print {
    body {
        background: #fff;
        margin: 0;
        padding: 0;
        width: 80mm; /* largura da bobina */
    }

    .receipt-wrapper {
        width: 80mm;
        margin: 0;
        padding: 0;
    }

    .receipt-copy {
        width: 100%;
        padding: 8px;
        margin: 0 0 12px 0;
        border: none;
        font-size: 12px; /* fonte mais compacta */
        box-shadow: none;
        page-break-inside: avoid;
    }

    .receipt-header h2 {
        font-size: 14px;
        margin: 0 0 3px 0;
    }

    .receipt-header p {
        font-size: 11px;
        margin: 0;
    }

    .receipt-amount .amount-value {
        font-size: 16px;
    }

    .receipt-details .detail-item {
        font-size: 11px;
        padding: 4px 0;
    }

    .receipt-footer {
        font-size: 9px;
        margin-top: 8px;
    }

    /* Remove botões e links */
    .actions, .non-printable, a[href] {
        display: none !important;
    }

    /* Linha pontilhada entre vias */
    .receipt-copy.via-estabelecimento {
        border-bottom: 1px dashed #000;
        margin-bottom: 8px;
        padding-bottom: 8px;
    }
}
    </style>
</head>
<body>
    <div class="receipt-wrapper">
        <p class="non-printable" style="text-align: center; margin-bottom: 20px;"><a href="/painel/empresa/receber">&larr; Voltar para a tela de recebimento</a></p>
        
        <div class="print-area">
            
            <div class="receipt-copy via-estabelecimento">
                <div class="receipt-header">
                    <h2>Comprovante de Pagamento</h2>
                    <p>TURNY Pay - Via do Estabelecimento</p>
                </div>
                <div class="receipt-amount"><p class="amount-label">Valor Pago</p><p class="amount-value">R$ <?= $amount ?></p></div>
                <div class="receipt-details">
                    <div class="detail-item"><span class="detail-label">Pago por (Operador)</span><span class="detail-value"><?= $operator_name ?></span></div>
                    <div class="detail-item"><span class="detail-label">Recebido por (Caixa)</span><span class="detail-value"><?= $user_name ?></span></div>
                    <div class="detail-item"><span class="detail-label">Loja</span><span class="detail-value"><?= $store_name ?></span></div>
                    <div class="detail-item"><span class="detail-label">Data e Hora</span><span class="detail-value"><?= $completed_at ?></span></div>
                </div>
                <div class="receipt-footer">ID da Transação: <?= $transaction_token ?></div>
            </div>

            <div class="receipt-copy via-operador">
                <div class="receipt-header">
                    <h2>Comprovante de Pagamento</h2>
                    <p>TURNY Pay - Via do Operador</p>
                </div>
                <div class="receipt-amount"><p class="amount-label">Valor Pago</p><p class="amount-value">R$ <?= $amount ?></p></div>
                <div class="receipt-details">
                    <div class="detail-item"><span class="detail-label">Pago por (Operador)</span><span class="detail-value"><?= $operator_name ?></span></div>
                    <div class="detail-item"><span class="detail-label">Recebido por (Caixa)</span><span class="detail-value"><?= $user_name ?></span></div>
                    <div class="detail-item"><span class="detail-label">Loja</span><span class="detail-value"><?= $store_name ?></span></div>
                    <div class="detail-item"><span class="detail-label">Data e Hora</span><span class="detail-value"><?= $completed_at ?></span></div>
                </div>
                <div class="receipt-footer">ID da Transação: <?= $transaction_token ?></div>
            </div>

        </div>

        <div class="actions">
            <button onclick="window.print()" class="btn">Imprimir 2 Vias</button>
        </div>
    </div>
</body>
</html>
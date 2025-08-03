<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avaliar Empresa - TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .star-rating { display: flex; flex-direction: row-reverse; justify-content: center; gap: 5px; margin-top: 10px; }
        .star-rating input[type="radio"] { display: none; }
        .star-rating label { font-size: 2.5em; color: #ddd; cursor: pointer; transition: color 0.2s; }
        .star-rating input[type="radio"]:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label { color: #ffc107; }
    </style>
</head>
<body class="operador-body">
    <div class="operador-container">
        <header class="operador-header">
            <div class="logo">Turn<span>y</span></div>
        </header>

        <main class="operador-content">
            <p style="margin-bottom: 30px;"><a href="/painel/operador/meus-turnos">&larr; Voltar para Meus Turnos</a></p>
            
            <?php display_flash_message(); ?>

            <form action="/painel/operador/avaliar" method="POST" class="form-panel" style="border: none; box-shadow: none; padding: 0;">
                
                <?php csrf_field(); // <-- Token de segurança adicionado ?>
                <input type="hidden" name="application_id" value="<?= htmlspecialchars($shiftDetails['application_id']) ?>">
                
                <fieldset>
                    <legend>Como foi a sua experiência?</legend>
                    <div class="info-box" style="margin-bottom: 20px;">
                        <p><strong>Empresa:</strong> <?= htmlspecialchars($shiftDetails['company_name']) ?></p>
                        <p style="margin:0;"><strong>Data:</strong> <?= htmlspecialchars(date('d/m/Y', strtotime($shiftDetails['shift_date']))) ?></p>
                    </div>
                    <div class="form-group">
                        <label>Sua Nota</label>
                        <div class="star-rating">
                            <input type="radio" id="star5" name="rating" value="5" required/><label for="star5" title="5 estrelas">&#9733;</label>
                            <input type="radio" id="star4" name="rating" value="4" /><label for="star4" title="4 estrelas">&#9733;</label>
                            <input type="radio" id="star3" name="rating" value="3" /><label for="star3" title="3 estrelas">&#9733;</label>
                            <input type="radio" id="star2" name="rating" value="2" /><label for="star2" title="2 estrelas">&#9733;</label>
                            <input type="radio" id="star1" name="rating" value="1" /><label for="star1" title="1 estrela">&#9733;</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="comment">Seu Comentário (opcional)</label>
                        <textarea name="comment" rows="4"></textarea>
                    </div>
                </fieldset>
                <div class="form-group" style="margin-top: 20px;">
                    <button type="submit">Enviar Avaliação</button>
                </div>
            </form>
        </main>
    </div>
</body>
</html>

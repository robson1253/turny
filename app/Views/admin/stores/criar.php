<?php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403); die('Acesso negado.');
}
if (!isset($company)) die('Erro: dados da empresa não carregados.');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Adicionar Nova Loja - TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="container" style="padding: 40px 0; max-width: 800px;">
        <h1>Adicionar Nova Loja para: <?= htmlspecialchars($company['nome_fantasia']) ?></h1>
        <p><a href="/admin/stores?company_id=<?= htmlspecialchars($_GET['company_id']) ?>">&larr; Voltar para a Lista de Lojas</a></p>
        
        <form action="/admin/stores/criar" method="POST" class="form-panel" id="store-form" style="margin-top: 20px;">
            <input type="hidden" name="company_id" value="<?= htmlspecialchars($_GET['company_id']) ?>">
            
            <fieldset>
                <legend>Dados da Loja</legend>
                <div class="form-group">
                    <label for="name">Nome da Loja (Ex: Loja Centro)</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="cnpj">CNPJ da Loja</label>
                    <input type="text" id="cnpj" name="cnpj" required>
                </div>
                <div class="form-group">
                    <label for="inscricao_estadual">Inscrição Estadual (IE)</label>
                    <input type="text" id="inscricao_estadual" name="inscricao_estadual" required>
                </div>
            </fieldset>

            <fieldset>
                <legend>Endereço da Loja</legend>
                <div class="form-group"><label for="cep">CEP</label><input type="text" id="cep" name="cep" required></div>
                <div class="form-group"><label for="endereco">Endereço</label><input type="text" id="endereco" name="endereco" required></div>
                <div class="form-group"><label for="bairro">Bairro</label><input type="text" id="bairro" name="bairro" required></div>
                <div class="form-group"><label for="cidade">Cidade</label><input type="text" id="cidade" name="cidade" required></div>
                <div class="form-group"><label for="estado">Estado (UF)</label><input type="text" id="estado" name="estado" maxlength="2" required></div>
            </fieldset>

            <div class="form-group" style="margin-top: 30px;">
                <button type="submit">Guardar Loja</button>
            </div>
        </form>
    </div>

    <script src="/js/cep.js"></script>
    <script src="/js/validators.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('store-form');
            if (form) {
                // Seleciona todos os elementos de input e select que não estão escondidos
                const focusableElements = Array.from(
                    form.querySelectorAll('input:not([type="hidden"]), select')
                );

                form.addEventListener('keydown', function(event) {
                    // Verifica se a tecla pressionada foi 'Enter' e se não foi numa área de texto
                    if (event.key === 'Enter' && event.target.tagName !== 'TEXTAREA') {
                        // Impede o comportamento padrão de submeter o formulário
                        event.preventDefault();

                        const currentElement = document.activeElement;
                        const currentIndex = focusableElements.indexOf(currentElement);

                        // Se houver um próximo elemento na lista, move o foco para ele
                        if (currentIndex > -1 && (currentIndex + 1) < focusableElements.length) {
                            const nextElement = focusableElements[currentIndex + 1];
                            nextElement.focus();
                        } else {
                            // Se for o último elemento, clica no botão de submissão
                            form.querySelector('button[type="submit"]').click();
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>
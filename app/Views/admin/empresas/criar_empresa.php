<?php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403); die('Acesso negado.');
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Adicionar Nova Conta de Empresa - TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="container" style="padding: 40px 0; max-width: 800px;">
        <h1>Adicionar Nova Conta de Empresa</h1>
        <p><a href="/admin/empresas">&larr; Voltar para a Lista de Empresas</a></p>
        
        <form action="/admin/empresas/criar" method="POST" class="form-panel" id="company-form">
            
            <?php 
            // Exibe qualquer mensagem flash (de erro, por exemplo) que possa existir
            display_flash_message(); 
            
            // Adiciona o campo CSRF oculto e obrigatório ao formulário.
            csrf_field(); 
            ?>
            
            <fieldset>
                <legend>1. Dados da Empresa-Mãe</legend>
                <div class="form-group">
                    <label for="razao_social">Razão Social</label>
                    <input type="text" id="razao_social" name="razao_social" required>
                </div>
                <div class="form-group">
                    <label for="nome_fantasia">Nome Fantasia</label>
                    <input type="text" id="nome_fantasia" name="nome_fantasia" required>
                </div>
                <div class="form-group">
                    <label for="telefone">Telefone Principal</label>
                    <input type="text" id="telefone" name="telefone" required>
                </div>
                <div class="form-group">
                    <label for="contact_email">E-mail de Contacto Principal</label>
                    <input type="email" id="contact_email" name="contact_email" required>
                </div>
            </fieldset>
            
            <fieldset>
                <legend>2. Utilizadores Principais</legend>
                <h4 style="margin-top:0;">Utilizador: Administrador da Empresa</h4>
                <div class="form-group"><label for="admin_name">Nome</label><input type="text" id="admin_name" name="admin_name" required></div>
                <div class="form-group"><label for="admin_email">E-mail de Login</label><input type="email" id="admin_email" name="admin_email" required></div>
                <div class="form-group">
                    <label for="admin_password">Senha Provisória</label>
                    <input type="password" id="admin_password" name="admin_password" required pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" title="A senha deve ter pelo menos 8 caracteres, incluindo um número, uma letra maiúscula e uma minúscula.">
                    <small>Mínimo 8 caracteres, 1 maiúscula, 1 minúscula, 1 número.</small>
                </div>
                <hr>
                <h4>Utilizador: Gerente</h4>
                <div class="form-group"><label for="manager_name">Nome</label><input type="text" id="manager_name" name="manager_name" required></div>
                <div class="form-group"><label for="manager_email">E-mail de Login</label><input type="email" id="manager_email" name="manager_email" required></div>
                 <div class="form-group">
                    <label for="manager_password">Senha Provisória</label>
                    <input type="password" id="manager_password" name="manager_password" required pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" title="A senha deve ter pelo menos 8 caracteres, incluindo um número, uma letra maiúscula e uma minúscula.">
                    <small>Mínimo 8 caracteres, 1 maiúscula, 1 minúscula, 1 número.</small>
                </div>
                <hr>
                <h4>Utilizador: Recepcionista</h4>
                <div class="form-group"><label for="receptionist_name">Nome</label><input type="text" id="receptionist_name" name="receptionist_name" required></div>
                <div class="form-group"><label for="receptionist_email">E-mail de Login</label><input type="email" id="receptionist_email" name="receptionist_email" required></div>
                 <div class="form-group">
                    <label for="receptionist_password">Senha Provisória</label>
                    <input type="password" id="receptionist_password" name="receptionist_password" required pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" title="A senha deve ter pelo menos 8 caracteres, incluindo um número, uma letra maiúscula e uma minúscula.">
                    <small>Mínimo 8 caracteres, 1 maiúscula, 1 minúscula, 1 número.</small>
                </div>
            </fieldset>

            <div class="form-group" style="margin-top: 30px;">
                <button type="submit">Criar Conta e Adicionar Lojas</button>
            </div>
        </form>
    </div>

    <script>
        // O seu script de navegação com a tecla Enter continua o mesmo
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('company-form');
            if (form) {
                const focusableElements = Array.from(
                    form.querySelectorAll('input:not([type="hidden"]), select')
                );

                form.addEventListener('keydown', function(event) {
                    if (event.key === 'Enter' && event.target.tagName !== 'TEXTAREA') {
                        event.preventDefault();
                        const currentElement = document.activeElement;
                        const currentIndex = focusableElements.indexOf(currentElement);

                        if (currentIndex > -1 && (currentIndex + 1) < focusableElements.length) {
                            const nextElement = focusableElements[currentIndex + 1];
                            nextElement.focus();
                        } else {
                            form.querySelector('button[type="submit"]').click();
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>
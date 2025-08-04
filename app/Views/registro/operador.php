<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registo de Operador - TURNY</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        /* Estilos para o Wizard de Registo */
        .progress-bar { display: flex; justify-content: space-between; margin-bottom: 40px; list-style-type: none; padding: 0; }
        .progress-step { text-align: center; flex: 1; position: relative; font-weight: bold; color: #ccc; font-size: 14px; }
        .progress-step::before { content: ''; width: 30px; height: 30px; border-radius: 50%; background-color: #ccc; border: 3px solid var(--cor-fundo); display: block; margin: 0 auto 10px auto; transition: all 0.3s; }
        .progress-step::after { content: ''; width: 100%; height: 3px; background-color: #ccc; position: absolute; top: 15px; left: -50%; z-index: -1; transition: all 0.3s; }
        .progress-step:first-child::after { content: none; }
        .progress-step.active { color: var(--cor-primaria); }
        .progress-step.active::before { background-color: var(--cor-primaria); border-color: var(--cor-primaria-light); }
        .progress-step.active::after { background-color: var(--cor-primaria); }

        .form-step { display: none; }
        .form-step.active { display: block; animation: fadeIn 0.5s; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .form-navigation { display: flex; justify-content: space-between; margin-top: 40px; }
        .nav-btn { padding: 12px 30px; border: none; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; transition: all 0.2s; }
        .nav-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .btn-prev { background-color: #eee; color: #555; }
        .btn-next { background-color: var(--cor-primaria); color: var(--cor-branco); }
        
        .input-group-prepend { display: flex; align-items: center; }
        .input-group-text { background-color: #e9ecef; border: 1px solid #ced4da; padding: 0 12px; border-radius: 8px 0 0 8px; font-weight: bold; height: 48px; box-sizing: border-box; }
        .input-group-prepend + input { border-radius: 0 8px 8px 0; }
        
        /* Estilos para mensagens flash */
        .flash-message { border-radius: 8px; padding: 15px; margin-bottom: 20px; font-size: 14px; font-weight: bold; text-align: left; }
        .flash-message.error { background-color: #fce4e4; color: #c62828; border: 1px solid #c62828; }
        .flash-message.success { background-color: #e8f5e9; color: #2e7d32; border: 1px solid #2e7d32; }
    </style>
</head>
<body class="operador-body">

    <div class="operador-container">
        <div style="text-align: center; padding: 30px 20px 20px 20px;">
             <a href="/" class="logo">Turn<span>y</span></a>
        </div>
        
        <form action="/registro/operador" method="POST" class="form-panel" id="register-form" enctype="multipart/form-data" style="border: none; box-shadow: none; padding: 20px 30px;">
            <h1 style="text-align: center; margin-bottom: 10px;">Registo de Novo Operador</h1>
            <p style="text-align: center; margin-bottom: 40px;">Preencha os seus dados para começar a receber oportunidades.</p>
            
            <?php 
            // Exibe qualquer mensagem flash (de erro, por exemplo, CPF duplicado) que possa existir
            display_flash_message(); 
            ?>

            <ul class="progress-bar">
                <li class="progress-step active">Pessoais</li>
                <li class="progress-step">Endereço</li>
                <li class="progress-step">Pagamento</li>
                <li class="progress-step">Documentos</li>
                <li class="progress-step">Acesso</li>
            </ul>

            <div id="form-error-message" class="flash-message error" style="display: none;"></div>

            <?php 
            // Adiciona o campo CSRF oculto e obrigatório ao formulário.
            csrf_field(); 
            ?>

            <fieldset class="form-step active">
                <legend>Dados Pessoais</legend>
                <div class="form-group"><label for="name">Nome Completo</label><input type="text" id="name" name="name" required></div>
                <div class="form-group"><label for="cpf">CPF</label><input type="text" id="cpf" name="cpf" required></div>
                <div class="form-group"><label for="phone">Telefone / WhatsApp</label><input type="text" id="phone" name="phone" required></div>
                <div class="form-navigation">
                    <div></div>
                    <button type="button" class="nav-btn btn-next">Próximo &rarr;</button>
                </div>
            </fieldset>

            <fieldset class="form-step">
                <legend>Endereço</legend>
                <div class="form-group"><label for="cep">CEP</label><input type="text" id="cep" name="cep" required></div>
                <div class="form-group"><label for="endereco">Endereço</label><input type="text" id="endereco" name="endereco" required></div>
                <div class="form-group"><label for="numero">Número</label><input type="text" id="numero" name="numero" required></div>
                <div class="form-group"><label for="bairro">Bairro</label><input type="text" id="bairro" name="bairro" required></div>
                <div class="form-group"><label for="cidade">Cidade</label><input type="text" id="cidade" name="cidade" required></div>
                <div class="form-group"><label for="estado">Estado (UF)</label><input type="text" id="estado" name="estado" maxlength="2" required></div>
                <div class="form-navigation">
                    <button type="button" class="nav-btn btn-prev">&larr; Anterior</button>
                    <button type="button" class="nav-btn btn-next">Próximo &rarr;</button>
                </div>
            </fieldset>
            
            <fieldset class="form-step">
                <legend>Dados de Pagamento (PIX)</legend>
                <div class="form-group"><label for="pix_key_type">Tipo de Chave PIX</label><select name="pix_key_type" id="pix_key_type" required><option value="">Selecione um tipo</option><option value="cpf">CPF</option><option value="email">E-mail</option><option value="telefone">Telefone</option><option value="aleatoria">Chave Aleatória</option></select></div>
                <div class="form-group"><label for="pix_key">Chave PIX</label><input type="text" id="pix_key" name="pix_key" required></div>
                <div class="form-navigation">
                    <button type="button" class="nav-btn btn-prev">&larr; Anterior</button>
                    <button type="button" class="nav-btn btn-next">Próximo &rarr;</button>
                </div>
            </fieldset>

            <fieldset class="form-step">
                <legend>Validação de Identidade</legend>
                <p style="font-size: 14px; color: #555;">Use a câmara para tirar uma foto nítida e bem iluminada dos seus documentos.</p>
                <div class="form-group"><label for="doc_frente">Frente do Documento (RG ou CNH)</label><input type="file" id="doc_frente" name="doc_frente" accept="image/*,application/pdf" capture="environment" required></div>
                <div class="form-group"><label for="doc_verso">Verso do Documento (se aplicável)</label><input type="file" id="doc_verso" name="doc_verso" accept="image/*,application/pdf" capture="environment"></div>
                <div class="form-group"><label for="selfie">Selfie (Foto do seu rosto)</label><input type="file" id="selfie" name="selfie" accept="image/*" capture="user" required></div>
                <div class="form-navigation">
                    <button type="button" class="nav-btn btn-prev">&larr; Anterior</button>
                    <button type="button" class="nav-btn btn-next">Próximo &rarr;</button>
                </div>
            </fieldset>

            <fieldset class="form-step">
                <legend>Dados de Acesso</legend>
                <div class="form-group">
                    <label for="username">Crie o seu @Username</label>
                    <div class="input-group-prepend">
                        <span class="input-group-text">@</span>
                        <input type="text" id="username" name="username" required pattern="[a-zA-Z0-9_]+" title="Apenas letras (sem acentos), números e _" placeholder="ex: joao_silva">
                    </div>
                    <small style="color: #777;">Este será o seu identificador único na plataforma. Não pode conter espaços ou acentos.</small>
                </div>
                <div class="form-group"><label for="email">Seu E-mail (será o seu login)</label><input type="email" id="email" name="email" required></div>
                <div class="form-group">
                    <label for="password">Crie uma Senha</label>
                    <input type="password" id="password" name="password" required pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" title="A senha deve ter pelo menos 8 caracteres, incluindo um número, uma letra maiúscula e uma minúscula.">
                    <small style="color: #777;">Pelo menos 8 caracteres, 1 maiúscula, 1 minúscula e 1 número.</small>
                </div>
                <div class="form-group"><input type="checkbox" id="terms" name="terms" value="1" required><label for="terms" style="display:inline; font-weight:normal;">Eu li e aceito os <a href="/termos" target="_blank">Termos de Serviço</a>.</label></div>
                <div class="form-navigation">
                    <button type="button" class="nav-btn btn-prev">&larr; Anterior</button>
                    <button type="submit" class="nav-btn btn-next">Finalizar Registo</button>
                </div>
            </fieldset>

        </form>
    </div>

    <script src="/js/cep.js"></script>
    <script src="/js/validators.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const nextBtns = document.querySelectorAll('.btn-next');
            const prevBtns = document.querySelectorAll('.btn-prev');
            const formSteps = document.querySelectorAll('.form-step');
            const progressSteps = document.querySelectorAll('.progress-step');
            const errorMessageContainer = document.getElementById('form-error-message');
            let currentStep = 0;

            nextBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    if (btn.type !== 'submit') {
                        if (validateStep(currentStep)) {
                            currentStep++;
                            updateFormSteps();
                        }
                    }
                });
            });

            prevBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    currentStep--;
                    updateFormSteps();
                });
            });
            
            // Valida se todos os campos 'required' do passo atual estão preenchidos
            function validateStep(stepIndex) {
                const inputs = formSteps[stepIndex].querySelectorAll('input[required], select[required]');
                let allValid = true;
                errorMessageContainer.style.display = 'none'; // Esconde a mensagem de erro antiga

                inputs.forEach(input => {
                    let isValid = input.checkValidity(); // Usa a validação nativa do navegador
                    
                    if (input.type === 'checkbox' && !input.checked) {
                        isValid = false;
                    }

                    if (!isValid) {
                        allValid = false;
                        input.style.borderColor = 'red';
                    } else {
                        input.style.borderColor = 'var(--cor-borda)';
                    }
                });

                if (!allValid) {
                    // Mostra a mensagem de erro sem usar alert()
                    errorMessageContainer.textContent = 'Por favor, preencha todos os campos obrigatórios deste passo.';
                    errorMessageContainer.style.display = 'block';
                    errorMessageContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return allValid;
            }

            function updateFormSteps() {
                errorMessageContainer.style.display = 'none'; // Esconde a mensagem de erro ao trocar de passo
                formSteps.forEach((step, index) => {
                    step.classList.toggle('active', index === currentStep);
                });
                updateProgressBar();
            }

            function updateProgressBar() {
                progressSteps.forEach((step, index) => {
                    step.classList.toggle('active', index <= currentStep);
                });
            }
        });
    </script>
</body>
</html>
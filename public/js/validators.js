// Adiciona um "ouvinte" que espera o conteúdo da página carregar completamente
document.addEventListener('DOMContentLoaded', function() {
    
    // Encontra os campos de CPF e CNPJ pelos seus 'id's
    const cpfInput = document.getElementById('cpf');
    const cnpjInput = document.getElementById('cnpj');

    // Se o campo de CPF existir na página
    if (cpfInput) {
        cpfInput.addEventListener('blur', function() {
            validateField(this, validateCpf, '11.222.333/4444-55');
        });
    }

    // Se o campo de CNPJ existir na página
    if (cnpjInput) {
        cnpjInput.addEventListener('blur', function() {
            validateField(this, validateCnpj, '11.222.333/4444-55');
        });
    }

    // Função genérica para validar o campo e aplicar o estilo
    function validateField(inputElement, validationFunction) {
        const value = inputElement.value;
        if (validationFunction(value)) {
            // Válido: borda verde
            inputElement.style.borderColor = '#198754'; // Verde
            inputElement.style.boxShadow = '0 0 5px rgba(25, 135, 84, 0.5)';
        } else if (value.length > 0) {
            // Inválido (e não está vazio): borda vermelha
            inputElement.style.borderColor = '#dc3545'; // Vermelho
            inputElement.style.boxShadow = '0 0 5px rgba(220, 53, 69, 0.5)';
        } else {
            // Campo vazio: reseta o estilo
            inputElement.style.borderColor = '#ccc';
            inputElement.style.boxShadow = 'none';
        }
    }

    // --- Funções de Validação de CPF e CNPJ ---

    function validateCpf(cpf) {
        cpf = cpf.replace(/[^\d]+/g, '');
        if (cpf === '' || cpf.length !== 11 || /^(\d)\1+$/.test(cpf)) return false;
        let add = 0;
        for (let i = 0; i < 9; i++) add += parseInt(cpf.charAt(i)) * (10 - i);
        let rev = 11 - (add % 11);
        if (rev === 10 || rev === 11) rev = 0;
        if (rev !== parseInt(cpf.charAt(9))) return false;
        add = 0;
        for (let i = 0; i < 10; i++) add += parseInt(cpf.charAt(i)) * (11 - i);
        rev = 11 - (add % 11);
        if (rev === 10 || rev === 11) rev = 0;
        if (rev !== parseInt(cpf.charAt(10))) return false;
        return true;
    }

    function validateCnpj(cnpj) {
        cnpj = cnpj.replace(/[^\d]+/g, '');
        if (cnpj === '' || cnpj.length !== 14 || /^(\d)\1+$/.test(cnpj)) return false;
        let size = cnpj.length - 2;
        let numbers = cnpj.substring(0, size);
        let digits = cnpj.substring(size);
        let sum = 0;
        let pos = size - 7;
        for (let i = size; i >= 1; i--) {
            sum += numbers.charAt(size - i) * pos--;
            if (pos < 2) pos = 9;
        }
        let result = sum % 11 < 2 ? 0 : 11 - sum % 11;
        if (result != digits.charAt(0)) return false;
        size = size + 1;
        numbers = cnpj.substring(0, size);
        sum = 0;
        pos = size - 7;
        for (let i = size; i >= 1; i--) {
            sum += numbers.charAt(size - i) * pos--;
            if (pos < 2) pos = 9;
        }
        result = sum % 11 < 2 ? 0 : 11 - sum % 11;
        if (result != digits.charAt(1)) return false;
        return true;
    }
});
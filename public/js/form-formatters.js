document.addEventListener('DOMContentLoaded', function() {
    
    // Encontra o campo de valor pelo seu ID
    const currencyInput = document.getElementById('valor_minimo_turno_6h');

    if (currencyInput) {
        // Adiciona um evento que é disparado quando o utilizador sai do campo
        currencyInput.addEventListener('blur', function() {
            formatCurrency(this);
        });
    }

    /**
     * Formata o valor de um campo para o padrão monetário brasileiro (xx,xx)
     * @param {HTMLInputElement} inputElement O elemento do formulário a ser formatado.
     */
    function formatCurrency(inputElement) {
        let value = inputElement.value;

        // Se o campo estiver vazio, não faz nada
        if (!value) return;

        // Limpa o valor para ter apenas números, substituindo vírgula por ponto
        let number = parseFloat(value.replace(',', '.'));

        // Se o valor não for um número válido, limpa o campo
        if (isNaN(number)) {
            inputElement.value = '';
            return;
        }

        // A lógica especial para o seu caso "5520" -> "55,20"
        // Se o valor não tem casas decimais e é maior que 100, divide por 100
        if (!value.includes(',') && !value.includes('.') && number >= 100) {
            number = number / 100;
        }

        // Formata o número para ter sempre duas casas decimais
        const formattedValue = number.toFixed(2);

        // Substitui o ponto pela vírgula para exibição
        inputElement.value = formattedValue.replace('.', ',');
    }
});
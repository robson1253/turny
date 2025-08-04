document.addEventListener('DOMContentLoaded', function() {
    
    const cepInput = document.getElementById('cep');

    // Função auxiliar para definir o valor de um campo de forma segura
    function setFieldValue(id, value) {
        const element = document.getElementById(id);
        if (element) { // Só tenta definir o valor SE o elemento for encontrado
            element.value = value;
        }
    }

    // Função para limpar todos os campos de endereço
    function clearAddressFields() {
        setFieldValue('endereco', '');
        setFieldValue('bairro', '');
        setFieldValue('cidade', '');
        setFieldValue('estado', '');
    }

    // Continua apenas se o campo CEP existir na página
    if (cepInput) {
        cepInput.addEventListener('blur', function() {
            const cep = this.value.replace(/\D/g, '');

            if (cep.length === 8) {
                // Define os campos para '...' de forma segura
                setFieldValue('endereco', '...');
                setFieldValue('bairro', '...');
                setFieldValue('cidade', '...');
                setFieldValue('estado', '...');

                // Faz a chamada à nossa API interna
                fetch(`/api/cep-lookup?cep=${cep}`)
                    .then(response => response.json())
                    .then(data => {
                        if (!data.erro) {
                            // Preenche os campos de endereço de forma segura
                            setFieldValue('endereco', data.logradouro);
                            setFieldValue('bairro', data.bairro);
                            setFieldValue('cidade', data.localidade);
                            setFieldValue('estado', data.uf);
                        } else {
                            alert('CEP não encontrado.');
                            clearAddressFields();
                        }
                    })
                    .catch(error => {
                        console.error("Erro ao buscar o CEP:", error);
                        clearAddressFields();
                    });
            } else if (cep.length > 0) {
                clearAddressFields();
            }
        });
    }
});
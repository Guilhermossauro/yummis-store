document.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    const error = params.get('error');

    if (error) {
        let msg = '';
        switch (error) {
            case 'empty_fields': msg = 'Por favor, preencha todos os campos.'; break;
            case 'invalid_credentials': msg = 'E-mail ou senha inválidos.'; break;
            case 'system_error': msg = 'Erro interno. Tente novamente mais tarde.'; break;
            default: msg = 'Ocorreu um erro. Verifique os dados e tente novamente.';
        }
        if (msg) showError(msg);
    }

    document.querySelector('form').addEventListener('submit', function(e) {
        const email = document.querySelector('input[name="email"]').value;
        const senha = document.querySelector('input[name="password"]').value;

        if (email.trim() === "" || senha.trim() === "") {
            e.preventDefault();
            showError("Por favor, preencha todos os campos.");
        }
    });
});

function showError(message) {
    const errorDiv = document.querySelector('.error-msg');
    if (errorDiv) {
        errorDiv.textContent = message; // Use textContent para evitar XSS
        errorDiv.style.display = 'block';
    } else {
        alert(message);
    }
}
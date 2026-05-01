document.querySelector('form').addEventListener('submit', function(e) {
    const email = document.querySelector('input[name="email"]').value;
    const senha = document.querySelector('input[name="password"]').value;
    const errorDiv = document.querySelector('.error-msg');

    // Validação básica antes de enviar
    if (email.trim() === "" || senha.trim() === "") {
        e.preventDefault(); // Impede o envio do formulário
        showError("Por favor, preencha todos os campos.");
    }
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
document.addEventListener('DOMContentLoaded', () => {
    const btnNovoPedido = document.getElementById('btnNovoPedido');
    const modal = document.getElementById('modalPedido');
    const btnClose = document.querySelector('.close-modal');

    // Abre o modal
    btnNovoPedido.addEventListener('click', () => {
        modal.classList.add('active');
    });

    // Fecha o modal pelo 'X'
    btnClose.addEventListener('click', () => {
        modal.classList.remove('active');
    });

    // Fecha o modal clicando fora dele (no fundo escuro)
    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.remove('active');
        }
    });
});
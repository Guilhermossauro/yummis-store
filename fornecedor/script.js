// fornecedor/script.js

document.addEventListener('DOMContentLoaded', function() {
    // Navegação da sidebar
    const navItems = document.querySelectorAll('.nav-item');
    const contentSections = document.querySelectorAll('.content-section');

    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();

            // Remove active class from all nav items
            navItems.forEach(nav => nav.classList.remove('active'));
            // Add active class to clicked item
            this.classList.add('active');

            // Hide all sections
            contentSections.forEach(section => section.classList.remove('active'));

            // Show selected section
            const sectionId = this.getAttribute('data-section');
            const targetSection = document.getElementById(sectionId);
            if (targetSection) {
                targetSection.classList.add('active');
            }

            // Update page title
            const pageTitle = document.querySelector('.page-title');
            if (pageTitle) {
                pageTitle.textContent = this.textContent.trim();
            }
        });
    });

    // Toggle sidebar
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');

    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }

    // Modal de proposta
    const propostaModal = document.getElementById('propostaModal');
    const propostaForm = document.getElementById('propostaForm');
    const modalCloseBtn = document.querySelector('#propostaModal .modal-close');

    if (modalCloseBtn) {
        modalCloseBtn.addEventListener('click', fecharModal);
    }

    if (propostaForm) {
        propostaForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        fetch('../api/enviar_proposta.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Proposta enviada com sucesso!');
                fecharModal();
                location.reload(); // Recarregar para atualizar dados
            } else {
                alert('Erro: ' + (data.error || 'Erro desconhecido'));
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao enviar proposta');
        });
        });
    }
});

// Funções globais
function enviarProposta(pedidoId) {
    document.getElementById('pedido_id').value = pedidoId;
    document.getElementById('propostaModal').classList.add('active');
}

function fecharModal() {
    document.getElementById('propostaModal').classList.remove('active');
    const form = document.getElementById('propostaForm');
    if (form) form.reset();
}

function verDetalhes(pedidoId) {
    // Implementar visualização detalhada do pedido
    alert('Funcionalidade em desenvolvimento: Ver detalhes do pedido ' + pedidoId);
}

function editarProposta(propostaId) {
    // Implementar edição de proposta
    alert('Funcionalidade em desenvolvimento: Editar proposta ' + propostaId);
}

function deletarProposta(propostaId) {
    if (confirm('Tem certeza que deseja excluir esta proposta?')) {
        // Implementar exclusão
        alert('Funcionalidade em desenvolvimento: Deletar proposta ' + propostaId);
    }
}

function refreshCotacoes() {
    location.reload();
}

// Fechar modal ao clicar fora
window.onclick = function(event) {
    const modal = document.getElementById('propostaModal');
    if (event.target === modal) {
        fecharModal();
    }
}
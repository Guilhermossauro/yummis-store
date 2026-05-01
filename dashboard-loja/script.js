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

// ================= FUNÇÕES DE PROPOSTAS =================

// Função para mostrar seções
function showSection(sectionName) {
    // Esconder todas as seções
    document.querySelectorAll('.content-section').forEach(section => {
        section.style.display = 'none';
    });

    // Mostrar seção específica
    const targetSection = document.getElementById(sectionName);
    if (targetSection) {
        targetSection.style.display = 'block';
    }

    // Atualizar menu ativo
    document.querySelectorAll('.menu a').forEach(link => {
        link.classList.remove('active');
    });

    if (sectionName === 'propostas') {
        document.querySelector('a[href="#propostas"]').classList.add('active');
    } else {
        document.querySelector('a[href="index.php"]').classList.add('active');
    }
}

// Função para comparar propostas de um pedido
function compararPropostas(pedidoId) {
    showSection('propostas');
    // Scroll para o pedido específico
    const pedidoCard = document.querySelector(`[data-pedido-id="${pedidoId}"]`);
    if (pedidoCard) {
        pedidoCard.scrollIntoView({ behavior: 'smooth' });
    }
}

// Função para aprovar proposta
async function aprovarProposta(propostaId, pedidoId) {
    if (!confirm('Tem certeza que deseja aprovar esta proposta? Todas as outras propostas serão rejeitadas automaticamente.')) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('proposta_id', propostaId);
        formData.append('pedido_id', pedidoId);

        const response = await fetch('../api/aprovar_proposta.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            alert(result.message);
            location.reload(); // Recarregar página para atualizar status
        } else {
            alert('Erro: ' + result.message);
        }
    } catch (error) {
        console.error('Erro ao aprovar proposta:', error);
        alert('Erro ao processar a solicitação');
    }
}

// Função para rejeitar proposta
async function rejeitarProposta(propostaId) {
    if (!confirm('Tem certeza que deseja rejeitar esta proposta?')) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('proposta_id', propostaId);

        const response = await fetch('../api/rejeitar_proposta.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            alert(result.message);
            location.reload(); // Recarregar página para atualizar status
        } else {
            alert('Erro: ' + result.message);
        }
    } catch (error) {
        console.error('Erro ao rejeitar proposta:', error);
        alert('Erro ao processar a solicitação');
    }
}

// Função para contatar fornecedor (abre chat)
function contatarFornecedor(fornecedorId) {
    // Implementar abertura do chat com fornecedor específico
    window.location.href = `chat.php?fornecedor=${fornecedorId}`;
}
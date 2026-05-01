window.addEventListener('load', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');

    if (status) {
        let msg = "";
        let isError = false;

        switch (status) {
            case 'sucesso': msg = "Cotação criada com sucesso!"; break;
            case 'sucesso_edit': msg = "Cotação atualizada com sucesso!"; break;
            case 'sucesso_delete': msg = "Cotação deletada permanentemente."; break;
            case 'erro_produto': msg = "Erro: O nome do produto é obrigatório."; isError = true; break;
            case 'erro_db': msg = "Erro interno de conexão. Tente novamente."; isError = true; break;
        }

        if (msg) showToast(msg, isError);
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});

function showToast(message, isError = false) {
    const toast = document.createElement('div');
    toast.textContent = message;
    Object.assign(toast.style, {
        position: 'fixed', bottom: '20px', right: '20px',
        background: isError ? '#dc2626' : '#16a34a', color: 'white',
        padding: '15px 25px', borderRadius: '8px', boxShadow: '0 4px 6px rgba(0,0,0,0.1)',
        fontWeight: 'bold', zIndex: '9999', opacity: '0', transition: '0.3s', transform: 'translateY(20px)'
    });
    document.body.appendChild(toast);
    setTimeout(() => { toast.style.opacity = '1'; toast.style.transform = 'translateY(0)'; }, 10);
    setTimeout(() => {
        toast.style.opacity = '0'; toast.style.transform = 'translateY(20px)';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ----------------------------------------
// MODAL DE EDIÇÃO (Com carregamento das fotos antigas)
// ----------------------------------------
function abrirModalEditar(pedido) {
    document.getElementById('edit_id').value = pedido.id;
    document.getElementById('edit_produto').value = pedido.produto_nome;
    document.getElementById('edit_descricao').value = pedido.descricao;
    
    // Limpa o dropzone da edição antes de abrir
    if (window.dropzoneEdit) window.dropzoneEdit.clear();

    // Lógica mágica: Transforma URLs do servidor em arquivos reais na memória
    if (pedido.imagem_url) {
        try {
            const imagens = JSON.parse(pedido.imagem_url);
            if (imagens && imagens.length > 0) {
                Promise.all(imagens.map(img => 
                    fetch('../' + img)
                    .then(res => res.blob())
                    .then(blob => {
                        const fileName = img.split('/').pop();
                        return new File([blob], fileName, { type: blob.type || 'image/jpeg' });
                    })
                )).then(files => {
                    files.forEach(f => window.dropzoneEdit.addFile(f));
                }).catch(err => console.error("Erro ao carregar miniaturas antigas", err));
            }
        } catch(e) { console.error("Erro no JSON de imagens"); }
    }
    
    document.getElementById('modalEditar').classList.add('active');
}

// ----------------------------------------
// MODAL VER DETALHES (Agora exibe galeria de imagens)
// ----------------------------------------
function abrirModalVer(pedido) {
    document.getElementById('ver_produto').innerText = pedido.produto_nome;
    document.getElementById('ver_status').innerText = pedido.status.toUpperCase();
    document.getElementById('ver_status').className = 'status ' + pedido.status;
    document.getElementById('ver_descricao').innerText = pedido.descricao || "Nenhuma especificação adicional fornecida.";
    
    const container = document.getElementById('ver_imagem_container');
    container.innerHTML = ''; // Limpa antigas
    
    // Alinha a galeria no centro e permite quebra de linha se forem muitas fotos
    container.style.display = 'flex';
    container.style.flexWrap = 'wrap';
    container.style.gap = '10px';
    container.style.justifyContent = 'center';
    container.style.marginBottom = '20px';

    if (pedido.imagem_url) {
        try {
            const imagens = JSON.parse(pedido.imagem_url);
            if (imagens && imagens.length > 0) {
                imagens.forEach(img => {
                    const imgTag = document.createElement('img');
                    imgTag.src = '../' + img;
                    imgTag.className = 'view-product-image';
                    container.appendChild(imgTag);
                });
            } else {
                container.style.display = 'none';
            }
        } catch(e) { container.style.display = 'none'; }
    } else {
        container.style.display = 'none';
    }
    
    document.getElementById('modalVer').classList.add('active');
}

function fecharModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}
document.addEventListener('DOMContentLoaded', () => {
    // 1. Lógica do Menu Hamburger Mobile
    const hamburger = document.getElementById('hamburger');
    const sidebar = document.getElementById('sidebar');

    if (hamburger && sidebar) {
        hamburger.addEventListener('click', () => {
            hamburger.classList.toggle('open');
            sidebar.classList.toggle('active');
        });

        // Clica fora do menu para fechar
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 992 && !sidebar.contains(e.target) && !hamburger.contains(e.target)) {
                sidebar.classList.remove('active');
                hamburger.classList.remove('open');
            }
        });
    }

    // 2. Gráfico Chart.js (Analytics de Exemplo Visual)
    const ctx = document.getElementById('analyticsChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'],
                datasets: [{
                    label: 'Arquivos Enviados',
                    data: [12, 19, 15, 25, 22, 30],
                    backgroundColor: '#3b82f6',
                    borderRadius: 5,
                    barPercentage: 0.5
                }, {
                    label: 'Propostas Recebidas',
                    data: [8, 15, 10, 18, 15, 20],
                    backgroundColor: '#06b6d4',
                    borderRadius: 5,
                    barPercentage: 0.5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { labels: { color: '#94a3b8' } } },
                scales: {
                    y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#94a3b8' } },
                    x: { grid: { display: false }, ticks: { color: '#94a3b8' } }
                }
            }
        });
    }

    function updateStorageDisplays() {
        const progressFill = document.querySelector('.storage-box .progress-fill');
        if (progressFill) {
            progressFill.style.width = progressFill.dataset.percentage || '0%';
        }

        const circle = document.querySelector('.storage-circular .circle');
        if (circle) {
            const rotation = Number(circle.dataset.rotation) || 0;
            const masks = circle.querySelectorAll('.mask.full, .mask.full .fill, .mask.half .fill');
            masks.forEach((el) => {
                el.style.transform = `rotate(${rotation}deg)`;
            });
        }
    }

    updateStorageDisplays();
});
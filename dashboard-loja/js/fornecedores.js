document.addEventListener('DOMContentLoaded', () => {
    // Modal Novo Fornecedor
    const btnNovo = document.getElementById('btnNovoFornecedor');
    const modalNovo = document.getElementById('modalNovoFornecedor');
    if(btnNovo && modalNovo) {
        btnNovo.addEventListener('click', () => {
            if(window.dropzoneNovoForn) window.dropzoneNovoForn.clear();
            modalNovo.classList.add('active');
        });
    }

    // Fechar modais
    document.querySelectorAll('.close-modal').forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = btn.closest('.modal');
            if(modal) modal.classList.remove('active');
        });
    });

    // ==========================================
    // DROPZONE PARA FOTO ÚNICA (PERFIL/LOGO)
    // ==========================================
    window.setupDropzoneSingle = function(dropzoneId, fileInputId, previewContainerId, formSelector) {
        const dropzone = document.getElementById(dropzoneId);
        const fileInput = document.getElementById(fileInputId);
        const previewContainer = document.getElementById(previewContainerId);
        const form = document.querySelector(formSelector);

        let arquivoUpload = null; 

        if (!dropzone || !fileInput) return null;

        dropzone.addEventListener('click', () => fileInput.click());
        dropzone.addEventListener('dragover', (e) => { e.preventDefault(); dropzone.classList.add('dragover'); });
        dropzone.addEventListener('dragleave', () => dropzone.classList.remove('dragover'));
        dropzone.addEventListener('drop', (e) => {
            e.preventDefault(); 
            dropzone.classList.remove('dragover'); 
            if(e.dataTransfer.files.length > 0) processarArquivo(e.dataTransfer.files[0]);
        });
        
        fileInput.addEventListener('change', (e) => {
            if(e.target.files.length > 0) processarArquivo(e.target.files[0]);
        });

        function processarArquivo(file) {
            if (!file) return;

            const nome = (file.name || '').toLowerCase();
            const extensaoValida = /\.(jpg|jpeg|png|gif|webp)$/i.test(nome);
            const mimeValido = (file.type || '').startsWith('image/');

            if (mimeValido || extensaoValida) {
                arquivoUpload = file;
                renderizarPreview();
            } else {
                alert('⚠️ Formato inválido! Por favor, selecione apenas arquivos de imagem (PNG, JPG, GIF ou WEBP).');
                if (fileInput) fileInput.value = '';
            }
        }

        function renderizarPreview() {
            if (!previewContainer) return;
            previewContainer.innerHTML = '';
            
            if (arquivoUpload) {
                const div = document.createElement('div');
                div.className = 'preview-item avatar'; 

                const imgUrl = URL.createObjectURL(arquivoUpload);
                
                div.innerHTML = `
                    <img src="${imgUrl}" alt="Preview" class="preview-avatar-image">
                    <button type="button" class="remove-preview" onclick="removerArquivo_${dropzoneId}(event)">✖</button>
                `;
                previewContainer.appendChild(div);
            }
        }

        window[`removerArquivo_${dropzoneId}`] = (event) => {
            event.stopPropagation();
            arquivoUpload = null;
            if(fileInput) fileInput.value = ''; 
            renderizarPreview();
        };

        if (form) {
            form.addEventListener('submit', (e) => {
                if (arquivoUpload) {
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(arquivoUpload);
                    fileInput.files = dataTransfer.files;
                }
            });
        }

        return {
            addFile: (file) => { arquivoUpload = file; renderizarPreview(); },
            clear: () => { arquivoUpload = null; renderizarPreview(); }
        };
    };

    // Inicializa os Dropzones
    window.dropzoneNovoForn = setupDropzoneSingle('dropzoneFornNovo', 'foto_perfil_novo', 'previewFornNovo', '#modalNovoFornecedor form');
    window.dropzoneEditForn = setupDropzoneSingle('dropzoneFornEdit', 'foto_perfil_edit', 'previewFornEdit', '#modalEditarFornecedor form');
});

// Controle de Abas (Submenus)
function openTab(evt, tabId) {
    evt.preventDefault();
    const modalContent = evt.target.closest('.modal-content');
    
    const contents = modalContent.querySelectorAll('.tab-content');
    contents.forEach(c => c.classList.remove('active'));
    
    const buttons = modalContent.querySelectorAll('.tab-btn');
    buttons.forEach(b => b.classList.remove('active'));
    
    document.getElementById(tabId).classList.add('active');
    evt.currentTarget.classList.add('active');
}

// Abrir Edição e popular dados
function abrirModalEditarFornecedor(fornecedor) {
    document.getElementById('edit_id').value = fornecedor.id;
    document.getElementById('edit_nome').value = fornecedor.nome;
    document.getElementById('edit_email').value = fornecedor.email;
    document.getElementById('edit_documento').value = fornecedor.documento || '';
    document.getElementById('edit_endereco').value = fornecedor.endereco || '';
    
    const permEnviar = document.getElementById('perm_enviar');
    const permVerOutros = document.getElementById('perm_ver_outros');
    
    permEnviar.checked = false;
    permVerOutros.checked = false;

    if (fornecedor.permissoes) {
        try {
            const perms = JSON.parse(fornecedor.permissoes);
            if(perms.enviar_proposta) permEnviar.checked = true;
            if(perms.ver_concorrencia) permVerOutros.checked = true;
        } catch(e) {}
    }

    if (window.dropzoneEditForn) window.dropzoneEditForn.clear();
    if (fornecedor.foto_perfil) {
        fetch('../' + fornecedor.foto_perfil)
            .then(res => res.blob())
            .then(blob => {
                const fileName = fornecedor.foto_perfil.split('/').pop();
                const file = new File([blob], fileName, { type: blob.type || 'image/jpeg' });
                window.dropzoneEditForn.addFile(file);
            }).catch(err => console.error("Erro ao carregar miniatura antiga", err));
    }

    document.querySelector('#modalEditarFornecedor .tab-btn').click();
    document.getElementById('modalEditarFornecedor').classList.add('active');
}

function fecharModal(id) {
    document.getElementById(id).classList.remove('active');
}
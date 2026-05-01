document.addEventListener('DOMContentLoaded', () => {
    const btnNovoPedido = document.getElementById('btnNovoPedido');
    const modalPedido = document.getElementById('modalPedido');
    const btnsClose = document.querySelectorAll('.close-modal');

    // Abre modal de Novo Pedido
    if(btnNovoPedido && modalPedido) {
        btnNovoPedido.addEventListener('click', () => modalPedido.classList.add('active'));
    }

    // Fecha os modais
    btnsClose.forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = btn.closest('.modal');
            if(modal) modal.classList.remove('active');
        });
    });

    window.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal')) e.target.classList.remove('active');
    });

    // ==========================================
    // FUNÇÃO GERADORA DE DROPZONES INDEPENDENTES
    // ==========================================
    window.setupDropzone = function(dropzoneId, fileInputId, previewContainerId, formSelector) {
        const dropzone = document.getElementById(dropzoneId);
        const fileInput = document.getElementById(fileInputId);
        const previewContainer = document.getElementById(previewContainerId);
        const form = document.querySelector(formSelector);

        let arquivosUpload = [];

        if (!dropzone || !fileInput) return null;

        dropzone.addEventListener('click', () => fileInput.click());
        dropzone.addEventListener('dragover', (e) => { e.preventDefault(); dropzone.classList.add('dragover'); });
        dropzone.addEventListener('dragleave', () => dropzone.classList.remove('dragover'));
        dropzone.addEventListener('drop', (e) => {
            e.preventDefault(); dropzone.classList.remove('dragover'); processarArquivos(e.dataTransfer.files);
        });
        
        fileInput.addEventListener('change', (e) => processarArquivos(e.target.files));

        function processarArquivos(files) {
            Array.from(files).forEach(file => {
                if (file.type.startsWith('image/')) arquivosUpload.push(file);
            });
            renderizarPreviews();
        }

        function renderizarPreviews() {
            if (!previewContainer) return;
            previewContainer.innerHTML = '';
            
            arquivosUpload.forEach((file, index) => {
                const div = document.createElement('div');
                div.className = 'preview-item';
                div.draggable = true;
                div.dataset.index = index;

                const imgUrl = URL.createObjectURL(file);
                
                div.innerHTML = `
                    <img src="${imgUrl}" alt="Preview">
                    <button type="button" class="remove-preview" onclick="removerArquivo_${dropzoneId}(${index}, event)">✖</button>
                `;

                div.addEventListener('dragstart', iniciarArrasto);
                div.addEventListener('dragover', (e) => e.preventDefault());
                div.addEventListener('drop', soltarArrasto);

                previewContainer.appendChild(div);
            });
        }

        // Função global dinâmica para deletar da miniatura correta
        window[`removerArquivo_${dropzoneId}`] = (index, event) => {
            event.stopPropagation();
            arquivosUpload.splice(index, 1);
            renderizarPreviews();
        };

        let indexArrastado = null;

        function iniciarArrasto(e) {
            indexArrastado = +this.dataset.index;
            this.classList.add('dragging');
        }

        function soltarArrasto(e) {
            e.preventDefault();
            previewContainer.querySelectorAll('.preview-item').forEach(item => item.classList.remove('dragging'));
            
            const indexAlvo = +this.dataset.index;
            if (indexArrastado !== null && indexArrastado !== indexAlvo) {
                const itemRemovido = arquivosUpload.splice(indexArrastado, 1)[0];
                arquivosUpload.splice(indexAlvo, 0, itemRemovido);
                renderizarPreviews();
            }
        }

        if (form) {
            form.addEventListener('submit', (e) => {
                const dataTransfer = new DataTransfer();
                arquivosUpload.forEach(file => dataTransfer.items.add(file));
                fileInput.files = dataTransfer.files;
            });
        }

        // Retorna métodos para usarmos no dashboard.js
        return {
            addFile: (file) => { arquivosUpload.push(file); renderizarPreviews(); },
            clear: () => { arquivosUpload = []; renderizarPreviews(); }
        };
    };

    // Inicializa os dois Dropzones (Novo Pedido e Edição)
    window.dropzoneNovo = setupDropzone('dropzone', 'imagem_produto', 'previewContainer', '#modalPedido form');
    window.dropzoneEdit = setupDropzone('dropzoneEdit', 'imagem_produto_edit', 'previewContainerEdit', '#modalEditar form');
});
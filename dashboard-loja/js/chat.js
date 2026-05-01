document.addEventListener('DOMContentLoaded', () => {
    const contacts = document.querySelectorAll('.contact-item');
    const emptyChat = document.getElementById('emptyChat');
    const activeChat = document.getElementById('activeChat');
    
    const chatHeaderName = document.getElementById('chatHeaderName');
    const chatHeaderAvatar = document.getElementById('chatHeaderAvatar');
    const destinatarioIdInput = document.getElementById('destinatarioId');
    const respostaIdInput = document.getElementById('respostaId');
    const chatMessages = document.getElementById('chatMessages');
    const messageInput = document.getElementById('messageInput');
    const btnSendMessage = document.getElementById('btnSendMessage');
    
    // Elementos do Reply e Context Menu
    const replyPreview = document.getElementById('replyPreview');
    const replyTextPreview = document.getElementById('replyTextPreview');
    const btnCloseReply = document.getElementById('closeReply');
    const contextMenu = document.getElementById('chatContextMenu');

    let chatInterval = null; 
    let msgSelecionadaId = null;
    let msgSelecionadaTexto = '';
    let msgSelecionadaIsMine = false;

    // 1. Clicar em um Fornecedor
    contacts.forEach(contact => {
        contact.addEventListener('click', () => {
            contacts.forEach(c => c.classList.remove('active'));
            contact.classList.add('active');

            const id = contact.dataset.id;
            chatHeaderName.textContent = contact.dataset.nome;
            chatHeaderAvatar.src = contact.dataset.avatar;
            destinatarioIdInput.value = id;

            emptyChat.style.display = 'none';
            activeChat.style.display = 'flex';

            // Remove a bolinha vermelha e avisa o servidor que leu
            const badge = document.getElementById('badge-' + id);
            if(badge) {
                badge.remove();
                fetch(`../api/chat_handler.php?acao=marcar_lidas&remetente_id=${id}`);
            }

            carregarMensagens(id);

            if (chatInterval) clearInterval(chatInterval);
            chatInterval = setInterval(() => carregarMensagens(id), 3000);
            fecharMenu();
        });
    });

    // 2. Enviar Mensagem
    btnSendMessage.addEventListener('click', enviarMensagem);
    messageInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            enviarMensagem();
        }
    });

    function enviarMensagem() {
        const texto = messageInput.value.trim();
        const destinatarioId = destinatarioIdInput.value;
        const respostaId = respostaIdInput.value;

        if (texto === '' || destinatarioId === '') return;

        messageInput.value = '';
        fecharReply();

        const formData = new FormData();
        formData.append('destinatario_id', destinatarioId);
        formData.append('mensagem', texto);
        if(respostaId) formData.append('resposta_a', respostaId);

        fetch('../api/chat_handler.php?acao=enviar', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => { if(data.success) carregarMensagens(destinatarioId); });
    }

    // 3. Carregar Mensagens
    function carregarMensagens(destinatarioId) {
        fetch(`../api/chat_handler.php?acao=carregar&destinatario_id=${destinatarioId}`)
        .then(response => response.json())
        .then(mensagens => {
            // Guarda a altura do scroll para não jogar a tela pra baixo se a gente tiver subido lendo o histórico
            const scrollBottom = chatMessages.scrollHeight - chatMessages.scrollTop - chatMessages.clientHeight;
            
            chatMessages.innerHTML = ''; 

            if(mensagens.length === 0) {
                chatMessages.innerHTML = '<div style="text-align: center; color: #64748b; margin-top: 20px;">Nenhuma mensagem ainda. Mande um olá!</div>';
                return;
            }

            mensagens.forEach(msg => {
                const isMine = (parseInt(msg.remetente_id) === MEU_ID);
                const classe = isMine ? 'sent' : 'received';
                const dataFormatada = new Date(msg.data_envio).toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'});

                // Se for resposta a alguém, monta a citação
                let quoteHtml = '';
                if(msg.msg_respondida) {
                    quoteHtml = `<div class="quote-box">${msg.msg_respondida}</div>`;
                }

                const div = document.createElement('div');
                div.className = `message ${classe}`;
                div.dataset.id = msg.id;
                div.dataset.texto = msg.mensagem;
                div.dataset.mine = isMine;
                
                div.innerHTML = `
                    <div class="text">
                        ${quoteHtml}
                        ${msg.mensagem}
                    </div>
                    <div class="time">${dataFormatada} ${isMine && msg.lida == 1 ? '<span style="color:#0ea5e9;">✓✓</span>' : ''}</div>
                `;
                chatMessages.appendChild(div);
            });

            // Só joga pra baixo automaticamente se a gente já estivesse no fundo da tela
            if(scrollBottom < 50) {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        });
    }

    // ==========================================
    // LÓGICA DO MENU DE CONTEXTO (BOTÃO DIREITO)
    // ==========================================
    document.addEventListener('contextmenu', (e) => {
        const msgEl = e.target.closest('.message');
        if (msgEl) {
            e.preventDefault(); // Impede o menu do Chrome de abrir
            
            msgSelecionadaId = msgEl.dataset.id;
            msgSelecionadaTexto = msgEl.dataset.texto;
            msgSelecionadaIsMine = (msgEl.dataset.mine === 'true');

            // Esconde opções de editar/deletar se a mensagem for do fornecedor
            const btnMineOnly = document.querySelectorAll('.mine-only');
            btnMineOnly.forEach(btn => btn.style.display = msgSelecionadaIsMine ? 'block' : 'none');

            // Posiciona o menu no mouse
            contextMenu.style.display = 'block';
            contextMenu.style.left = `${e.pageX}px`;
            contextMenu.style.top = `${e.pageY}px`;
        } else {
            fecharMenu();
        }
    });

    document.addEventListener('click', fecharMenu);

    function fecharMenu() { contextMenu.style.display = 'none'; }

    // ==========================================
    // AÇÕES DO MENU
    // ==========================================
    
    // A. RESPONDER
    document.getElementById('menuReply').addEventListener('click', () => {
        respostaIdInput.value = msgSelecionadaId;
        replyTextPreview.textContent = msgSelecionadaTexto;
        replyPreview.style.display = 'flex';
        messageInput.focus();
    });

    btnCloseReply.addEventListener('click', fecharReply);
    function fecharReply() {
        replyPreview.style.display = 'none';
        respostaIdInput.value = '';
    }

    // B. EDITAR (Apenas minhas)
    document.getElementById('menuEdit').addEventListener('click', () => {
        if(!msgSelecionadaIsMine) return;
        const novoTexto = prompt("Edite sua mensagem:", msgSelecionadaTexto);
        if (novoTexto && novoTexto.trim() !== '' && novoTexto !== msgSelecionadaTexto) {
            const formData = new FormData();
            formData.append('id', msgSelecionadaId);
            formData.append('novo_texto', novoTexto.trim());

            fetch('../api/chat_handler.php?acao=editar', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => { if(data.success) carregarMensagens(destinatarioIdInput.value); });
        }
    });

    // C. DELETAR (Apenas minhas)
    document.getElementById('menuDelete').addEventListener('click', () => {
        if(!msgSelecionadaIsMine) return;
        if(confirm("Tem certeza que deseja apagar esta mensagem para todos?")) {
            const formData = new FormData();
            formData.append('id', msgSelecionadaId);

            fetch('../api/chat_handler.php?acao=deletar', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => { if(data.success) carregarMensagens(destinatarioIdInput.value); });
        }
    });

    // Busca rápida
    document.getElementById('searchContacts').addEventListener('input', function(e) {
        const termo = e.target.value.toLowerCase();
        contacts.forEach(c => {
            const nome = c.dataset.nome.toLowerCase();
            c.style.display = nome.includes(termo) ? 'flex' : 'none';
        });
    });
});
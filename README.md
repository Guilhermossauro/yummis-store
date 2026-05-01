# Lyumios Supply (Yummis Store)

## Visão Geral

Lyumios Supply é um sistema SaaS B2B para gestão de compras, cotações e suprimentos entre lojas (compradores) e fornecedores (vendedores).

## Arquitetura Atual

- Backend: PHP puro com APIs organizadas em `/api/`.
- Frontend: HTML5, CSS3, JavaScript Vanilla com Fetch/AJAX.
- Design: Tema Dark Premium com glassmorphism e UI corporativa.
- Segurança: PDO, validação de sessão, log de auditoria via `api/logger.php`.

## Status Atual

O sistema atual foi capturado em um único commit inicial contendo toda a estrutura existente, páginas, scripts, estilos e APIs.

## Plano de Desenvolvimento por Etapa

Cada etapa abaixo será trabalhada como um commit documentado e separado para criar um histórico claro de evolução.

## Etapa 1: Portal do Parceiro (Lado do Fornecedor)

- Dashboard com KPIs de desempenho: total de propostas enviadas, taxa de aprovação e valor negociado.
- Feed de cotações para fornecedores visualizarem pedidos abertos pelas lojas.
- Formulário de envio de propostas com preço, prazo e upload de anexos (PDFs e imagens).
- Ocultação de preços concorrentes conforme permissões JSON do usuário.
- Rastreamento de status de propostas: Em análise, Visualizada, Ganha, Perdida.

### Justificativa

Esta etapa organiza o fluxo de fornecedores e assegura que tudo seja tratado via APIs no /api/, com logs e segurança em cada ação.


## Etapa 2: Fechamento de Negócios (Lado da Loja)

- Interface de comparação de propostas lado a lado para a loja avaliar ofertas.
- Workflow de aprovação/recusa com ação automática para recusar demais propostas quando uma for aceita.
- Cálculo de economia mostrando a diferença entre a proposta escolhida e a mais cara.

### Justificativa

Esta etapa aumenta a eficiência de decisão de compra e garante consistência no processo de aprovação de propostas.


## Etapa 3: Autenticação e Configurações Base

- Validação de sessão em todas as páginas e APIs via session_start().
- Meu Perfil para edição de dados, troca de senha e upload de logo ou foto.
- Notificações in-app via sininho para alertas de nova proposta e cotações encerradas.

### Justificativa

Estes recursos são a base de uso diário e segurança, garantindo controle de acesso e experiência personalizada para cada usuário.


## Etapa 4: Dinâmica de Negociação Avançada

- Deadlines para propostas que bloqueiam submissões após data de validade final.
- Timeline visual de auditoria do pedido mostrando ações e status em sequência.
- Chat contextual por pedido, separado do chat geral, para negociações com histórico de cotação.

### Justificativa

A negociação avançada melhora transparência e compliance, permitindo que todas as partes acompanhem prazos e histórico da negociação.


## Etapa 5: Produtividade e Agilidade

- Funcionalidade de duplicar pedido para recompra rápida a partir de cotações anteriores.
- Catálogo de produtos frequentes para criar cotações com um clique.
- Estrutura inicial de PWA com manifest.json para comportamento app-like em dispositivos móveis.

### Justificativa

Estas melhorias visam acelerar processos repetitivos e transformar o sistema em uma experiência mais mobile-friendly e produtiva.


## Etapa 6: Relacionamento, Confiança e Relatórios

- Sistema de rating 1-5 estrelas para a loja avaliar o fornecedor após entrega.
- Perfil público do fornecedor com média de notas e ficha técnica.
- Filtros avançados e paginação para busca inteligente em pedidos e propostas.
- Exportação de relatórios CSV/Excel para gastos e vendas.
- Modo Admin com visibilidade global, usuários banidos e acesso a logs.

### Justificativa

Esses recursos fortalecem confiança, suporte comercial e governança SaaS para operações B2B de alto valor.


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


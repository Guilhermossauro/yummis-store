# Lyumios Supply (Yummis Store)

> Plataforma SaaS B2B de gestão de compras, cotações e suprimentos entre Lojas (compradores) e Fornecedores (vendedores).

[![PHP Version](https://img.shields.io/badge/PHP-8.1+-blue.svg)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-orange.svg)](https://www.mysql.com/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![GitHub Issues](https://img.shields.io/github/issues/Guilhermossauro/yummis-store)](https://github.com/Guilhermossauro/yummis-store/issues)
[![GitHub Stars](https://img.shields.io/github/stars/Guilhermossauro/yummis-store)](https://github.com/Guilhermossauro/yummis-store/stargazers)

---

## 📋 Sumário

- [🌐 Sobre o sistema](#-sobre-o-sistema)
- [🏗 Arquitetura técnica](#-arquitetura-técnica)
- [🔐 Segurança e compliance](#-segurança-e-compliance)
- [🚀 Estado atual](#-estado-atual)
- [📌 Histórico de commits relevantes](#-histórico-de-commits-relevantes)
- [📂 Estrutura do projeto](#-estrutura-do-projeto)
- [⚙️ Como executar localmente](#️-como-executar-localmente)
- [✨ Próximos passos](#-próximos-passos)
- [Contato](#contato)

---

## 🌐 Sobre o sistema

Lyumios Supply é uma solução corporativa desenhada para tornar a negociação B2B eficiente, segura e auditável.
O produto suporta o fluxo completo de pedidos, propostas, chat contextual e auditoria para operações entre lojas e fornecedores.

### Recursos principais

- UI Dark Premium com glassmorphism e navegação fluida
- Backend em PHP puro com APIs dedicadas em `api/`
- Frontend em HTML5, CSS3 e JavaScript Vanilla
- Autenticação robusta e controle de sessão em todas as rotinas
- Auditoria obrigatória via `api/logger.php`
- Estrutura pronta para expansão em módulos de relatório e administração

---

## 🏗 Arquitetura técnica {#arquitetura-técnica}

| Camada | Tecnologia | Descrição |
|---|---|---|
| Backend | PHP + PDO | Operações seguras com prepared statements |
| Frontend | HTML5, CSS3, Vanilla JS | Sem frameworks externos |
| APIs | `/api/*.php` | Microsserviços leves e isolados |
| Logs | `api/logger.php` | Auditoria de ações, falhas e acessos |
| DB | MySQL | Suporta `usuarios`, `pedidos`, `propostas` e `mensagens` |

---

## 🔐 Segurança e compliance {#segurança-e-compliance}

- Validação de sessão com `session_start()` em todas as páginas e APIs
- Proteção SQL Injection via PDO `prepare()` / `execute()`
- Sistema de logs para todas as ações críticas
- Controle de acesso por `tipo_usuario` (`loja` / `fornecedor`)
- Painel de logs oculto com senha mestre para auditoria interna

---

## 🚀 Estado atual {#estado-atual}

O sistema atual inclui:

- Painel de loja com criação, edição e exclusão de pedidos
- Cadastro e gestão de fornecedores
- Upload de imagens para pedidos e perfis
- Chat interno com histórico e marcação de mensagens lidas
- Login/logout com proteção de sessão e regeneração de ID
- API centralizada para autenticação e auditoria

---

## 📌 Histórico de commits relevantes {#histórico-de-commits-relevantes}

O projeto possui commits detalhados com explicações de cada etapa e ajustes técnicos.

- `efacc75` — Initial commit: sistema atual Lyumios Supply / Yummis Store
- `ab13b52` — docs: add project README and roadmap overview for Lyumios Supply
- `bfd3d24` — roadmap: document stage 1 - supplier portal, proposal feed, pricing permissions
- `3d06264` — roadmap: document stage 2 - proposal comparison and approval workflow for buyers
- `1981c99` — roadmap: document stage 3 - auth, profile settings, in-app notifications
- `13b288b` — roadmap: document stage 4 - deadlines, order timeline, contextual chat
- `c76147d` — roadmap: document stage 5 - reorder duplication, frequent catalog, PWA setup
- `cdc3c9b` — roadmap: document stage 6 - ratings, supplier profiles, advanced filtering, reports, admin mode
- `1c8ab50` — fix: centralize API auth and add backend audit logging for all CRUD and access operations

---

## 📂 Estrutura do projeto {#estrutura-do-projeto}

- `api/` — APIs de backend, autenticação e auditoria
- `config/` — configuração de banco e sistema
- `dashboard-loja/` — painel da loja, scripts e estilos
- `fornecedor/` — portal do fornecedor
- `login/` — interface de autenticação
- `uploads/` — arquivos enviados por usuários

---

## ⚙️ Como executar localmente {#como-executar-localmente}

1. Configure PHP + MySQL
2. Crie a base de dados `lyumios_supply`
3. Atualize as credenciais em `config/db.php`
4. Acesse `http://localhost/Yummis_store`

---

## ✨ Próximos passos {#próximos-passos}

- Concluir o portal completo do fornecedor
- Construir comparação de propostas lado a lado para lojas
- Integrar notificações in-app e timeline de auditoria
- Adicionar relatórios de gastos e vendas

---

## Contato {#contato}

Projeto mantido pela equipe Lyumios Supply. Para próximas entregas, mantenha o README atualizado com o progresso de desenvolvimento.

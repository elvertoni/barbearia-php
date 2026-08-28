# Roadmap

O projeto começou como peça de portfólio focada na regra de conflito de agendamento
(barbeiro + cadeira simultâneos). Em **2026-08-28** virou um **produto SaaS de gestão para
barbearia**, inspirado no [AppBarber](https://appbarber.com.br) (web + app), entregue por
módulos incrementais. A stack **PHP puro, sem framework**, foi mantida por decisão — as
peças de infraestrutura (router, DI, migrations, sessão, middleware) são construídas à mão.

**Regra de ouro:** um módulo por vez, completo (código + testes + documentação). Não
antecipar módulo futuro nem largar um módulo pela metade.

---

## Fundação (entregue antes da virada)

CRUD de barbeiros / cadeiras / serviços / clientes · horário de trabalho por barbeiro ·
compatibilidade cadeira↔serviço · criação de agendamento com as 7 regras de conflito
(validação de janela, compatibilidade, sobreposição em SQL, concorrência via `FOR UPDATE`) ·
agenda por barbeiro/dia · cancelamento com sinalização de fila de espera · máquina de
estados de status · camada HTTP própria (router, request/response, view) · Docker Compose.

## Módulo 1 — Auth + multi-profissional ✅

- Sessão nativa (cookie `HttpOnly`/`SameSite=Lax`, `Secure` em prod).
- Perfis: `dono`, `recepcao`, `barbeiro`, `cliente`.
- RBAC via pipeline de middleware própria no `Router` (`Autenticar`, `AutorizarPerfil`).
- CSRF global em POST/PUT/DELETE, campo `_token` injetado automaticamente pelo `View`.
- `password_hash` / `password_verify`; rate limit de login (5 falhas / 15 min por e-mail).
- `barbeiros.usuario_id` liga o recurso agendável ao login; barbeiro opera só a própria agenda (`/minha-agenda`).
- Gestão de usuários (`/usuarios`, só dono); seed idempotente do primeiro dono.

## Módulo 2 — Portal do cliente + booking público

Cadastro/login do cliente · página pública de agendamento self-service · escolha de
serviço/barbeiro/horário com os slots livres calculados pela regra de conflito · histórico
de agendamentos do cliente · entrada na fila de espera pelo próprio cliente.

## Módulo 3 — Comanda + financeiro + comissão

Comanda por atendimento (serviços + produtos consumidos) · fechamento de comanda ·
formas de pagamento e taxa de cartão · fluxo de caixa diário · comissão e vale por
profissional · pacotes de serviço.

## Módulo 4 — Notificações

Fila assíncrona (worker próprio) · lembrete de horário · confirmação de agendamento ·
aviso de vaga na fila de espera · mensagem de aniversário · canais e-mail / SMS / WhatsApp
atrás de uma interface de driver.

## Módulo 5 — Fidelidade

Programa de pontos resgatáveis · clube VIP com desconto automático · cashback ·
pesquisa de satisfação pós-atendimento.

## Módulo 6 — Estoque

Produtos com custo e preço · saldo, entrada e baixa · alerta de mínimo e de validade ·
relatório de valorização de estoque.

## Módulo 7 — Relatórios e dashboards

Faturamento e movimento por dia/hora/canal · ranking de serviços · desempenho por
profissional · retenção de clientes.

## Módulo 8 — Multi-unidade

Loja como entidade · usuários e agenda por loja · relatórios consolidados.

## Módulo 9 — App mobile

PWA primeiro (instalável, push via web) · app nativo depois, se necessário.

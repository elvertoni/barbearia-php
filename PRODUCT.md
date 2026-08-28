# Product

<!-- impeccable:product-schema 2 -->

## Platform

web

## Users

Produto SaaS de gestão para barbearia. Perfis de usuário:

1. **Dono** — acesso total, inclusive gestão de usuários e configuração.
2. **Recepção** — operação do dia a dia: agenda, cadastros (barbeiros, serviços, cadeiras, clientes), fila de espera.
3. **Barbeiro** — enxerga e opera apenas a própria agenda (confirmar/iniciar/concluir/no-show dos próprios atendimentos).
4. **Cliente** — portal self-service de agendamento (a ser entregue no módulo 2).

## Product Purpose

Sistema de gestão para barbearia inspirado no AppBarber (web + app), entregue por módulos: agenda, equipe, comanda/financeiro, comissão, fidelidade, notificações, estoque, relatórios. O núcleo técnico sobre o qual tudo é construído é resolver corretamente o conflito de dois recursos simultâneos — barbeiro E cadeira — que um agendamento consome ao mesmo tempo, verificado em SQL e com lock de concorrência.

## Positioning

Produto real construído sem framework por decisão: as peças que Laravel/Symfony resolveriam por baixo (router, DI, migrations, sessão, middleware, verificação de concorrência) são implementadas à mão, de forma enxuta e correta. Roadmap de módulos em `ROADMAP.md`.

## Operating Context

Fluxo operacional: login (por perfil) → cadastro de barbeiros e vínculo com usuário de login → horário de trabalho por barbeiro → cadastro de cadeiras e compatibilidade cadeira↔serviço → cadastro de serviços → cadastro de clientes → criação de agendamento (valida janela de trabalho, compatibilidade de cadeira, conflito de horário em SQL, concorrência via lock) → agenda por barbeiro/dia (recepção) ou "Minha Agenda" (barbeiro) → cancelamento (com sinalização de fila de espera) → transições de status (solicitado→confirmado→em_atendimento→concluído, ou →cancelado/no_show).

Rodando localmente via Docker Compose (`docker compose up -d --build`), acessado em `localhost:8080`. Setup: `migrate.php` aplica o schema, `seed.php` cria o usuário dono a partir de `ADMIN_EMAIL`/`ADMIN_SENHA`.

## Capabilities and Constraints

Entregue por módulos incrementais (`ROADMAP.md`) — não antecipar módulo futuro nem deixar módulo em andamento pela metade. Módulo 1 (auth + multi-profissional) entregue; próximos: portal do cliente, comanda/financeiro/comissão, notificações, fidelidade, estoque, relatórios, multi-unidade, PWA.

Constraints técnicas que a interface deve respeitar: preço sempre em centavos (nunca float na exibição), datas armazenadas em UTC com conversão pra `America/Sao_Paulo` na apresentação, toda variável renderizada passa por escape (`e()`) contra XSS, todo formulário mutante carrega token CSRF (injetado automaticamente pelo `View`), rotas protegidas por perfil via middleware.

## Brand Commitments

Nenhuma. "Barbearia" é nome de projeto/placeholder — não existe negócio real, nome de marca, logo ou identidade visual comprometida por trás. Liberdade total pra definir identidade visual.

## Evidence on Hand

Nenhum dado real. Todo conteúdo no banco (barbeiros, serviços, preços, clientes) é sintético/seed de teste. Trabalho futuro não deve fabricar depoimentos, clientes reais, cases ou métricas de negócio — usar dados de exemplo claramente genéricos.

## Product Principles

1. A regra de conflito (barbeiro + cadeira, verificada em SQL, com lock de concorrência) é o coração do produto — qualquer decisão de UI que a torne menos visível ou compreensível é regressão.
2. Entrega por módulos: um módulo por vez, completo (código + testes + docs), sem antecipar o próximo nem deixar o atual pela metade.
3. Sem framework é decisão mantida — as peças de infraestrutura são construídas à mão e documentadas.
4. Sem dado real disponível: exemplos e conteúdo de demonstração devem ser reconhecíveis como genéricos, nunca fabricados como se fossem reais.

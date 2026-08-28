# 💈 Sistema de Gestão para Barbearia

Sistema de gestão para barbearia em **PHP puro (sem framework)**, inspirado no AppBarber
(web + app) e entregue por módulos incrementais. Ver [`ROADMAP.md`](ROADMAP.md).

O núcleo técnico é a regra de conflito de agendamento — barbeiro **e** cadeira consumidos
ao mesmo tempo, verificada em SQL com lock de concorrência — sobre a qual os demais
módulos são construídos.

## 🎯 Por que sem framework?

A ausência de framework é uma **decisão de projeto mantida**. As peças que Laravel, Symfony
e similares resolvem por baixo são construídas à mão, de forma enxuta e correta:

| Conceito de Framework | Implementação Própria |
|---|---|
| Router (Symfony Routing) | `src/Http/Router.php` — regex com parâmetros dinâmicos |
| Request/Response (PSR-7) | `src/Http/Request.php`, `src/Http/Response.php` |
| DI Container | DI manual em `public/index.php` (composição explícita) |
| ORM/Query Builder | PDO puro com prepared statements em repositórios |
| Migrations (Doctrine) | SQL puro versionado + script runner (`database/migrate.php`) |
| Template Engine (Blade) | PHP nativo com helper `e()` para XSS |
| Sessão / Auth (Laravel Auth) | `src/Http/Auth/SessaoAtual.php` sobre `$_SESSION` nativa |
| Middleware (pipeline HTTP) | `src/Http/Middleware/` + pipeline por rota no `Router` |
| Proteção CSRF | token na sessão + injeção automática no `View` + checagem no dispatch |

## 🏗 Arquitetura

```
src/
  Domain/           ← Entidades, Enums, Exceções, Interfaces de Repository
  │                    (zero dependência de infraestrutura — NENHUM import de PDO)
  │
  Application/      ← Use Cases que orquestram domínio + repositórios
  │                    (CriarAgendamento, CancelarAgendamento, etc.)
  │
  Infrastructure/   ← Implementação PDO dos repositórios, conexão de banco
  │
  Http/             ← Controllers finos, Router, Request/Response, Views
  
public/
  index.php         ← Front controller único

tests/
  Unit/             ← Testes sem banco (máquina de estados, enums)
  Integration/      ← Testes com banco real (conflitos, concorrência)

database/
  migrations/       ← SQL puro versionado (001_ a 008_)
```

**Regra arquitetural:** Dependências sempre apontam para dentro. `Domain/` não conhece `Infrastructure/`.

## 🧠 Regra de Negócio Central

O coração do sistema é o **conflito de dois recursos simultâneos**: um agendamento consome um **barbeiro** e uma **cadeira** ao mesmo tempo. O horário só é válido se **ambos** estiverem livres.

### 7 Regras Implementadas

1. **Dupla dimensão de conflito** — Barbeiro E cadeira verificados
2. **Compatibilidade cadeira↔serviço** — Nem toda cadeira serve para todo serviço
3. **Janela de trabalho** — Barbeiro só atende no horário cadastrado
4. **Verificação em SQL** — Sobreposição de intervalos via query, nunca loop em PHP
5. **Concorrência** — `SELECT ... FOR UPDATE` impede dupla reserva
6. **Máquina de estados** — Transições validadas: solicitado→confirmado→em_atendimento→concluído
7. **Fila de espera** — Sinalizada automaticamente ao cancelar

### Query de Conflito (Código Crítico)

```sql
SELECT COUNT(*) FROM agendamentos
WHERE barbeiro_id = :barbeiro_id
  AND status NOT IN ('cancelado', 'no_show')
  AND hora_inicio < :novo_fim
  AND hora_fim > :novo_inicio
FOR UPDATE
```

### Índices de Performance

Criados conscientemente para que a query de conflito escale:
- `idx_agendamentos_barbeiro (barbeiro_id, hora_inicio, hora_fim)`
- `idx_agendamentos_cadeira (cadeira_id, hora_inicio, hora_fim)`

## 🚀 Setup (um comando)

```bash
docker compose up -d --build
```

Depois, aplique o schema e crie o usuário dono:

```bash
docker compose exec app php database/migrate.php
docker compose exec app php database/seed.php
```

O `seed.php` lê `ADMIN_NOME` / `ADMIN_EMAIL` / `ADMIN_SENHA` (definidos no
`docker-compose.yml` para desenvolvimento). Acesse **http://localhost:8080** e entre com
essas credenciais.

## 🧪 Testes

```bash
# Todos os testes
docker compose exec app vendor/bin/phpunit

# Apenas unitários (sem banco)
docker compose exec app vendor/bin/phpunit --testsuite Unit

# Apenas integração (requer banco)
docker compose exec app vendor/bin/phpunit --testsuite Integration
```

### Casos de Borda Testados

| Caso | Resultado Esperado | Teste |
|---|---|---|
| Sobreposição total | ❌ Falha | `testSobreposicaoTotalFalha` |
| Sobreposição parcial (início) | ❌ Falha | `testSobreposicaoParcialNoInicioFalha` |
| Sobreposição parcial (fim) | ❌ Falha | `testSobreposicaoParcialNoFimFalha` |
| Horários adjacentes | ✅ Sucesso | `testHorariosAdjacentesPermitidos` |
| Cadeira ocupada (outro barbeiro) | ❌ Falha | `testConflitoCadeiraComDoisBarbeiros` |
| Cadeira incompatível | ❌ Falha | `testCadeiraIncompativelComServicoFalha` |
| Fora da janela de trabalho | ❌ Falha | `testForaDaJanelaDeTrabalhoFalha` |
| Concorrência (dupla reserva) | ❌ 1 falha | `testDuasReservasSimultaneasApenasUmaSucede` |

## 🔐 Autenticação e perfis (Módulo 1)

| Perfil | Acesso |
|---|---|
| **dono** | Tudo, inclusive gestão de usuários (`/usuarios`) |
| **recepcao** | Agenda, cadastros, fila de espera |
| **barbeiro** | Só a própria agenda (`/minha-agenda`); transita status apenas dos próprios agendamentos |
| **cliente** | Portal self-service (Módulo 2) |

- Sessão nativa PHP, cookie `HttpOnly`/`SameSite=Lax` (+`Secure` com `APP_ENV=prod`).
- Senha com `password_hash`; rate limit de 5 falhas / 15 min por e-mail.
- CSRF em todo POST/PUT/DELETE — o campo `_token` é injetado automaticamente pelo `View`.
- RBAC por pipeline de middleware registrada por rota no `Router`.

## 📋 Decisões Técnicas

| Decisão | Justificativa |
|---|---|
| **MySQL 8** (vs PostgreSQL) | Mais comum no ecossistema PHP. `FOR UPDATE` demonstra tratamento explícito de concorrência. |
| **Migrations SQL puro** (vs Phinx) | Coerente com a proposta "sem framework". Script runner com 60 linhas. |
| **Preço em centavos** (int) | Nunca `float` para valores monetários — evita erros de arredondamento. |
| **UTC no banco** | Datas em UTC, conversão na apresentação para `America/Sao_Paulo`. |
| **DI manual** | Sem container de framework — composição explícita em `index.php`. |

## 🗺 Módulos

O produto é entregue por módulos — detalhes em [`ROADMAP.md`](ROADMAP.md).

| # | Módulo | Status |
|---|---|---|
| — | Fundação: agenda + regra de conflito + camada HTTP | ✅ |
| 1 | Auth + multi-profissional (perfis, RBAC, CSRF, rate limit, agenda do barbeiro) | ✅ |
| 2 | Portal do cliente + booking público | ⏳ |
| 3 | Comanda + financeiro + comissão | ⏳ |
| 4 | Notificações (fila assíncrona) | ⏳ |
| 5 | Fidelidade (pontos / clube VIP) | ⏳ |
| 6 | Estoque | ⏳ |
| 7 | Relatórios e dashboards | ⏳ |
| 8 | Multi-unidade | ⏳ |
| 9 | App mobile (PWA primeiro) | ⏳ |

Não implementado antes do módulo correspondente: gateway de pagamento real, app nativo.

## 📁 Stack

- PHP 8.2+
- MySQL 8.0
- Docker Compose
- PHPUnit 10
- Zero frameworks

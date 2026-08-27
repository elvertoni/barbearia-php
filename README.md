# 💈 Sistema de Agendamento para Barbearia

Sistema de agendamento de serviços para barbearia em **PHP puro (sem framework)**, construído como peça de portfólio técnico que demonstra competência de engenharia em escopo deliberadamente pequeno.

## 🎯 Por que sem framework?

A ausência de framework é **proposital**. O objetivo é demonstrar que entendo o que Laravel, Symfony e similares resolvem por baixo, construindo versões enxutas e corretas dessas peças:

| Conceito de Framework | Implementação Própria |
|---|---|
| Router (Symfony Routing) | `src/Http/Router.php` — regex com parâmetros dinâmicos |
| Request/Response (PSR-7) | `src/Http/Request.php`, `src/Http/Response.php` |
| DI Container | DI manual em `public/index.php` (composição explícita) |
| ORM/Query Builder | PDO puro com prepared statements em repositórios |
| Migrations (Doctrine) | SQL puro versionado + script runner (`database/migrate.php`) |
| Template Engine (Blade) | PHP nativo com helper `e()` para XSS |

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

Depois, rode as migrations:

```bash
docker compose exec app php database/migrate.php
```

Acesse: **http://localhost:8080**

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

## 📋 Decisões Técnicas

| Decisão | Justificativa |
|---|---|
| **MySQL 8** (vs PostgreSQL) | Mais comum no ecossistema PHP. `FOR UPDATE` demonstra tratamento explícito de concorrência. |
| **Migrations SQL puro** (vs Phinx) | Coerente com a proposta "sem framework". Script runner com 60 linhas. |
| **Preço em centavos** (int) | Nunca `float` para valores monetários — evita erros de arredondamento. |
| **UTC no banco** | Datas em UTC, conversão na apresentação para `America/Sao_Paulo`. |
| **DI manual** | Sem container de framework — composição explícita em `index.php`. |

## 🚫 Fora de Escopo (deliberado)

- Autenticação/login multi-usuário
- Notificações (e-mail/SMS/WhatsApp)
- Pagamento online
- Multi-loja
- Relatórios/dashboards
- App mobile

Cada item é uma decisão consciente de foco, não uma limitação técnica.

## 📁 Stack

- PHP 8.2+
- MySQL 8.0
- Docker Compose
- PHPUnit 10
- Zero frameworks

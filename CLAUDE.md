# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Idioma

Código, comentários, docblocks, nomes de classes/métodos, mensagens de commit e exceções são **em português**. Mantenha essa convenção — nomes de domínio (`Agendamento`, `Cadeira`, `FilaEspera`, `existeConflitoBarbeiro`) não devem ser traduzidos para inglês.

## Comandos

Tudo roda dentro do container `app` (PHP 8.2 + Apache).

```bash
docker compose up -d --build            # sobe app (porta 8080) + MySQL 8 (porta 3306)
docker compose exec app php database/migrate.php   # aplica migrations pendentes
docker compose exec app php database/seed.php      # cria usuário dono (ADMIN_EMAIL/ADMIN_SENHA); idempotente
docker compose exec app composer install
docker compose exec app composer dump-autoload    # após mexer no autoload.files do composer.json
```

Testes (PHPUnit 10):

```bash
docker compose exec app vendor/bin/phpunit                        # tudo
docker compose exec app vendor/bin/phpunit --testsuite Unit       # sem banco
docker compose exec app vendor/bin/phpunit --testsuite Integration # exige MySQL up + migrations
docker compose exec app vendor/bin/phpunit --filter testHorariosAdjacentesPermitidos   # um teste
docker compose exec app vendor/bin/phpunit tests/Unit/Domain/Entity/AgendamentoTest.php # um arquivo
```

Sem linter/formatter configurado. `phpunit.xml` usa `failOnRisky` e `failOnWarning` — warnings quebram a suíte.

## Arquitetura

Clean Architecture em PHP puro, **sem framework** por decisão de projeto mantida (o produto é entregue por módulos — ver `ROADMAP.md`; README.md tem o mapeamento "conceito de framework → implementação própria").

```
src/Domain/         entidades, enums, exceções, interfaces de repository
src/Application/    use cases (orquestram domínio + repositórios + transação)
src/Infrastructure/ repositórios PDO, Connection
src/Http/           Router, Request, Response, View, controllers finos, templates
src/Http/Auth/      SessaoAtual (wrapper de $_SESSION), ContextoAutenticacao
src/Http/Middleware/ Autenticar, AutorizarPerfil (pipeline rodado no dispatch)
public/index.php    front controller: session_start + DI manual + rotas com middleware + CSRF + dispatch
```

**Regra mandatória:** dependências apontam para dentro. `src/Domain/` não pode importar PDO nem nada de `Infrastructure/`. Controllers não contêm regra de negócio — delegam a use cases ou chamam repositórios direto para CRUD trivial.

### Regra de negócio central

Um agendamento consome **barbeiro E cadeira ao mesmo tempo**. Ambos precisam estar livres. As 7 regras estão especificadas em `PRD.md` (`<regras_de_negocio_criticas>`) e referenciadas por número nos docblocks do código (`// Regra 4: ...`) — ao mexer nessas partes, mantenha a numeração coerente com o PRD.

1. Dupla dimensão de conflito (barbeiro + cadeira)
2. Compatibilidade cadeira↔serviço (`cadeira_servico_compativel`)
3. Janela de trabalho do barbeiro (`barbeiro_horarios` por `dia_semana`)
4. Sobreposição verificada **em SQL**, nunca em loop PHP: `hora_inicio < :novo_fim AND hora_fim > :novo_inicio`
5. Concorrência via `SELECT ... FOR UPDATE` dentro de transação
6. Máquina de estados em `StatusAgendamento` / `Agendamento::transitar()`
7. Fila de espera sinalizada ao cancelar

`CriarAgendamentoUseCase` é o ponto de entrada dessa regra: valida janela → busca cadeiras compatíveis → abre transação → checa conflito de barbeiro → escolhe a primeira cadeira compatível livre → persiste como `solicitado`.

### Convenções que valem em todo o código

- **Idempotência de transação:** use cases checam `$pdo->inTransaction()` antes de `beginTransaction()` e só dão commit/rollback se foram eles que abriram. Isso permite que testes de integração envolvam tudo numa transação externa e façam rollback no `tearDown`. Preserve esse padrão em qualquer use case novo.
- **UTC no banco**, sempre. `Connection` força `SET time_zone = '+00:00'`; entidades criam datas com `new DateTimeZone('UTC')`. Conversão para `America/Sao_Paulo` só na apresentação.
- **Preço em centavos** (`int`), nunca `float`.
- **Entidades imutáveis:** propriedades `readonly` + factories `criar()` (novo, sem id) e `reconstituir()` (vindo do banco, sem revalidar transições). `comId()` devolve nova instância. Exceção: `$status`, mutado por `transitar()`.
- **Status que ocupam slot:** `StatusAgendamento::ocupaSlot()`; as queries de conflito excluem `cancelado` e `no_show`.

### Camada HTTP

- Rotas são registradas em `public/index.php`. `{param}` vira regex nomeado; `Router::resolve()` suporta override de método por campo de formulário `_method` (PUT/DELETE).
- Controllers retornam `Response` (`->html()`, `->json()`, `->redirect()`); o dispatch em `index.php` chama `send()`. Feedback ao usuário vai por query string (`?sucesso=...` / `?erro=...`), renderizada pelo layout.
- Templates são PHP nativo. Cada view começa com `ob_start()` e termina com `$content = ob_get_clean(); require __DIR__ . '/../layouts/main.php';`. Defina `$titulo` no topo. (`View::layout()` existe mas não é o caminho usado pelas views atuais.)
- **XSS:** escape toda variável com `e()` (`use function App\Http\e;`) — é função namespaced em `src/Http/View.php`, precisa do import em controllers e views.
- `src/Http/View` está excluído da cobertura em `phpunit.xml`.
- **Middleware por rota:** `$router->get($path, $handler, [$mw1, $mw2])` — cada middleware é `callable(Request): ?Response`; retornar `Response` corta a cadeia. O dispatch roda a pipeline antes do handler. Instâncias reutilizáveis (`$painel`, `$somenteDono`, ...) são montadas no topo de `index.php`.
- **CSRF:** validado globalmente no dispatch para POST/PUT/DELETE (`$_POST['_token']` vs `$_SESSION['_csrf']`). `View::render()` injeta `<input name="_token">` em todo `<form method="POST">` automaticamente — não adicione manualmente. Falha → HTTP 400.

### Autenticação e autorização

- **Sessão nativa** PHP (`session_start()` no topo do `index.php`), cookie `HttpOnly` + `SameSite=Lax`, `Secure` quando `APP_ENV=prod`. `SessaoAtual` (`src/Http/Auth/`) encapsula `$_SESSION`: `login()` faz `session_regenerate_id`, guarda só `usuario_id`/`perfil`/`nome`; `usuario()` recarrega a entidade sob demanda.
- **Perfis** (`PerfilUsuario`): `dono` (tudo + `/usuarios`), `recepcao` (operação), `barbeiro` (só `/minha-agenda` + transitar status próprio), `cliente` (portal — módulo 2). Valores batem com a coluna `usuarios.perfil`.
- **Senha:** `password_hash(PASSWORD_DEFAULT)` no `RegistrarUsuarioUseCase`; `Usuario::verificarSenha()` usa `password_verify`. A entidade nunca carrega senha em texto puro.
- **Rate limit de login:** `login_tentativas`, 5 falhas / 15 min por e-mail (`AutenticarUsuarioUseCase`).
- **Barbeiro ↔ recurso:** `barbeiros.usuario_id` (nullable, UNIQUE, FK) liga o `Usuario` de perfil barbeiro ao recurso agendável. `TransitarStatusUseCase` aceita `?int $restritoBarbeiroId` — o controller passa o id do barbeiro logado para impedir que ele opere agendamento alheio.
- Primeiro dono: `database/seed.php` (idempotente) a partir de `ADMIN_NOME`/`ADMIN_EMAIL`/`ADMIN_SENHA`.
- Ao adicionar rota nova em `index.php`, escolha a pipeline de middleware adequada — não deixe rota de painel sem `Autenticar` + `AutorizarPerfil`.

### Banco e migrations

SQL puro versionado em `database/migrations/NNN_*.sql`, aplicado por `database/migrate.php` (rastreia execuções na tabela `migrations`, para no primeiro erro). Migrations são **DDL apenas** — MySQL faz commit implícito em DDL, então não misture DML transacional ali. Para adicionar schema: crie o próximo arquivo numerado, não edite os existentes.

Índices `idx_agendamentos_barbeiro (barbeiro_id, hora_inicio, hora_fim)` e `idx_agendamentos_cadeira (...)` existem para a query de conflito — não os remova.

Credenciais vêm de variáveis de ambiente (`DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`), injetadas pelo `docker-compose.yml`; `Connection::create()` lança `RuntimeException` se faltarem. `APP_ENV` e `ADMIN_*` também vêm do compose.

`login_tentativas` compara contra `UTC_TIMESTAMP()` — a janela do rate limit não depende do time_zone da conexão.

### Testes

- `tests/Unit/` — sem banco (máquina de estados, enums, janela de horário, middleware com stub de `ContextoAutenticacao`).
- `tests/Integration/` — MySQL real. `CriarAgendamentoTest`, `CancelarAgendamentoTest`, `RegistrarUsuarioTest`, `AutenticarUsuarioTest`, `TransitarStatusRestritoTest` abrem transação no `setUp` e fazem rollback; `ConcorrenciaTest` usa **duas conexões PDO separadas** e limpa por range de data, portanto não pode rodar dentro de uma transação.
- Casos de borda cobertos (sobreposição total/parcial, horários adjacentes permitidos, cadeira ocupada por outro barbeiro, cadeira incompatível, fora da janela, dupla reserva simultânea) estão tabelados no README. Regra nova de conflito exige teste de borda escrito junto com a implementação.

## Escopo por módulo

O produto é entregue por módulos incrementais — ver `ROADMAP.md`. **Um módulo por vez, completo** (código + testes + docs). Não antecipe módulo futuro dentro de um módulo em andamento; não largue um módulo pela metade.

- **Módulo 1 — Auth + multi-profissional:** entregue.
- **Próximos:** portal do cliente + booking público → comanda + financeiro + comissão → notificações (fila assíncrona) → fidelidade → estoque → relatórios → multi-unidade → PWA.

Não implementar antes do módulo correspondente: gateway de pagamento real, app mobile nativo. Qualquer feature fora do módulo atual precisa de pedido explícito.

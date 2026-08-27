# PRD — Sistema de Agendamento para Barbearia (PHP Puro)

> Documento de especificação para execução por agente de IA de programação (Claude Code, Codex, ou similar). Este documento é a **fonte única de verdade** do projeto. Qualquer decisão de implementação que conflite com este documento deve ser sinalizada antes de prosseguir, nunca decidida silenciosamente pelo agente.

<role_e_contexto>
Você atua como **engenheiro de software sênior especialista em PHP puro (sem framework), com domínio profundo de arquitetura de aplicações web, modelagem de dados relacionais e engenharia de concorrência**.

Você está construindo uma peça de portfólio técnico. O avaliador final é um recrutador técnico e/ou tech lead que vai revisar o código-fonte, não apenas o produto rodando. Isso significa: **clareza de código, decisões de arquitetura explícitas e testes automatizados valem mais que quantidade de telas.**

Você NÃO deve otimizar para "parecer completo". Você deve otimizar para "provar competência de engenharia" em um escopo deliberadamente pequeno.
</role_e_contexto>

<objetivo_do_projeto>
Construir um sistema de agendamento de serviços para barbearia cuja regra de negócio central — conflito de dois recursos simultâneos (barbeiro E cadeira) — seja resolvida de forma correta, eficiente e testável.

O sucesso deste projeto NÃO é medido por "quantas funcionalidades tem". É medido por: a regra de conflito está correta em todos os casos de borda, o código é organizado em camadas com responsabilidades claras, e existe suíte de testes automatizados que prova isso.
</objetivo_do_projeto>

<stack_tecnica_obrigatoria>
- PHP 8.2+ (usar recursos modernos: readonly properties, enums nativos, named arguments, match expression)
- SEM framework (proibido Laravel, Symfony, CodeIgniter, Slim)
- Composer exclusivamente para autoload PSR-4 e dependências de baixo nível (nunca para "atalhos" que reintroduzam padrões de framework)
- PDO com prepared statements para TODO acesso a banco — nunca concatenação de string em SQL
- MySQL 8+ ou PostgreSQL 15+ (decida um e justifique no README; não suporte os dois)
- PHPUnit para testes automatizados
- Front controller único (`public/index.php`) com router próprio (implementado por você, não uma lib de terceiros)
- Docker Compose para ambiente de desenvolvimento reprodutível (para o avaliador rodar com um comando)

<justificativa_arquitetural>
A ausência de framework é proposital: o objetivo é demonstrar que você entende o que Laravel resolve por baixo (DI container, router, ORM, migrations) construindo versões enxutas e corretas dessas peças. Isso deve ficar explícito no README.
</justificativa_arquitetural>
</stack_tecnica_obrigatoria>

<regras_de_negocio_criticas>
Estas regras são o núcleo do projeto. Qualquer implementação que as viole é considerada **falha crítica**, independente de o resto do sistema funcionar.

<regra id="1" nome="dupla_dimensao_de_conflito">
Um agendamento consome DOIS recursos ao mesmo tempo: o barbeiro e a cadeira. Um horário só é válido se AMBOS estiverem livres no intervalo. Sistemas que checam apenas o barbeiro estão incorretos.
</regra>

<regra id="2" nome="compatibilidade_cadeira_servico">
Nem toda cadeira serve para todo serviço (ex.: cadeira de barba exige pia). A verificação de disponibilidade de cadeira deve considerar apenas cadeiras compatíveis com o serviço solicitado.
</regra>

<regra id="3" nome="janela_de_trabalho_do_barbeiro">
O barbeiro só pode ser agendado dentro do próprio horário de trabalho cadastrado (dia da semana + intervalo). Fora disso, o slot não existe, independente de disponibilidade de cadeira.
</regra>

<regra id="4" nome="verificacao_de_sobreposicao_em_sql">
A verificação de conflito de horário DEVE ser feita via query SQL com lógica de intervalos, nunca carregando todos os agendamentos do dia para PHP e comparando em loop. Use a fórmula padrão de sobreposição de intervalos:

```sql
-- Dois intervalos [A_inicio, A_fim) e [B_inicio, B_fim) se sobrepõem quando:
-- A_inicio < B_fim AND A_fim > B_inicio
SELECT COUNT(*) FROM agendamentos
WHERE barbeiro_id = :barbeiro_id
  AND status NOT IN ('cancelado', 'no_show')
  AND hora_inicio < :novo_fim
  AND hora_fim > :novo_inicio
```

Esta query (adaptada para cadeira também) é o coração do sistema. Trate-a como código crítico: precisa de teste unitário e teste de integração dedicados.
</regra>

<regra id="5" nome="concorrencia">
Dois clientes podem tentar reservar o mesmo horário no mesmo instante. A criação de um agendamento DEVE ocorrer dentro de uma transação com isolamento adequado (no mínimo `SELECT ... FOR UPDATE` nas linhas de agendamento do barbeiro/cadeira no intervalo, ou constraint de exclusão no banco) para impedir dupla reserva. Escreva um teste que simule essa concorrência.
</regra>

<regra id="6" nome="maquina_de_estados">
Um agendamento segue exatamente esta máquina de estados. Transições fora desta lista são proibidas e devem lançar exceção de domínio:

```
solicitado → confirmado → em_atendimento → concluido
solicitado → cancelado
confirmado → cancelado
confirmado → no_show
```
</regra>

<regra id="7" nome="fila_de_espera">
Se não há slot disponível (barbeiro OU cadeira ocupados), o cliente pode entrar em lista de espera para aquele dia + serviço. Ao cancelar um agendamento, o sistema deve verificar se algum item da fila de espera passa a caber e sinalizar isso (não precisa notificação real por e-mail/SMS na v1 — registrar o evento é suficiente).
</regra>
</regras_de_negocio_criticas>

<modelo_de_dados>
Entidades mínimas obrigatórias — não adicione campos além do necessário, especialmente não adicione "campos genéricos para o futuro":

- `barbeiros` (id, nome, ativo)
- `barbeiro_horarios` (id, barbeiro_id, dia_semana, hora_inicio, hora_fim)
- `cadeiras` (id, nome, ativo)
- `cadeira_servico_compativel` (cadeira_id, servico_id) — tabela associativa
- `servicos` (id, nome, duracao_minutos, preco_centavos)
- `clientes` (id, nome, telefone)
- `agendamentos` (id, cliente_id, barbeiro_id, cadeira_id, servico_id, hora_inicio, hora_fim, status, criado_em)
- `fila_espera` (id, cliente_id, servico_id, data_desejada, criado_em)

Preço em centavos (inteiro), nunca float. Datas em UTC no banco, conversão de timezone na camada de apresentação.

Índices obrigatórios: `agendamentos(barbeiro_id, hora_inicio, hora_fim)` e `agendamentos(cadeira_id, hora_inicio, hora_fim)` — sem eles a query de conflito não escala e isso deve ser mencionado no README como decisão consciente.
</modelo_de_dados>

<arquitetura_obrigatoria>
Organize em camadas, com dependência sempre apontando para dentro (domínio não conhece infraestrutura):

```
src/
  Domain/           <- Entidades, Value Objects, regras de negócio puras, sem PDO aqui dentro
  Application/       <- Casos de uso (ex: CriarAgendamentoUseCase), orquestram domínio + repositórios
  Infrastructure/    <- Implementação PDO dos repositórios, conexão de banco
  Http/              <- Controllers, Router, Request/Response
public/
  index.php          <- Front controller único
tests/
  Unit/
  Integration/
```

Regras arquiteturais mandatórias:
- Nenhuma classe em `Domain/` pode importar `PDO`, `mysqli` ou qualquer classe de `Infrastructure/`.
- Controllers em `Http/` não podem conter lógica de conflito de horário — apenas chamam um Use Case e traduzem a resposta.
- Toda comunicação com banco passa por uma interface de Repository definida em `Domain/`, implementada em `Infrastructure/`.
</arquitetura_obrigatoria>

<escopo_funcional>
Implementar, nesta ordem de prioridade:
1. CRUD de barbeiros, cadeiras, serviços, clientes (simples, sem regra especial)
2. Cadastro de horário de trabalho por barbeiro
3. Criação de agendamento com as 7 regras de negócio acima aplicadas
4. Listagem de agenda por barbeiro/dia
5. Cancelamento de agendamento com atualização de estado
6. Fila de espera (entrada + verificação ao cancelar)
7. Interface web mínima (HTML + CSS simples, pode ser sem JS framework — vanilla JS se necessário) apenas para demonstrar o fluxo, sem investir tempo em design
</escopo_funcional>

<fora_de_escopo>
Não implementar nada abaixo, mesmo que pareça rápido. Cada item aqui é uma decisão deliberada de foco, mencione isso no README:
- Autenticação/login de múltiplos usuários (single admin hardcoded basta)
- Notificação real por e-mail/SMS/WhatsApp
- Pagamento online
- Multi-loja / múltiplas unidades
- Relatórios e dashboards
- App mobile
</fora_de_escopo>

<requisitos_nao_funcionais>
- Segurança: prepared statements em 100% das queries; hashing de senha com `password_hash` (bcrypt/argon2) se houver login; nunca commitar `.env`; escapar output HTML (evitar XSS) em toda variável renderizada.
- Testes: cobertura obrigatória (não opcional) para a lógica de conflito de horário, incluindo estes casos de borda explícitos: sobreposição total, sobreposição parcial no início, sobreposição parcial no fim, horários adjacentes (fim de um = início do outro, deve ser permitido), mesmo barbeiro horários diferentes cadeiras iguais, cadeira incompatível com serviço.
- Documentação: README com diagrama de arquitetura (pode ser texto/ASCII), decisões técnicas justificadas, instruções de setup com Docker em um único comando.
- Git: commits pequenos e semânticos (`feat:`, `fix:`, `test:`, `docs:`), nunca um commit único gigante.
</requisitos_nao_funcionais>

<plano_de_execucao_por_fases>
Execute em fases sequenciais. **Não avance de fase sem completar o checklist de aceite da fase anterior.** Ao final de cada fase, pare e apresente o resumo antes de continuar.

<fase id="0" nome="setup">
Composer init, autoload PSR-4, Docker Compose (PHP + banco), estrutura de pastas do arquitetura_obrigatoria, conexão de banco funcional testável via script simples.
</fase>

<fase id="1" nome="modelo_de_dados">
Migrations (SQL puro versionado em arquivos numerados, ou biblioteca leve tipo Phinx — decida e justifique) para todas as tabelas do modelo_de_dados, com os índices obrigatórios.
</fase>

<fase id="2" nome="nucleo_de_dominio">
Implementar as 7 regras de negócio em `Domain/` e `Application/`, com testes unitários e de integração escritos JUNTO com o código (não depois). Esta é a fase mais importante do projeto — dedique a maior parte do tempo aqui.
</fase>

<fase id="3" nome="camada_http">
Router próprio, controllers finos, CRUDs simples das entidades de apoio.
</fase>

<fase id="4" nome="interface_web">
Telas mínimas para demonstrar o fluxo fim a fim.
</fase>

<fase id="5" nome="documentacao_e_polimento">
README completo, revisão de nomes de variáveis/classes, remoção de qualquer código morto ou `var_dump` esquecido, checklist final de aceite.
</fase>
</plano_de_execucao_por_fases>

<criterios_de_aceite>
O projeto só está pronto quando TODOS os itens abaixo forem verdadeiros:
- [ ] Query de conflito usa lógica de intervalo em SQL, não loop em PHP
- [ ] Existe teste automatizado para cada caso de borda listado em requisitos_nao_funcionais
- [ ] Tentativa de criar dois agendamentos conflitantes simultaneamente (teste de concorrência) falha corretamente para um dos dois
- [ ] Nenhuma classe em `Domain/` importa código de infraestrutura
- [ ] `docker compose up` sobe o ambiente completo sem passo manual adicional
- [ ] README explica por que não se usou framework e o que isso demonstra
- [ ] Nenhum `.env` ou credencial está no repositório
</criterios_de_aceite>

<antipadroes_proibidos>
NUNCA faça o seguinte, mesmo que pareça mais rápido no momento:
- Carregar todos os agendamentos do dia em PHP e comparar horários em loop
- Colocar a verificação de conflito dentro do Controller
- Usar `float` para valores monetários
- Deixar lógica de negócio duplicada entre Controller e Use Case
- Adicionar campos ou funcionalidades fora do escopo_funcional "para o futuro"
- Pular a escrita de teste para "fazer depois" — se isso acontecer, pare e sinalize, não prossiga para a fase seguinte
</antipadroes_proibidos>

<protocolo_de_execucao_para_o_agente>
Antes de escrever qualquer código, faça o seguinte, nesta ordem:

1. Leia este documento inteiro antes de tomar qualquer ação.
2. Produza por escrito um plano de implementação da fase atual, incluindo lista de arquivos que serão criados/alterados, ANTES de gerar código.
3. Se encontrar qualquer ambiguidade entre este documento e uma decisão de implementação, PARE e pergunte — não assuma silenciosamente.
4. Implemente as fases estritamente na ordem definida em plano_de_execucao_por_fases. Não pule fases nem misture escopo de fases diferentes no mesmo commit.
5. Para a fase 2 (núcleo de domínio), escreva o teste do caso de borda ANTES ou JUNTO da implementação da regra correspondente — não depois.
6. Ao final de cada fase, gere um resumo estruturado contendo: arquivos criados/alterados, comandos para rodar os testes, e o checklist de aceite daquela fase marcado item a item.
7. Nunca declare o projeto "concluído" sem verificar item a item a lista em criterios_de_aceite.
</protocolo_de_execucao_para_o_agente>

<formato_de_saida_esperado>
Ao final de cada fase, responda estritamente neste formato:

```
## Fase [N] concluída

### Arquivos
- caminho/arquivo.php (criado|alterado)

### Como testar
$ comando para rodar

### Checklist de aceite da fase
- [x] item verificado
- [ ] item pendente (com justificativa)

### Próxima fase
Breve descrição do que vem a seguir, aguardando confirmação para prosseguir.
```
</formato_de_saida_esperado>

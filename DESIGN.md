---
name: Barbearia
description: Sistema de agendamento para barbearia — escuro, sóbrio, dourado como único ponto de calor.
colors:
  preto-fosco: "#0f0f0f"
  carvao: "#1a1a1a"
  grafite: "#222222"
  grafite-input: "#2a2a2a"
  branco-fumaca: "#e8e8e8"
  cinza-medio: "#a0a0a0"
  linha-sutil: "#333333"
  champagne-dourado: "#c9a96e"
  champagne-dourado-hover: "#d4b87a"
  champagne-dourado-fosco: "#a68b56"
  vermelho-alerta: "#e74c3c"
  vermelho-alerta-escuro: "#c0392b"
  verde-confirmacao: "#27ae60"
  status-azul-info: "#3498db"
  status-verde-vivo: "#2ecc71"
  status-laranja-atencao: "#f39c12"
  status-cinza-neutro: "#95a5a6"
  status-roxo-ausencia: "#9b59b6"
typography:
  headline:
    fontFamily: "'Segoe UI', system-ui, -apple-system, sans-serif"
    fontSize: "1.6rem"
    fontWeight: 700
    lineHeight: 1.6
  title:
    fontFamily: "'Segoe UI', system-ui, -apple-system, sans-serif"
    fontSize: "1.4rem"
    fontWeight: 700
    lineHeight: 1.6
  body:
    fontFamily: "'Segoe UI', system-ui, -apple-system, sans-serif"
    fontSize: "1rem"
    fontWeight: 400
    lineHeight: 1.6
  label:
    fontFamily: "'Segoe UI', system-ui, -apple-system, sans-serif"
    fontSize: "0.85rem"
    fontWeight: 600
    letterSpacing: "0.5px"
rounded:
  md: "8px"
  pill: "20px"
spacing:
  xs: "4px"
  sm: "8px"
  md: "12px"
  lg: "16px"
  xl: "20px"
  xxl: "24px"
components:
  button-primary:
    backgroundColor: "{colors.champagne-dourado}"
    textColor: "{colors.preto-fosco}"
    rounded: "{rounded.md}"
    padding: "10px 20px"
  button-primary-hover:
    backgroundColor: "{colors.champagne-dourado-hover}"
  button-danger:
    backgroundColor: "{colors.vermelho-alerta}"
    textColor: "#ffffff"
    rounded: "{rounded.md}"
    padding: "6px 12px"
  button-outline:
    backgroundColor: "transparent"
    textColor: "{colors.cinza-medio}"
    rounded: "{rounded.md}"
  input-field:
    backgroundColor: "{colors.grafite-input}"
    textColor: "{colors.branco-fumaca}"
    rounded: "{rounded.md}"
    padding: "10px 14px"
---

# Design System: Barbearia

## Overview

**Creative North Star: "The Late-Night Chair"**

Barbearia funcionando depois do expediente, luz baixa, só a lâmpada da cadeira acesa. O fundo é quase preto, as superfícies sobem em camadas discretas de cinza-carvão, e o único calor na sala é o dourado — usado com rara, quase proposital. Nada aqui pede atenção decorativa: cada elemento existe porque tem função (confirmar, cancelar, sinalizar status), não porque enfeita a tela.

A atmosfera é sóbria, quente e discreta — o oposto deliberado de um dashboard SaaS colorido e cheio de gradientes. Cor com função (dourado para ação primária, vermelho para perigo, uma paleta própria para status de agendamento) faz o trabalho pesado; tudo o resto fica em tons de cinza sem competir por atenção.

**Key Characteristics:**
- Fundo quase preto (#0f0f0f), superfícies em camadas de cinza-carvão sem gradiente
- Dourado (#c9a96e) como único acento de calor — raro, reservado pra ação primária e marca
- Tudo plano em repouso; a única sombra é ambiente, nunca estrutural
- Cantos suavemente arredondados (8px) em quase tudo; pill (20px) só nos badges de status
- Tipografia única (sistema), sem par display/body — hierarquia vem de tamanho, peso e maiúsculas, não de fonte trocada

## Colors

Paleta escura e restrita: poucos tons de cinza pra superfície e texto, um único acento quente, e uma paleta semântica separada pra estado (perigo/sucesso/status de agendamento).

### Primary
- **Champagne Dourado** (#c9a96e): ação primária (botão principal, marca da navbar), foco de input, hover de link ativo. Usado com moderação — é o único elemento quente na tela, sua raridade é o ponto.
- **Champagne Dourado Hover** (#d4b87a): estado hover/active do dourado primário.
- **Champagne Dourado Fosco** (#a68b56): variante mais escura do acento, reservada pra uso futuro de estado pressed/disabled.

### Neutral
- **Preto Fosco** (#0f0f0f): fundo base da página. Quase preto, sem frieza azulada.
- **Carvão** (#1a1a1a): navbar, footer, cabeçalho de tabela — camada logo acima do fundo.
- **Grafite** (#222222): superfície de card e tabela — a camada de conteúdo.
- **Grafite Input** (#2a2a2a): campos de formulário — camada mais clara, sinaliza "editável".
- **Branco Fumaça** (#e8e8e8): texto primário.
- **Cinza Médio** (#a0a0a0): texto secundário, labels, placeholders.
- **Linha Sutil** (#333333): toda borda de card, input e divisor de tabela.

### Status (semântico — estado de negócio, não decoração)
Paleta própria, desacoplada da paleta de superfície, porque o estado do agendamento é informação crítica que precisa ser reconhecida à distância:
- **Vermelho Alerta** (#e74c3c): perigo (botão danger, alerta de erro) e status `cancelado`.
- **Vermelho Alerta Escuro** (#c0392b): hover do vermelho.
- **Verde Confirmação** (#27ae60): alerta de sucesso.
- **Azul Info** (#3498db): status `solicitado`.
- **Verde Vivo** (#2ecc71): status `confirmado` — mais saturado que o verde de alerta, pra não confundir os dois.
- **Laranja Atenção** (#f39c12): status `em_atendimento`.
- **Cinza Neutro** (#95a5a6): status `concluído` — desligado, sem mais ação pendente.
- **Roxo Ausência** (#9b59b6): status `no_show` — deliberadamente fora da paleta de trânsito (azul→verde→laranja), pra marcar "isso não seguiu o fluxo normal".

### Named Rules
**The One Warm Color Rule.** Dourado é o único tom quente no sistema inteiro. Ele nunca decora — aparece só em ação primária, marca e foco. Se um elemento novo "precisa" de dourado pra chamar atenção sem ser a ação principal da tela, é sinal de que a hierarquia está errada, não que falta dourado.

## Typography

**Body Font:** 'Segoe UI', system-ui, -apple-system, sans-serif (fonte única do sistema, sem par display)

**Character:** Uma família só, fazendo todo o trabalho de hierarquia por tamanho/peso/caixa em vez de troca de fonte. Direto, sem personalidade decorativa — layout de ferramenta, não de vitrine.

### Hierarchy
- **Headline** (700, 1.6rem, 1.6): título de página (`<h1>` do page-header).
- **Title** (700, 1.4rem, 1.6): marca da navbar.
- **Body** (400, 1rem, 1.6): texto corrido, tabela, formulário.
- **Label** (600, 0.85rem, letter-spacing 0.5px, uppercase): rótulo de campo de formulário e cabeçalho de tabela — o mesmo tratamento pros dois, reforçando que são a mesma categoria de informação ("isto é um rótulo de estrutura, não conteúdo").

### Named Rules
**The Uppercase Label Rule.** Todo rótulo estrutural (label de campo, cabeçalho de tabela, texto de badge) é maiúsculo, 0.5px de tracking, peso 600. Texto de conteúdo real nunca é maiúsculo.

## Layout

Container único, `max-width: 1100px`, centralizado, padding lateral de 20px. Sem grid de múltiplas colunas fora dos formulários (`.form-grid` usa `auto-fit, minmax(200px, 1fr)`). Navbar fixa no topo (`position: sticky`). Densidade confortável, não densa: cards com 20px de padding interno, linhas de tabela com 12–16px.

Sem breakpoints declarados — responsividade vem inteiramente de `flex-wrap` nos agrupamentos horizontais (navbar, page-header, form-inline, filter-bar), não de media queries dedicadas.

Ritmo de espaçamento observado: 4/8/12/16/20/24px como os passos reais mais usados; 30px e 60px aparecem só no padding vertical da área principal (respiro de página, não de componente).

## Elevation & Depth

Sistema majoritariamente plano. Existe uma única sombra ambiente (`0 2px 8px rgba(0,0,0,0.3)`), aplicada só ao `.card`, cuja função é separar sutilmente a superfície do fundo — não comunicar hierarquia ou camada. Não há tiers de elevação (sem sombra maior em modal, popover ou dropdown); profundidade vem de mudança de tom de cinza (fundo → carvão → grafite → grafite-input), não de sombra.

### Shadow Vocabulary
- **Ambiente** (`box-shadow: 0 2px 8px rgba(0,0,0,0.3)`): único uso — superfície de card, separando do fundo.
- **Anel de foco** (`box-shadow: 0 0 0 2px rgba(201, 169, 110, 0.2)`): halo dourado translúcido em input focado. É o único indício "elevado" de interação, tratado como estado, não como camada.

### Named Rules
**The Flat-By-Default Rule.** Superfícies são planas em repouso. A sombra ambiente do card é a única exceção, e permanece constante — não varia por tipo de conteúdo nem cresce em hover.

## Shapes

Cantos suavemente arredondados (8px, `--radius`) em card, input, botão, tabela e item de navbar ativo — um único raio, sem escala. Exceção proposital: badges de status usam raio pill (20px) pra se lerem como etiqueta, não como bloco. Sem bordas decorativas — a única borda é `1px solid` na cor `--border` (#333333), usada pra separar card/input/tabela do fundo, nunca como ornamento.

## Components

Componentes são funcionais e sem enfeite — hierarquia vem de cor e peso, nunca de decoração. Cada elemento colorido tem uma função de negócio específica; nenhum existe "pra dar vida" à tela.

### Buttons
- **Shape:** 8px de raio (`--radius`), sem borda no primário/danger.
- **Primary:** fundo Champagne Dourado, texto Preto Fosco, peso 600, padding 10px 20px. Hover: Champagne Dourado Hover + leve elevação (`translateY(-1px)`).
- **Danger:** fundo Vermelho Alerta, texto branco, menor (padding 6px 12px, 0.8rem) — reservado a ações destrutivas de linha de tabela (excluir).
- **Outline:** transparente, borda Linha Sutil, texto Cinza Médio. Hover: borda e texto viram Champagne Dourado — é o único botão cuja cor muda no hover em vez de só clarear.
- **Nav Primary** (`.btn-nav-primary`): mesmo tratamento do primary, mas vive dentro da navbar como CTA de "+ Agendar".

### Status Badges (componente de assinatura)
O componente mais importante do sistema — é a representação visual da máquina de estados do agendamento (`solicitado → confirmado → em_atendimento → concluído`, ou `→ cancelado`/`no_show`). Pill (20px de raio), fundo na cor do status a 20% de opacidade, texto sólido na cor cheia, 0.75rem, peso 600, uppercase, tracking 0.5px. Cada status tem cor própria e fixa (ver paleta Status); a cor nunca é reaproveitada para outro significado na mesma tela.

### Cards / Containers
- **Corner Style:** 8px.
- **Background:** Grafite (#222222).
- **Shadow Strategy:** sombra ambiente única (ver Elevation & Depth).
- **Border:** 1px solid Linha Sutil.
- **Internal Padding:** 20px.

### Inputs / Fields
- **Style:** fundo Grafite Input, borda 1px Linha Sutil, 8px de raio, padding 10px 14px.
- **Focus:** borda vira Champagne Dourado + anel translúcido dourado (`box-shadow: 0 0 0 2px rgba(201,169,110,0.2)`), sem mudança de fundo.

### Navigation
- Fundo Carvão, borda inferior 1px Linha Sutil, sticky no topo. Links em Cinza Médio; hover/ativo troca pra Branco Fumaça sobre fundo Grafite. Marca (`.navbar-brand`) sempre em Champagne Dourado — o único texto permanentemente dourado da interface. Mobile: `flex-wrap`, sem menu colapsável dedicado.

### Tables
- Fundo Grafite, mesmo raio 8px do resto do sistema, `overflow: hidden` pra conter o raio nas bordas da primeira/última linha. Cabeçalho em Carvão com o mesmo tratamento tipográfico de Label (uppercase, 600, tracking). Linha em hover recebe lavagem sutilíssima de dourado (`rgba(201,169,110,0.05)`) — o único lugar onde o acento aparece "ambiente" em vez de sólido.

## Do's and Don'ts

### Do:
- **Do** reservar Champagne Dourado pra ação primária, marca e foco — nunca decoração.
- **Do** manter a paleta de Status isolada da paleta de superfície/marca; um status novo ganha cor própria, nunca reaproveita dourado ou vermelho de perigo sem ser literalmente `cancelado`.
- **Do** manter tudo plano em repouso; a única sombra permitida é a ambiente do card.
- **Do** usar o mesmo raio de 8px em qualquer superfície nova; só badge/pill foge disso, de propósito.

### Don't:
- **Don't** introduzir uma segunda fonte ou par display/body — a hierarquia atual é só tamanho/peso/caixa.
- **Don't** adicionar tiers de sombra (elevação estrutural) — a decisão confirmada é manter a sombra como ambiente, não como sistema de camadas.
- **Don't** usar cor decorativa sem função de negócio — cada cor no sistema mapeia pra uma ação (perigo, sucesso, foco) ou um estado (status de agendamento).

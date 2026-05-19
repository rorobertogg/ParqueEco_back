# Estrutura do Projeto - Parque Ecológico

## 📂 Organização de Arquivos

```
parque_ecologico/
│
├── app/
│   ├── controllers/          # Lógica dos endpoints da API
│   │   └── AgendamentoController.php
│   │
│   ├── models/               # Modelos de dados / ORM
│   │   └── Agendamento.php
│   │
│   ├── core/                 # Núcleo da aplicação
│   │   └── Router.php
│   │
│   └── views/                # 🆕 Templates HTML
│       ├── layouts/          # Layouts base (header, footer, navbar)
│       │   └── base.html
│       │
│       ├── pages/            # Páginas principais
│       │   ├── home.html
│       │   ├── agendamento.html
│       │   ├── admin.html
│       │   ├── sobre.html
│       │   └── contato.html
│       │
│       ├── components/       # Componentes reutilizáveis
│       │   ├── card.html
│       │   ├── modal.html
│       │   └── pagination.html
│       │
│       └── partials/         # Fragmentos (includes)
│           ├── navbar.html
│           └── footer.html
│
├── public/                   # Arquivos públicos (web root)
│   ├── index.php             # Entry point da API
│   ├── index.html            # Home estática (opcional)
│   │
│   ├── css/                  # 🆕 Estilos CSS
│   │   ├── style.css         # Estilos globais
│   │   ├── agendamento.css   # Estilos específicos
│   │   └── admin.css
│   │
│   ├── js/                   # 🆕 Scripts JavaScript
│   │   ├── main.js           # Scripts globais
│   │   ├── agendamento.js    # Scripts específicos
│   │   └── admin.js
│   │
│   └── images/               # 🆕 Imagens e assets
│       ├── logo.png
│       ├── hero.jpg
│       └── icons/
│
├── config/                   # Configurações
│   ├── database.php
│   └── env.php
│
├── .env                      # Variáveis de ambiente
├── .gitignore
├── admin.html                # ANTIGO - pode ser movido para app/views/pages/
└── APP_STRUCTURE.md          # Este arquivo
```

---

## 🎯 Padrão de Uso

### 1. **Criar uma Nova Página**

**Arquivo:** `app/views/pages/minha-pagina.html`

```html
<h1>Título da Página</h1>
<p>Conteúdo aqui...</p>
```

### 2. **Criar um Componente Reutilizável**

**Arquivo:** `app/views/components/botao.html`

```html
<button class="btn <?= $classe ?? 'btn-primary' ?>">
    <?= $texto ?? 'Clique aqui' ?>
</button>
```

### 3. **Usar um Partial em um Layout**

**Arquivo:** `app/views/layouts/base.html`

```html
<?php include __DIR__ . '/../partials/navbar.html'; ?>
<!-- conteúdo -->
<?php include __DIR__ . '/../partials/footer.html'; ?>
```

### 4. **Adicionar CSS Específico**

**Arquivo:** `public/css/agendamento.css`

```html
<!-- Na página, incluir -->
<link rel="stylesheet" href="/parque_ecologico/public/css/agendamento.css">
```

---

## 🔄 Fluxo Recomendado

1. **Estrutura estática** → `app/views/` (HTML puro)
2. **Estilos** → `public/css/`
3. **Interatividade** → `public/js/`
4. **Dados dinâmicos** → Controllers da API
5. **Renderizar views** → Router retorna HTML ou JSON

---

## 📋 Arquivos Criados

✅ `app/views/layouts/base.html` - Layout base com navbar e footer
✅ `app/views/pages/home.html` - Página inicial
✅ `app/views/pages/agendamento.html` - Formulário de agendamento
✅ `app/views/partials/navbar.html` - Navegação
✅ `app/views/partials/footer.html` - Rodapé
✅ `public/css/style.css` - Estilos globais
✅ `public/js/main.js` - Scripts globais
✅ `public/js/agendamento.js` - Scripts do agendamento
✅ Pastas: `components/`, `images/`

---

## 🚀 Próximos Passos

1. **Mover `admin.html`** para `app/views/pages/admin.html`
2. **Criar um View Renderer** em PHP (helper) para renderizar templates
3. **Atualizar o Router** para servir páginas HTML
4. **Adicionar mais componentes** conforme necessário
5. **Estilizar de acordo com o design** do projeto


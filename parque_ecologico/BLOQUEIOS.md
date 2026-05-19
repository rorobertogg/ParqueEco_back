# 📅 Sistema de Bloqueio de Datas Comemorativas

## Descrição

Sistema para bloquear automaticamente agendamentos de visitas técnicas e quiosques em datas comemorativas do Brasil. Prevent booking conflicts nos feriados.

## ✨ Funcionalidades

- ✅ Bloqueio automático de datas específicas
- ✅ Importação de datas comemorativas brasileiras (fixas e móveis)
- ✅ Interface no painel administrativo para gerenciar bloqueios
- ✅ Validação automática nas requisições de agendamento
- ✅ Suporte a Sexta-feira Santa e Corpus Christi (datas móveis)

## 🗓️ Datas Comemorativas Incluídas

### Fixas (mesmo dia todo ano):
- 01/01 - Ano Novo
- 21/04 - Tiradentes
- 01/05 - Dia do Trabalho
- 07/09 - Independência do Brasil
- 12/10 - Nossa Senhora Aparecida
- 02/11 - Finados
- 15/11 - Proclamação da República
- 20/11 - Consciência Negra
- 25/12 - Natal

### Móveis (variam conforme o calendário):
- Sexta-feira Santa (60 dias antes da Páscoa)
- Corpus Christi (60 dias após a Páscoa)

## 🔧 Como Usar

### Via Painel Administrativo

1. **Acessar o Painel Admin**
   - Faça login como administrador
   - Vá para `/admin`

2. **Abrir Gerenciador de Datas**
   - Clique no botão "Datas Bloqueadas"
   - Será aberto um painel com opções

3. **Bloquear Data Única**
   - Preencha a data no campo "Data"
   - Digite o motivo (ex: "Feriado municipal")
   - Clique em "Bloquear Data"

4. **Bloquear Datas Comemorativas Automaticamente**
   - Selecione o ano desejado
   - Clique em "Carregar Datas"
   - Serão exibidas todas as datas comemorativas do ano
   - Se houver datas não bloqueadas, clique em "Bloquear Todos"

5. **Remover Bloqueio**
   - Na seção "Datas Bloqueadas", clique no botão "Remover"

### Via Terminal (Alternativo)

```bash
cd /path/to/parque_ecologico
php populate_bloqueios.php
```

Este script irá:
- Ler as datas comemorativas de 2026
- Verificar quais já estão no banco
- Adicionar as que faltam
- Exibir um relatório de sucesso

## 📋 Estrutura de Dados

### Tabela: `bloqueios`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | INT | ID único |
| data_bloqueada | DATE | Data bloqueada |
| motivo | VARCHAR(255) | Motivo do bloqueio |

### Unique Constraint
- A combinação `data_bloqueada` é UNIQUE (uma data não pode ser bloqueada duas vezes)

## 🛡️ Validação

Quando um usuário tenta agendar ou reservar:

1. ✓ O sistema valida se a data está na tabela `bloqueios`
2. ✓ Se estiver, exibe erro: "Data indisponível para agendamento"
3. ✓ O agendamento é bloqueado automaticamente

### Validação nos Controllers:

**AgendamentoController.php:**
```php
if ($this->dataBloqueada($data['data_reserva']))
    return "Data indisponível para agendamento";
```

**VisitaTecnicaController.php:**
```php
if ($this->dataBloqueada($data['data_visita']))
    return "Data indisponível";
```

## 🔌 API Endpoints

### Listar bloqueios
```
GET /api/bloqueios/listar
```
**Autenticação:** Requerida (admin)

### Criar bloqueio
```
POST /api/bloqueios/criar
```
**Body:**
```json
{
  "data_bloqueada": "2026-09-07",
  "motivo": "Independência do Brasil"
}
```
**Autenticação:** Requerida (admin)

### Deletar bloqueio
```
DELETE /api/bloqueios/excluir/{id}
```
**Autenticação:** Requerida (admin)

### Gerar datas comemorativas
```
GET /api/bloqueios/datas-comemorativas?ano=2026
```
**Autenticação:** Requerida (admin)
**Resposta:**
```json
{
  "datas": [...],
  "total": 11,
  "ja_bloqueados": 5,
  "lista_bloqueados": [...]
}
```

### Bloquear todas as datas comemorativas
```
POST /api/bloqueios/bloquear-todos
```
**Body:**
```
ano=2026
```
**Autenticação:** Requerida (admin)

## 📂 Arquivos Criados/Modificados

### Novos:
- `app/models/Bloqueio.php` - Modelo de dados
- `app/controllers/BloqueioController.php` - Controller
- `public/js/bloqueios.js` - Interface JS
- `populate_bloqueios.php` - Script de população
- `BLOQUEIOS.md` - Este arquivo

### Modificados:
- `app/core/Router.php` - Rotas de API
- `app/views/pages/admin.html` - Interface admin
- `public/css/style.css` - Estilos

## 🎨 Componentes da Interface

### Seção 1: Bloquear Data Única
- Input de data
- Input de motivo
- Botão para bloquear
- Mensagem de feedback

### Seção 2: Datas Comemorativas
- Selector de ano
- Botão para carregar datas
- Exibição de datas com status
- Botão para bloquear todos

### Seção 3: Lista de Bloqueios
- Exibição de todas as datas bloqueadas
- Data formatada em pt-BR
- Motivo
- Botão para remover bloqueio

## ✅ Checklist de Implementação

- ✅ Modelo Bloqueio.php criado
- ✅ Controller BloqueioController.php criado
- ✅ Rotas de API adicionadas ao Router
- ✅ Interface HTML adicionada ao admin.html
- ✅ Estilos CSS adicionados
- ✅ JavaScript bloqueios.js criado
- ✅ Script de população criado
- ✅ Validação integrada aos controllers existentes

## 🚀 Próximos Passos (Opcional)

1. Adicionar suporte a bloqueios temporários (períodos)
2. Criar template de email para notificar sobre datas bloqueadas
3. Adicionar histórico de bloqueios (audit log)
4. Dashboard com estatísticas de bloqueios
5. Integração com calendário visual

## 📝 Notas

- As validações já existem nos controllers de agendamento e visita técnica
- O sistema usa a tabela `bloqueios` que já existe no banco
- As datas comemorativas são calculadas automaticamente
- A interface é responsiva e se adapta a mobile

## 🐛 Troubleshooting

**Problema:** "Esta data já está bloqueada"
- **Solução:** A data já existe na tabela. Remova e adicione novamente, ou use outro bloqueio.

**Problema:** "Erro ao bloquear data"
- **Solução:** Verifique a conexão com o banco de dados. Verifique os logs de erro.

**Problema:** Script populate_bloqueios.php não funciona
- **Solução:** Certifique-se de estar no diretório correto e que config/database.php está acessível.

---

**Desenvolvido em:** 15 de maio de 2026

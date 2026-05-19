const API = "/parque_ecologico/api/agendamentos";
const GUIA_API = "/parque_ecologico/api/guias";
const MENSAGENS_API = "/parque_ecologico/api/contato";

// Validação de telefone - apenas números com formatação automática
function setupPhoneValidation() {
    const phoneInputs = document.querySelectorAll('input[type="tel"]');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function() {
            // Remove tudo que não é número
            let value = this.value.replace(/[^0-9]/g, '');
            
            // Limita a 11 dígitos
            if (value.length > 11) {
                value = value.substring(0, 11);
            }
            
            // Formata automaticamente: (XX) XXXXX-XXXX
            if (value.length > 0) {
                if (value.length <= 2) {
                    value = '(' + value;
                } else if (value.length <= 6) {
                    value = '(' + value.substring(0, 2) + ') ' + value.substring(2);
                } else {
                    value = '(' + value.substring(0, 2) + ') ' + value.substring(2, 7) + '-' + value.substring(7);
                }
            }
            
            this.value = value;
        });
    });
}

document.addEventListener("DOMContentLoaded", () => {
    setupPhoneValidation();

    document.querySelectorAll(
        'input[name="filtro"]'
    ).forEach(el =>
        el.addEventListener("change", processarFiltro)
    );

    [
        "tipoFiltro",
        "dataFiltro",
        "quiosqueFiltro",
        "responsavelFiltro",
        "guiaFiltro"
    ].forEach(id => {
        document.getElementById(id)
            ?.addEventListener("input", processarFiltro);
    });

    document.getElementById('guiaNomeFiltro')
        ?.addEventListener('input', () => {
            if (window.adminGuias) {
                renderGuias(window.adminGuias);
            }
        });

    document.getElementById("sortOrder")
        ?.addEventListener("change", () => {
            sortCards();
            processarFiltro();
        });

    document.getElementById("btn-atualizar")
        ?.addEventListener("click", () => location.reload());

    const btnAgendamentos = document.getElementById('btn-agendamentos');
    const btnGuias = document.getElementById('btn-guias');
    const btnBloqueios = document.getElementById('btn-bloqueios');
    const btnMensagens = document.getElementById('btn-mensagens');

    function ativarAba(botaoAtivo) {
        [btnAgendamentos, btnGuias, btnBloqueios, btnMensagens].forEach(botao => {
            if (!botao) return;
            botao.classList.toggle('active', botao === botaoAtivo);
        });
    }

    function mostrarPainel(painel) {
        const painelGuia = document.getElementById('guia-panel');
        const painelBloqueios = document.getElementById('bloqueios-panel');
        const painelMensagens = document.getElementById('mensagens-panel');
        const lista = document.getElementById('lista');
        const filtros = document.getElementById('filtros-area');

        if (painel === 'agendamentos') {
            if (painelGuia) painelGuia.style.display = 'none';
            if (painelBloqueios) painelBloqueios.style.display = 'none';
            if (painelMensagens) painelMensagens.style.display = 'none';
            if (lista) lista.style.display = '';
            if (filtros) filtros.style.display = '';
            return;
        }

        if (painel === 'guias') {
            if (painelGuia) painelGuia.style.display = 'block';
            if (painelBloqueios) painelBloqueios.style.display = 'none';
            if (painelMensagens) painelMensagens.style.display = 'none';
            if (lista) lista.style.display = 'none';
            if (filtros) filtros.style.display = 'none';
            return;
        }

        if (painel === 'bloqueios') {
            if (painelGuia) painelGuia.style.display = 'none';
            if (painelBloqueios) painelBloqueios.style.display = 'block';
            if (painelMensagens) painelMensagens.style.display = 'none';
            if (lista) lista.style.display = 'none';
            if (filtros) filtros.style.display = 'none';
            return;
        }

        if (painel === 'mensagens') {
            if (painelGuia) painelGuia.style.display = 'none';
            if (painelBloqueios) painelBloqueios.style.display = 'none';
            if (painelMensagens) painelMensagens.style.display = 'block';
            if (lista) lista.style.display = 'none';
            if (filtros) filtros.style.display = 'none';
            return;
        }
    }

    btnAgendamentos?.addEventListener('click', () => {
        ativarAba(btnAgendamentos);
        mostrarPainel('agendamentos');
    });

    btnGuias?.addEventListener('click', async () => {
        ativarAba(btnGuias);
        mostrarPainel('guias');
        await carregarGuias();
    });

    btnBloqueios?.addEventListener('click', () => {
        ativarAba(btnBloqueios);
        mostrarPainel('bloqueios');
        carregarBloqueios();
    });

    btnMensagens?.addEventListener('click', async () => {
        ativarAba(btnMensagens);
        mostrarPainel('mensagens');
        await carregarMensagens();
    });

    const mensagensFiltro = document.getElementById('mensagensFiltro');
    const mensagensStatusFiltro = document.getElementById('mensagensStatusFiltro');
    const mensagensAtualizar = document.getElementById('mensagens-atualizar');
    mensagensFiltro?.addEventListener('input', () => renderMensagens(window.adminMensagens || []));
    mensagensStatusFiltro?.addEventListener('change', () => renderMensagens(window.adminMensagens || []));
    mensagensAtualizar?.addEventListener('click', () => carregarMensagens());

    document.getElementById('guia-form')
        ?.addEventListener('submit', async (e) => {
            e.preventDefault();

            const id = document.getElementById('guia_id').value;
            const nome = document.getElementById('guia_nome').value.trim();
            const email = document.getElementById('guia_email').value.trim();
            let telefone = document.getElementById('guia_telefone').value.trim();
            const especialidade = document.getElementById('guia_especialidade').value.trim();
            const ativo = document.getElementById('guia_ativo').checked;

            // Remove formatação do telefone para envio
            telefone = telefone.replace(/[^0-9]/g, '');

            const msgEl = document.getElementById('guia-msg');
            msgEl.textContent = '';

            try {
                const token = document.getElementById('csrfToken')?.value;
                const isEdit = id !== '';
                const url = isEdit ? `${GUIA_API}/atualizar/${encodeURIComponent(id)}` : `${GUIA_API}/criar`;
                const method = isEdit ? 'PUT' : 'POST';

                const res = await fetch(url, {
                    method,
                    credentials: 'include',
                    headers: Object.assign({ 'Content-Type': 'application/json' }, token ? { 'X-CSRF-Token': token } : {}),
                    body: JSON.stringify({ nome, email, telefone, especialidade, ativo })
                });

                const data = await res.json();

                if (!res.ok) throw new Error(data.erro || data.mensagem || 'Erro');

                msgEl.textContent = data.mensagem || (isEdit ? 'Guia atualizado' : 'Guia adicionado');
                document.getElementById('guia-form').reset();
                resetGuiaForm();
                showToast(data.mensagem || (isEdit ? 'Guia atualizado com sucesso' : 'Guia cadastrado com sucesso'), 'success');
                await carregarGuias();

            } catch (err) {
                const message = err.message || 'Erro ao processar guia';
                msgEl.textContent = message;
                showToast(message, 'error');
            }
        });

    document.getElementById('guia-cancel')
        ?.addEventListener('click', () => resetGuiaForm());

});

async function carregarGuias() {
    const lista = document.getElementById('guia-list');
    if (!lista) return;

    lista.textContent = 'Carregando...';

    try {
        const res = await fetch(`${GUIA_API}/listar`, {
            credentials: 'include'
        });

        const dados = await res.json();

        if (!res.ok) {
            throw new Error(dados.erro || 'Não foi possível carregar guias');
        }

        if (!Array.isArray(dados) || dados.length === 0) {
            lista.textContent = 'Nenhum guia encontrado.';
            return;
        }

        window.adminGuias = dados;
        renderGuias(dados);

    } catch (err) {
        lista.textContent = err.message || 'Erro ao carregar guias';
        showToast(err.message || 'Falha ao carregar guias', 'error');
    }
}

async function carregarMensagens() {
    const lista = document.getElementById('mensagens-list');
    if (!lista) return;

    lista.textContent = 'Carregando...';

    try {
        const res = await fetch(`${MENSAGENS_API}/listar`, {
            credentials: 'include'
        });

        const dados = await res.json();

        if (!res.ok) {
            throw new Error(dados.erro || 'Não foi possível carregar mensagens');
        }

        if (!Array.isArray(dados) || dados.length === 0) {
            lista.innerHTML = '<p>Nenhuma mensagem encontrada.</p>';
            return;
        }

        window.adminMensagens = dados;
        renderMensagens(dados);

    } catch (err) {
        lista.textContent = err.message || 'Erro ao carregar mensagens';
        showToast(err.message || 'Falha ao carregar mensagens', 'error');
    }
}

function renderMensagens(dados) {
    const lista = document.getElementById('mensagens-list');
    if (!lista) return;

    const filtro = document.getElementById('mensagensFiltro')?.value.trim().toLowerCase() || '';
    const statusFiltro = document.getElementById('mensagensStatusFiltro')?.value || '';

    const items = dados.filter(m => {
        if (filtro) {
            const term = filtro;
            const matchesSearch =
                (m.nome && m.nome.toLowerCase().includes(term)) ||
                (m.email && m.email.toLowerCase().includes(term)) ||
                (m.assunto && m.assunto.toLowerCase().includes(term)) ||
                (m.mensagem && m.mensagem.toLowerCase().includes(term));
            if (!matchesSearch) return false;
        }

        const isLida = Number(m.lida) === 1;
        const isRespondida = Number(m.respondida) === 1;

        if (statusFiltro === 'lida' && !isLida) return false;
        if (statusFiltro === 'nao_lida' && isLida) return false;
        if (statusFiltro === 'respondida' && !isRespondida) return false;
        if (statusFiltro === 'nao_respondida' && isRespondida) return false;

        return true;
    });

    if (items.length === 0) {
        lista.innerHTML = '<p>Nenhuma mensagem encontrada.</p>';
        return;
    }

    lista.innerHTML = items.map(m => {
        const date = m.criado_em ? new Date(m.criado_em).toLocaleString() : '';
        const telefone = m.telefone ? `<div class="meta">${escapeHtml(m.telefone)}</div>` : '';
        const isLida = String(m.lida) === '1' || String(m.lida) === 'true';
        const isRespondida = String(m.respondida) === '1' || String(m.respondida) === 'true';
        const respondidaDisabled = !isLida ? 'disabled' : '';
        const respondidaTitle = !isLida ? 'Marque como lida antes de marcar respondida' : '';
        const lidaBadge = isLida ? '<span class="badge badge-success">Lida</span>' : '<span class="badge badge-secondary">Não lida</span>';
        const respondidaBadge = isRespondida ? '<span class="badge badge-success">Respondida</span>' : '<span class="badge badge-secondary">Não respondida</span>';
        return `
            <article class="card mensagem-card" data-id="${m.id}">
                <div class="card-header">
                    <h3>${escapeHtml(m.nome)} <small>${escapeHtml(m.email)}</small></h3>
                    <span class="date">${escapeHtml(date)}</span>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <strong>Assunto</strong>
                            <span>${escapeHtml(m.assunto || '')}</span>
                        </div>
                        ${telefone}
                        <div class="info-item">
                            <strong>Status</strong>
                            <span>${lidaBadge} ${respondidaBadge}</span>
                        </div>
                        <div class="info-item" style="grid-column:1 / -1;">
                            <strong>Mensagem</strong>
                            <div style="white-space:pre-wrap;">${escapeHtml(m.mensagem || '')}</div>
                        </div>
                    </div>
                </div>
                <div class="card-actions">
                    <label class="checkbox-inline">
                        <input type="checkbox" class="mensagem-checkbox-lida" data-id="${m.id}" ${isLida ? 'checked' : ''}>
                        Lida
                    </label>
                    <label class="checkbox-inline">
                        <input type="checkbox" class="mensagem-checkbox-respondida" data-id="${m.id}" ${isRespondida ? 'checked' : ''} ${respondidaDisabled} title="${respondidaTitle}">
                        Respondida
                    </label>
                    <button class="btn btn-danger mensagem-delete" data-id="${m.id}">Excluir</button>
                </div>
            </article>
        `;
    }).join('');

    lista.querySelectorAll('.mensagem-checkbox-lida').forEach(input => {
        input.addEventListener('change', async () => {
            const id = input.dataset.id;
            const lida = input.checked;
            try {
                await fazerRequisicao(`${MENSAGENS_API}/status/${encodeURIComponent(id)}`, 'POST', { lida });
                showToast('Mensagem marcada como ' + (lida ? 'lida' : 'não lida'), 'success');
                window.adminMensagens = (window.adminMensagens || []).map(item => {
                    if (String(item.id) === String(id)) {
                        return Object.assign({}, item, { lida: lida ? 1 : 0 });
                    }
                    return item;
                });
                renderMensagens(window.adminMensagens);
            } catch (err) {
                showToast(err.message || 'Erro ao atualizar leitura', 'error');
            }
        });
    });

    lista.querySelectorAll('.mensagem-checkbox-respondida').forEach(input => {
        input.addEventListener('change', async () => {
            const id = input.dataset.id;
            const respondida = input.checked;
            const mensagem = (window.adminMensagens || []).find(item => String(item.id) === String(id));
            const isLida = mensagem && (String(mensagem.lida) === '1' || String(mensagem.lida) === 'true');

            if (respondida && !isLida) {
                input.checked = false;
                showToast('Marque a mensagem como lida antes de marcar como respondida.', 'warning');
                return;
            }

            try {
                await fazerRequisicao(`${MENSAGENS_API}/status/${encodeURIComponent(id)}`, 'POST', { respondida });
                showToast('Mensagem marcada como ' + (respondida ? 'respondida' : 'não respondida'), 'success');
                window.adminMensagens = (window.adminMensagens || []).map(item => {
                    if (String(item.id) === String(id)) {
                        return Object.assign({}, item, { respondida: respondida ? 1 : 0 });
                    }
                    return item;
                });
                renderMensagens(window.adminMensagens);
            } catch (err) {
                showToast(err.message || 'Erro ao atualizar resposta', 'error');
            }
        });
    });

    lista.querySelectorAll('.mensagem-delete').forEach(btn => {
        btn.addEventListener('click', async () => {
            const id = btn.dataset.id;
            if (!confirm('Excluir mensagem?')) return;
            try {
                await fazerRequisicao(`${MENSAGENS_API}/excluir/${encodeURIComponent(id)}`, 'DELETE');
                showToast('Mensagem excluída', 'success');
                window.adminMensagens = (window.adminMensagens || []).filter(x => String(x.id) !== String(id));
                renderMensagens(window.adminMensagens);
            } catch (err) {
                showToast(err.message || 'Erro ao excluir mensagem', 'error');
            }
        });
    });
}

function renderGuias(dados) {
    const lista = document.getElementById('guia-list');
    if (!lista) return;

    const filtroNome = document.getElementById('guiaNomeFiltro')?.value.trim().toLowerCase() || '';
    const guiasFiltrados = dados.filter(guia => {
        return !filtroNome || guia.nome.toLowerCase().includes(filtroNome);
    });

    if (guiasFiltrados.length === 0) {
        lista.textContent = 'Nenhum guia encontrado.';
        return;
    }

    lista.innerHTML = guiasFiltrados.map(guia => {
        const ativo = guia.ativo == 1 ? 'Ativo' : 'Inativo';
        const email = guia.email ? `<span>${escapeHtml(guia.email)}</span>` : '';
        const telefone = guia.telefone ? `<span>${escapeHtml(guia.telefone)}</span>` : '';
        const especialidade = guia.especialidade ? `<span>${escapeHtml(guia.especialidade)}</span>` : '';

        return `
            <div class="guia-item" data-id="${guia.id}">
                <div class="guia-item-title">
                    <strong>${escapeHtml(guia.nome)}</strong>
                    <span class="guia-status ${guia.ativo == 1 ? 'ativo' : 'inativo'}">${ativo}</span>
                </div>
                <div class="guia-item-meta">
                    ${email}
                    ${telefone}
                    ${especialidade}
                </div>
                <div class="guia-item-actions">
                    <button type="button" class="btn btn-secondary guia-edit-button" data-id="${guia.id}">Editar</button>
                    <button type="button" class="btn btn-danger guia-delete-button" data-id="${guia.id}">Remover</button>
                </div>
            </div>
        `;
    }).join('');

    lista.querySelectorAll('.guia-edit-button').forEach(button => {
        button.addEventListener('click', () => {
            const id = button.dataset.id;
            const guia = window.adminGuias.find(item => String(item.id) === String(id));
            if (!guia) return;
            preencherFormularioGuia(guia);
        });
    });

    lista.querySelectorAll('.guia-delete-button').forEach(button => {
        button.addEventListener('click', async () => {
            const id = button.dataset.id;
            if (!confirm('Tem certeza que deseja remover este guia?')) return;
            try {
                const token = document.getElementById('csrfToken')?.value;
                const res = await fetch(`${GUIA_API}/excluir/${encodeURIComponent(id)}`, {
                    method: 'DELETE',
                    credentials: 'include',
                    headers: Object.assign({ 'Content-Type': 'application/json' }, token ? { 'X-CSRF-Token': token } : {})
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.erro || 'Erro ao remover guia');
                showToast(data.mensagem || 'Guia removido com sucesso', 'success');
                await carregarGuias();
            } catch (err) {
                showToast(err.message || 'Erro ao remover guia', 'error');
            }
        });
    });
}

function escapeHtml(text) {
    if (typeof text !== 'string') return '';
    return text.replace(/[&<>"]|'/g, (match) => {
        switch (match) {
            case '&': return '&amp;';
            case '<': return '&lt;';
            case '>': return '&gt;';
            case '"': return '&quot;';
            case "'": return '&#39;';
            default: return match;
        }
    });
}

function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    if (!toast) return;

    toast.textContent = message;
    toast.className = `toast toast-${type}`;
    toast.style.opacity = '1';

    clearTimeout(window.guiaToastTimeout);
    window.guiaToastTimeout = setTimeout(() => {
        toast.style.opacity = '0';
    }, 4000);
}

function resetGuiaForm() {
    const form = document.getElementById('guia-form');
    const submitButton = document.getElementById('guia-submit');
    const cancelButton = document.getElementById('guia-cancel');
    const msgEl = document.getElementById('guia-msg');

    if (form) form.reset();
    document.getElementById('guia_id').value = '';
    if (submitButton) submitButton.textContent = 'Adicionar Guia';
    if (cancelButton) cancelButton.style.display = 'none';
    if (msgEl) msgEl.textContent = '';
}

function preencherFormularioGuia(guia) {
    document.getElementById('guia_id').value = guia.id;
    document.getElementById('guia_nome').value = guia.nome || '';
    document.getElementById('guia_email').value = guia.email || '';
    document.getElementById('guia_telefone').value = guia.telefone || '';
    document.getElementById('guia_especialidade').value = guia.especialidade || '';
    document.getElementById('guia_ativo').checked = guia.ativo == 1;

    const submitButton = document.getElementById('guia-submit');
    const cancelButton = document.getElementById('guia-cancel');
    if (submitButton) submitButton.textContent = 'Salvar alterações';
    if (cancelButton) cancelButton.style.display = 'inline-block';
}

async function fazerRequisicao(url, metodo = "POST", body = null) {

    const token = document.getElementById("csrfToken")?.value;

    const headers = {
        "Content-Type": "application/json"
    };

    if (token) {
        headers["X-CSRF-Token"] = token;
    }

    const options = {
        method: metodo,
        credentials: "include",
        headers
    };

    if (body !== null) {
        options.body = JSON.stringify(body);
    }

    const response = await fetch(url, options);

    const data = await response.json();

    if (!response.ok) {
        throw new Error(data.erro || "Erro");
    }

    return data;
}

async function aprovar(id) {

    if (!confirm("Aprovar?")) return;

    await fazerRequisicao(`${API}/aprovar/${id}`);
    atualizarCardStatus(id, "aprovado");
}

async function rejeitar(id) {

    if (!confirm("Rejeitar?")) return;

    await fazerRequisicao(`${API}/rejeitar/${id}`);
    atualizarCardStatus(id, "rejeitado");
}

async function excluir(id) {

    if (!confirm("Excluir?")) return;

    await fazerRequisicao(`${API}/excluir/${id}`);
    removerCard(id);
}

function atualizarCardStatus(id, status) {

    const card = document.querySelector(
        `[data-id="${id}"]`
    );

    if (!card) return;

    card.dataset.status = status;

    card.classList.remove(
        "pendente",
        "aprovado",
        "rejeitado"
    );

    card.classList.add(status);

    card.querySelector(".status-badge")
        .textContent = capitalizar(status);

    processarFiltro();
}

function removerCard(id) {

    document.querySelector(
        `[data-id="${id}"]`
    )?.remove();
}

function processarFiltro() {

    const status =
        document.querySelector(
            'input[name="filtro"]:checked'
        ).value;

    const tipo =
        document.getElementById("tipoFiltro").value;

    const data =
        document.getElementById("dataFiltro").value;

    const quiosque =
        document.getElementById("quiosqueFiltro").value;

    const responsavel =
        document.getElementById("responsavelFiltro")
            .value.toLowerCase();

    const guia =
        document.getElementById("guiaFiltro")
            .value.toLowerCase();

    document.querySelectorAll(".admin-card")
        .forEach(card => {

            const okStatus =
                status === "todos" ||
                card.dataset.status === status;

            const okTipo =
                !tipo ||
                card.dataset.tipo === tipo;

            const okData =
                !data ||
                card.dataset.data === data;

            const okQuiosque =
                !quiosque ||
                card.dataset.quiosque === quiosque;

            const okResponsavel =
                !responsavel ||
                card.dataset.responsavel
                    .toLowerCase()
                    .includes(responsavel);

            const okGuia =
                !guia ||
                card.dataset.guia
                    .toLowerCase()
                    .includes(guia);

            card.style.display =
                okStatus &&
                okTipo &&
                okData &&
                okQuiosque &&
                okResponsavel &&
                okGuia
                    ? ""
                    : "none";
        });
}

function sortCards() {
    const container = document.getElementById('lista');
    if (!container) return;

    const order = document.getElementById('sortOrder')?.value || 'desc';

    const cards = Array.from(container.querySelectorAll('.admin-card'));

    cards.sort((a, b) => {
        const da = a.dataset.data || '';
        const db = b.dataset.data || '';

        const ta = da ? new Date(da).getTime() : 0;
        const tb = db ? new Date(db).getTime() : 0;

        return order === 'desc' ? tb - ta : ta - tb;
    });

    cards.forEach(c => container.appendChild(c));
}

function capitalizar(txt) {
    return txt.charAt(0).toUpperCase() + txt.slice(1);
}
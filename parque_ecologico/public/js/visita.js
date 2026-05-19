// Validação de telefone - apenas números com formatação automática
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

function limparFormulario(form) {
    form.reset();
}

document.getElementById("visita-form")?.addEventListener("submit", async function(e) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);

    const data = {
        nome_instituicao: formData.get("nome_instituicao"),
        nome_diretor: formData.get("nome_diretor"),
        nome_responsavel: formData.get("nome_responsavel"),
        telefone: formData.get("telefone"),
        email: formData.get("email"),

        data_visita: formData.get("data_visita"),
        guia_id: parseInt(formData.get("guia_id"), 10),

        faixa_etaria: formData.get("faixa_etaria"),
        qtd_visitantes: parseInt(formData.get("qtd_visitantes"), 10),

        objetivo: formData.get("objetivo"),
        observacoes: formData.get("observacoes"),

        horario_entrada: formData.get("horario_entrada"),
        horario_saida: formData.get("horario_saida"),

        aceite_termos: formData.get("aceite_termos") ? 1 : 0
    };

    const response = await fetch("/parque_ecologico/api/visita/enviar", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(data)
    });

    const result = await response.json();
    const resultado = document.getElementById("resultado");

    resultado.innerText = result.erro || result.mensagem;
    resultado.className = response.ok ? "form-message sucesso" : "form-message erro";

    if (response.ok) {
        limparFormulario(form);
    }
});
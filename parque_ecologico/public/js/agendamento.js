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

document.getElementById("agendamento-form")
?.addEventListener("submit", async function (e) {

    e.preventDefault();

    const form = e.target;
    const resultado = document.getElementById("resultado");

    if (!form.checkValidity()) {
        resultado.innerText = "Campo obrigatório não preenchido.";
        resultado.className = "form-message erro";
        form.reportValidity();
        return;
    }

    const formData = new FormData(form);

    const data = {
        nome_responsavel: formData.get("nome_responsavel"),
        telefone: formData.get("telefone"),
        email: formData.get("email"),
        data_reserva: formData.get("data_reserva"),
        quiosque_id: parseInt(formData.get("quiosque_id")),
        qtd_visitantes: parseInt(formData.get("qtd_visitantes")),
        horario_entrada: formData.get("horario_entrada"),
        horario_saida: formData.get("horario_saida"),
        aceite_termos: formData.get("aceite_termos") ? 1 : 0
    };

    const botao = form.querySelector("button");

    botao.disabled = true;
    botao.textContent = "Enviando...";

    try {

        const response = await fetch(
            "/parque_ecologico/api/agendamentos/enviar",
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(data)
            }
        );

        const result = await response.json();

        resultado.innerText =
            result.erro ||
            result.mensagem ||
            "Solicitação processada";

        resultado.className =
            response.ok
                ? "form-message sucesso"
                : "form-message erro";

        if (response.ok) {
            limparFormulario(form);
        }

    } catch (error) {

        resultado.innerText =
            "Erro ao enviar. Tente novamente.";

        resultado.className =
            "form-message erro";

    } finally {

        botao.disabled = false;
        botao.textContent = "Enviar Agendamento";
    }
});
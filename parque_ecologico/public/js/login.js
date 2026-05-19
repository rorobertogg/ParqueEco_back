const BASE_URL = "/parque_ecologico";

const loginForm = document.getElementById("login-form");
const tabLogin = document.getElementById("tab-login");
const msg = document.getElementById("login-msg");

tabLogin?.addEventListener("click", () => {
    loginForm.style.display = "block";
    msg.innerText = "";
});

loginForm?.addEventListener("submit", async (e) => {
    e.preventDefault();

    const data = {
        usuario: document.getElementById("login_usuario").value,
        senha: document.getElementById("login_senha").value
    };

    const response = await fetch(`${BASE_URL}/api/auth/login`, {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        credentials: "include",
        body: JSON.stringify(data)
    });

    const result = await response.json();

    if (response.ok) {
        msg.innerText = result.mensagem;

        setTimeout(() => {
            const userTipo = String(result.tipo || '').toLowerCase().trim();
            if (userTipo === "admin") {
                window.location.href = `${BASE_URL}/admin`;
            } else {
                window.location.href = `${BASE_URL}/agendamento`;
            }
        }, 700);

        return;
    }

    msg.innerText = result.erro;
});

// Registration removed: users are managed directly in the database by admins.
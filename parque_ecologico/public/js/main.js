console.log("Parque Ecológico - Sistema carregado");

function mostrarMensagem(elemento, mensagem, tipo = "sucesso") {
    const msgElement = document.getElementById(elemento);

    if (!msgElement) {
        return;
    }

    msgElement.textContent = mensagem;
    msgElement.className = `form-message ${tipo}`;
    msgElement.style.display = "block";

    setTimeout(() => {
        msgElement.style.display = "none";
    }, 5000);
}

document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".faq-question").forEach((button) => {
        button.addEventListener("click", () => {
            const faqItem = button.parentElement;
            faqItem.classList.toggle("active");
        });
    });

    document.querySelectorAll(".gallery-carousel").forEach((carousel) => {
        const slides = Array.from(carousel.querySelectorAll(".gallery-slide"));
        const prev = carousel.querySelector(".gallery-prev");
        const next = carousel.querySelector(".gallery-next");
        const dotsContainer = carousel.querySelector(".gallery-dots");

        if (slides.length === 0) {
            return;
        }

        let current = 0;
        let timer = null;

        const dots = slides.map((_, index) => {
            const dot = document.createElement("button");
            dot.type = "button";
            dot.className = "gallery-dot";
            dot.setAttribute("aria-label", `Mostrar imagem ${index + 1}`);
            dot.addEventListener("click", () => {
                showSlide(index);
                restartTimer();
            });
            dotsContainer?.appendChild(dot);
            return dot;
        });

        function showSlide(index) {
            current = (index + slides.length) % slides.length;

            slides.forEach((slide, slideIndex) => {
                slide.classList.toggle("active", slideIndex === current);
            });

            dots.forEach((dot, dotIndex) => {
                dot.classList.toggle("active", dotIndex === current);
            });
        }

        function restartTimer() {
            window.clearInterval(timer);
            timer = window.setInterval(() => showSlide(current + 1), 5000);
        }

        prev?.addEventListener("click", () => {
            showSlide(current - 1);
            restartTimer();
        });

        next?.addEventListener("click", () => {
            showSlide(current + 1);
            restartTimer();
        });

        carousel.addEventListener("mouseenter", () => window.clearInterval(timer));
        carousel.addEventListener("mouseleave", restartTimer);

        showSlide(0);
        restartTimer();
    });
});

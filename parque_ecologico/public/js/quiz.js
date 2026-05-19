const QUIZ_QUESTIONS = [
    {
        question: "Qual dessas árvores é conhecida historicamente por ter dado nome ao Brasil?",
        options: ["Mangueira", "Pau-Brasil", "Figueira", "Ipê"],
        answer: "Pau-Brasil"
    },
    {
        question: "O Pau-Brasil era muito explorado principalmente por causa de:",
        options: ["Seu fruto comestível", "Sua madeira avermelhada usada para extração de tinta", "Suas flores perfumadas", "Sua sombra intensa"],
        answer: "Sua madeira avermelhada usada para extração de tinta"
    },
    {
        question: "A Grumixama é conhecida principalmente por produzir:",
        options: ["Flores roxas", "Frutos escuros comestíveis", "Espinhos grandes", "Madeira para construção"],
        answer: "Frutos escuros comestíveis"
    },
    {
        question: "A Grumixama pertence ao mesmo grupo botânico de qual fruta popular?",
        options: ["Maçã", "Jabuticaba", "Banana", "Uva"],
        answer: "Jabuticaba"
    },
    {
        question: "O Ipê é muito conhecido no Brasil principalmente por:",
        options: ["Produzir cocos", "Suas florações muito vistosas", "Ser uma árvore aquática", "Produzir resina medicinal"],
        answer: "Suas florações muito vistosas"
    },
    {
        question: "Uma característica comum dos Ipês é:",
        options: ["Florescer com pouca ou nenhuma folha", "Crescer somente em mangues", "Produzir frutos subterrâneos", "Ter tronco oco naturalmente"],
        answer: "Florescer com pouca ou nenhuma folha"
    },
    {
        question: "A Mangueira é famosa principalmente por produzir:",
        options: ["Jabuticaba", "Manga", "Pitanga", "Goiaba"],
        answer: "Manga"
    },
    {
        question: "A Mangueira é uma árvore geralmente:",
        options: ["De pequeno porte", "Frutífera e de grande copa", "Exclusivamente ornamental", "Aquática"],
        answer: "Frutífera e de grande copa"
    },
    {
        question: "A Paineira é bastante reconhecida por:",
        options: ["Produzir algodão natural em seus frutos", "Produzir pinhas grandes", "Ter folhas azuis", "Crescer apenas no litoral"],
        answer: "Produzir algodão natural em seus frutos"
    },
    {
        question: "O tronco da Paineira costuma apresentar:",
        options: ["Espinhos", "Frutos subterrâneos", "Casca branca lisa sem marcas", "Raízes aéreas"],
        answer: "Espinhos"
    },
    {
        question: "A Figueira é conhecida por apresentar com frequência:",
        options: ["Raízes muito superficiais e largas", "Tronco de bambu", "Folhas em formato de agulha", "Flores azuis grandes"],
        answer: "Raízes muito superficiais e largas"
    },
    {
        question: "Muitas espécies de Figueira pertencem ao gênero:",
        options: ["Pinus", "Ficus", "Mangifera", "Caesalpinia"],
        answer: "Ficus"
    },
    {
        question: "A Leucena é bastante utilizada porque possui:",
        options: ["Crescimento rápido", "Flores subterrâneas", "Tronco com espinhos gigantes", "Frutos aquáticos"],
        answer: "Crescimento rápido"
    },
    {
        question: "A Leucena é frequentemente usada em:",
        options: ["Recuperação de áreas e sombreamento", "Produção de pinhas", "Plantio aquático", "Cultivo em neve"],
        answer: "Recuperação de áreas e sombreamento"
    },
    {
        question: "O Pinheiro é facilmente reconhecido por:",
        options: ["Produzir frutos cítricos", "Suas folhas em forma de agulha", "Suas flores gigantes vermelhas", "Seu tronco transparente"],
        answer: "Suas folhas em forma de agulha"
    },
    {
        question: "No Brasil, um exemplo muito conhecido associado ao termo “Pinheiro” é:",
        options: ["Araucária", "Bananeira", "Coqueiro", "Goiabeira"],
        answer: "Araucária"
    },
    {
        question: "O Pau-Jacaré recebe esse nome popular geralmente por causa de:",
        options: ["Sua casca com aparência semelhante ao couro de jacaré", "Seus frutos em forma de jacaré", "Suas flores verdes", "Seu cheiro forte"],
        answer: "Sua casca com aparência semelhante ao couro de jacaré"
    },
    {
        question: "O Pau-Jacaré é valorizado principalmente por:",
        options: ["Sua madeira e presença em arborização", "Produzir mangas", "Crescer apenas dentro d’água", "Produzir algodão"],
        answer: "Sua madeira e presença em arborização"
    },
    {
        question: "O Pessegueiro-Bravo recebe esse nome por lembrar:",
        options: ["O pinheiro", "O pessegueiro comum", "A mangueira", "O coqueiro"],
        answer: "O pessegueiro comum"
    },
    {
        question: "O Pessegueiro-Bravo é mais conhecido como:",
        options: ["Árvore ornamental e de mata nativa", "Planta aquática", "Espécie de cacto", "Tipo de bambu"],
        answer: "Árvore ornamental e de mata nativa"
    }
];

document.addEventListener("DOMContentLoaded", () => {
    const app = document.querySelector("[data-quiz-app]");

    if (!app) {
        return;
    }

    const stepEl = document.getElementById("quiz-step");
    const scoreEl = document.getElementById("quiz-score");
    const questionEl = document.getElementById("quiz-question");
    const optionsEl = document.getElementById("quiz-options");
    const feedbackEl = document.getElementById("quiz-feedback");
    const nextBtn = document.getElementById("quiz-next");
    const restartBtn = document.getElementById("quiz-restart");
    const progressEl = document.getElementById("quiz-progress");

    let questions = [];
    let current = 0;
    let score = 0;
    let answered = false;

    function shuffle(items) {
        const copy = [...items];

        for (let i = copy.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [copy[i], copy[j]] = [copy[j], copy[i]];
        }

        return copy;
    }

    function startGame() {
        questions = shuffle(QUIZ_QUESTIONS).slice(0, 10).map((item) => ({
            ...item,
            options: shuffle(item.options)
        }));
        current = 0;
        score = 0;
        answered = false;
        scoreEl.textContent = score;
        restartBtn.textContent = "Jogar novamente";
        renderQuestion();
    }

    function renderQuestion() {
        const item = questions[current];
        answered = false;
        optionsEl.innerHTML = "";
        feedbackEl.textContent = "";
        feedbackEl.className = "game-feedback";
        nextBtn.disabled = true;
        nextBtn.textContent = current === questions.length - 1 ? "Ver resultado" : "Próxima";

        stepEl.textContent = `Pergunta ${current + 1} de ${questions.length}`;
        questionEl.textContent = item.question;
        progressEl.style.width = `${(current / questions.length) * 100}%`;

        item.options.forEach((option, index) => {
            const button = document.createElement("button");
            button.type = "button";
            button.className = "quiz-option";
            button.innerHTML = `<span>${String.fromCharCode(65 + index)}</span>${option}`;
            button.addEventListener("click", () => selectAnswer(button, option));
            optionsEl.appendChild(button);
        });
    }

    function selectAnswer(button, option) {
        if (answered) {
            return;
        }

        const item = questions[current];
        const isCorrect = option === item.answer;
        answered = true;

        if (isCorrect) {
            score++;
            scoreEl.textContent = score;
        }

        optionsEl.querySelectorAll(".quiz-option").forEach((optionButton) => {
            optionButton.disabled = true;

            if (optionButton.textContent.includes(item.answer)) {
                optionButton.classList.add("correct");
            }
        });

        if (!isCorrect) {
            button.classList.add("wrong");
        }

        feedbackEl.textContent = isCorrect ? "Resposta correta." : `Resposta correta: ${item.answer}.`;
        feedbackEl.classList.add(isCorrect ? "success" : "error");
        nextBtn.disabled = false;
        progressEl.style.width = `${((current + 1) / questions.length) * 100}%`;
    }

    function renderResult() {
        optionsEl.innerHTML = "";
        stepEl.textContent = "Resultado";
        questionEl.textContent = `Você acertou ${score} de ${questions.length} perguntas.`;
        feedbackEl.textContent = score >= 8 ? "Excelente! Você conhece muito bem as espécies do parque." : "Boa tentativa! Jogue novamente para ver outras perguntas.";
        feedbackEl.className = "game-feedback success";
        nextBtn.disabled = true;
        progressEl.style.width = "100%";
    }

    nextBtn.addEventListener("click", () => {
        if (current < questions.length - 1) {
            current++;
            renderQuestion();
        } else {
            renderResult();
        }
    });

    restartBtn.addEventListener("click", startGame);

    startGame();
});

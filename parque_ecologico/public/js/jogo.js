const WORD_TERMS = [
    "Natureza",
    "Floresta",
    "Parque",
    "Árvore",
    "Raiz",
    "Folha",
    "Semente",
    "Tronco",
    "Galho",
    "Sombra",
    "Bosque",
    "Verde",
    "Fauna",
    "Flora",
    "Rio",
    "Solo",
    "Fruto",
    "Jardim",
    "Ecologia",
    "Preservação"
];

document.addEventListener("DOMContentLoaded", () => {
    const app = document.querySelector("[data-word-search-app]");

    if (!app) {
        return;
    }

    const size = 13;
    const directions = [
        { row: 0, col: 1 },    // → Direita
        { row: 0, col: -1 },   // ← Esquerda
        { row: 1, col: 0 },    // ↓ Baixo
        { row: -1, col: 0 },   // ↑ Acima
        { row: 1, col: 1 },    // ↘ Diagonal
        { row: -1, col: -1 },  // ↖ Diagonal
        { row: 1, col: -1 },   // ↙ Diagonal
        { row: -1, col: 1 }    // ↗ Diagonal
    ];
    const alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";

    const gridEl = document.getElementById("word-grid");
    const listEl = document.getElementById("word-list");
    const messageEl = document.getElementById("word-message");
    const statusEl = document.getElementById("word-status");
    const newBtn = document.getElementById("word-new");
    const restartBtn = document.getElementById("word-restart");

    let selectedTerms = [];
    let foundWords = new Set();
    let grid = [];
    let startCell = null;
    let selectedCells = [];

    function normalizeWord(word) {
        return word
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .replace(/[^A-Za-z]/g, "")
            .toUpperCase();
    }

    function shuffle(items) {
        const copy = [...items];

        for (let i = copy.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [copy[i], copy[j]] = [copy[j], copy[i]];
        }

        return copy;
    }

    function emptyGrid() {
        return Array.from({ length: size }, () => Array.from({ length: size }, () => ""));
    }

    function canPlace(word, row, col, direction) {
        for (let i = 0; i < word.length; i++) {
            const nextRow = row + direction.row * i;
            const nextCol = col + direction.col * i;

            if (nextRow < 0 || nextRow >= size || nextCol < 0 || nextCol >= size) {
                return false;
            }

            const current = grid[nextRow][nextCol];

            if (current !== "" && current !== word[i]) {
                return false;
            }
        }

        return true;
    }

    function placeWord(term) {
        const word = normalizeWord(term);

        for (let attempt = 0; attempt < 300; attempt++) {
            const direction = directions[Math.floor(Math.random() * directions.length)];
            const row = Math.floor(Math.random() * size);
            const col = Math.floor(Math.random() * size);

            if (!canPlace(word, row, col, direction)) {
                continue;
            }

            for (let i = 0; i < word.length; i++) {
                const nextRow = row + direction.row * i;
                const nextCol = col + direction.col * i;
                grid[nextRow][nextCol] = word[i];
            }

            return true;
        }

        return false;
    }

    function fillGrid() {
        for (let row = 0; row < size; row++) {
            for (let col = 0; col < size; col++) {
                if (!grid[row][col]) {
                    grid[row][col] = alphabet[Math.floor(Math.random() * alphabet.length)];
                }
            }
        }
    }

    function generateGame() {
        let placed = false;

        while (!placed) {
            grid = emptyGrid();
            foundWords = new Set();
            startCell = null;
            selectedCells = [];
            selectedTerms = shuffle(WORD_TERMS).slice(0, 7);
            placed = selectedTerms.every(placeWord);
        }

        fillGrid();
        renderGrid();
        renderWordList();
        updateStatus();
        setMessage("Clique na primeira e na última letra de uma palavra.");
    }

    function renderGrid() {
        gridEl.innerHTML = "";
        gridEl.style.setProperty("--grid-size", size);

        for (let row = 0; row < size; row++) {
            for (let col = 0; col < size; col++) {
                const cell = document.createElement("button");
                cell.type = "button";
                cell.className = "word-cell";
                cell.textContent = grid[row][col];
                cell.dataset.row = row;
                cell.dataset.col = col;
                cell.addEventListener("click", () => handleCellClick(cell));
                gridEl.appendChild(cell);
            }
        }
    }

    function renderWordList() {
        listEl.innerHTML = "";

        selectedTerms.forEach((term) => {
            const item = document.createElement("li");
            item.textContent = term;
            item.dataset.word = normalizeWord(term);
            listEl.appendChild(item);
        });
    }

    function handleCellClick(cell) {
        const row = Number(cell.dataset.row);
        const col = Number(cell.dataset.col);

        if (!startCell) {
            clearSelection();
            startCell = { row, col };
            cell.classList.add("selected");
            selectedCells = [cell];
            return;
        }

        const path = getPath(startCell, { row, col });

        if (!path) {
            clearSelection();
            startCell = { row, col };
            cell.classList.add("selected");
            selectedCells = [cell];
            setMessage("Escolha letras em linha reta.");
            return;
        }

        const letters = path.map((position) => grid[position.row][position.col]).join("");
        const reversed = letters.split("").reverse().join("");
        const match = selectedTerms.find((term) => {
            const normalized = normalizeWord(term);
            return !foundWords.has(normalized) && (normalized === letters || normalized === reversed);
        });

        highlightPath(path, Boolean(match));

        if (match) {
            const normalizedMatch = normalizeWord(match);
            foundWords.add(normalizedMatch);
            markWordFound(normalizedMatch);
            setMessage(`Encontrou: ${match}.`, "success");
            updateStatus();
        } else {
            setMessage("Essa seleção não está na lista.", "error");
            window.setTimeout(() => clearTemporarySelection(), 700);
        }

        startCell = null;
        selectedCells = [];
    }

    function getPath(start, end) {
        const rowDiff = end.row - start.row;
        const colDiff = end.col - start.col;
        const rowStep = Math.sign(rowDiff);
        const colStep = Math.sign(colDiff);
        const isHorizontal = rowDiff === 0 && colDiff !== 0;
        const isVertical = colDiff === 0 && rowDiff !== 0;
        const isDiagonal = Math.abs(rowDiff) === Math.abs(colDiff) && rowDiff !== 0;

        if (!isHorizontal && !isVertical && !isDiagonal) {
            return null;
        }

        const length = Math.max(Math.abs(rowDiff), Math.abs(colDiff)) + 1;
        const path = [];

        for (let i = 0; i < length; i++) {
            path.push({
                row: start.row + rowStep * i,
                col: start.col + colStep * i
            });
        }

        return path;
    }

    function highlightPath(path, isFound) {
        clearTemporarySelection();

        path.forEach((position) => {
            const cell = getCell(position.row, position.col);

            if (cell) {
                cell.classList.add(isFound ? "found" : "selected");
            }
        });
    }

    function clearSelection() {
        gridEl.querySelectorAll(".word-cell.selected").forEach((cell) => {
            cell.classList.remove("selected");
        });
    }

    function clearTemporarySelection() {
        gridEl.querySelectorAll(".word-cell.selected").forEach((cell) => {
            cell.classList.remove("selected");
        });
    }

    function getCell(row, col) {
        return gridEl.querySelector(`[data-row="${row}"][data-col="${col}"]`);
    }

    function markWordFound(word) {
        const item = listEl.querySelector(`[data-word="${word}"]`);

        if (item) {
            item.classList.add("found");
        }
    }

    function updateStatus() {
        statusEl.textContent = `${foundWords.size} de ${selectedTerms.length} encontradas`;

        if (foundWords.size === selectedTerms.length) {
            setMessage("Você encontrou todas as palavras.", "success");
        }
    }

    function setMessage(text, type = "") {
        messageEl.textContent = text;
        messageEl.className = `game-feedback ${type}`.trim();
    }

    newBtn.addEventListener("click", generateGame);
    restartBtn.addEventListener("click", generateGame);

    generateGame();
});

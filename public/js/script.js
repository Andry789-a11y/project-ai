document.addEventListener("DOMContentLoaded", function () {
    const loadingDiv = document.getElementById("loading");
    const inputFile = document.getElementById("fileInput");
    const inputText = document.getElementById("chatInput");
    const chatBox = document.getElementById("chatMessages");
    const placeholder = document.getElementById('placeholder');

    function updatePlaceholderVisibility() {
        if (chatBox.children.length > 0) {
            placeholder.style.display = 'none';
        } else {
            placeholder.style.display = 'block';
        }
    }

    inputText.addEventListener("keydown", function (e) {
        if (e.key === "Enter") {
            e.preventDefault();
            window.handleSubmit();
        }
    });

    inputFile.addEventListener("change", function (e) {
        if (inputFile.files.length > 0) {
            inputText.value = inputFile.files[0].name;
            inputText.focus();
        } else {
            inputText.value = "";
        }
    });

    function showLoading() {
        if (loadingDiv) {
            loadingDiv.style.display = "flex";
            loadingDiv.style.opacity = "0";
            loadingDiv.classList.remove("fade-in", "fade-out");
            setTimeout(() => {
                loadingDiv.style.opacity = "1";
            }, 50);
        }
    }

    function hideLoading() {
        if (loadingDiv) {
            loadingDiv.classList.add("fade-out");
            setTimeout(() => {
                loadingDiv.style.display = "none";
            }, 500);
        }
    }

    function appendAIMessage(text, audioUrl = null) {
        const row = document.createElement('div');
        row.className = 'chat-row ai';
        const avatar = document.createElement('div');
        avatar.className = 'avatar';
        const img = document.createElement('img');
        img.src = "/images/icons8-ai-96.png";
        img.alt = "AI Icon";
        avatar.appendChild(img);
        row.appendChild(avatar);
        const bubble = document.createElement('div');
        bubble.className = 'chat-bubble ai';
        const message = document.createElement('div');
        message.style.margin = '0';
        message.innerHTML = marked.parse(text);
        bubble.appendChild(message);
        if (audioUrl) {
            const audio = document.createElement('audio');
            audio.controls = true;
            audio.src = audioUrl;
            audio.className = 'chat-audio';
            bubble.appendChild(audio);
        }
        row.appendChild(bubble);
        chatBox.appendChild(row);
        chatBox.scrollTop = chatBox.scrollHeight;
        updatePlaceholderVisibility();
    }

    function appendUserMessage(text) {
        const row = document.createElement('div');
        row.className = 'chat-row user';
        const bubble = document.createElement('div');
        bubble.className = 'chat-bubble user';
        const message = document.createElement('p');
        message.textContent = text;
        bubble.appendChild(message);
        row.appendChild(bubble);
        chatBox.appendChild(row);
        chatBox.scrollTop = chatBox.scrollHeight;
        updatePlaceholderVisibility();
    }

    function showTypingIndicator() {
        if (document.getElementById("typing-indicator")) return;
        const row = document.createElement('div');
        row.className = 'chat-row ai';
        const bubble = document.createElement("div");
        bubble.className = "chat-bubble typing";
        bubble.id = "typing-indicator";
        bubble.textContent = "DryyMate sedang mengetik...";
        row.appendChild(bubble);
        chatBox.appendChild(row);
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    function removeTypingIndicator() {
        const bubble = document.getElementById("typing-indicator");
        if (bubble) bubble.remove();
    }

    window.handleSubmit = function () {
        if (event) event.preventDefault();

        const text = inputText.value.trim();
        const file = inputFile.files[0];

        if (file && text === file.name) {
            const formData = new FormData();
            formData.append('file', file);
            showLoading();

            appendUserMessage(file.name);

            fetch('/ai/upload', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    hideLoading();
                    showTypingIndicator();
                    setTimeout(() => {
                        removeTypingIndicator();
                        if (!data.summary) {
                            appendAIMessage("Terjadi kesalahan internal!");
                        } else {
                            appendAIMessage(data.summary, data.audio_url);
                        }
                        inputFile.value = "";
                        inputText.value = "";
                    }, 500);
                })
                .catch(() => {
                    hideLoading();
                    alert("Gagal mengunggah file PDF!");
                    updatePlaceholderVisibility();
                });
        } else if (text !== "") {
            appendUserMessage(text);
            inputText.value = "";
            showTypingIndicator();

            const lower = text.toLowerCase();
            if (
                lower.includes("kesimpulan dari pdf") ||
                lower.includes("apa isi pdf") ||
                lower.includes("ringkasan terakhir") ||
                lower.includes("isi dokumen yang saya kirim")
            ) {
                fetch('/get-last-summary')
                    .then(res => res.json())
                    .then(data => {
                        removeTypingIndicator();
                        appendAIMessage(data.summary || "Tidak ada ringkasan PDF yang ditemukan");
                    })
                    .catch(() => {
                        removeTypingIndicator();
                        appendAIMessage("Gagal mengambil kesimpulan ringkasan PDF");
                    });
                return;
            }
            fetch('/ai/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ message: text })
            })
                .then(res => res.json())
                .then(data => {
                    hideLoading();
                    removeTypingIndicator();
                    appendAIMessage(data.reply);
                })
                .catch(() => {
                    hideLoading();
                    removeTypingIndicator();
                    appendAIMessage("Gagal mendapatkan balasan!!");
                });
        }
    };

    updatePlaceholderVisibility();
});

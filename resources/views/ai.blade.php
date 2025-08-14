<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DryyMate</title>
    <link rel="icon" href="{{ asset('images/ai-technology.png') }}" type="image/png">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <script src="{{ asset('js/script.js') }}"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
</head>

<body>
    <main id="mainContent" class="main">
        <a href="/ai" class="title" style="text-decoration: none; color: #3f82f7;">
            <img src="{{ asset('images/ai-technology.png') }}" alt="Logo" style="vertical-align: middle;">
            DryyMate AI
        </a>

        @if (isset($summary))
            <div class="result-section" id="result-section">
                <h3>Ringkasan PDF :</h3>
                <p style="text-align: justify;">{{ $summary }}</p>

                @if (isset($audio_url))
                    <audio controls>
                        <source src="{{ $audio_url }}" type="audio/mpeg">
                    </audio>
                @endif

                @if (isset($error))
                    <p style="color: red;">{{ $error }}</p>
                @endif
            </div>
        @endif

        <div id="placeholder" class="placeholder">
            Apa yang bisa saya lakukan hari ini?
        </div>

        <div id="chatMessages" class="chat-messages"></div>

        <div class="chat-bar">
            @csrf
            <button type="button" class="upload-btn" onclick="document.getElementById('fileInput').click()">+</button>
            <input type="text" id="chatInput" class="file-name" placeholder="Tanyakan DryyMate..."
                autocomplete="off" />
            <button type="button" class="send-btn" onclick="handleSubmit(event)">➤</button>
            <input type="file" name="file" id="fileInput" style="display: none;" />
        </div>
    </main>


</body>

</html>

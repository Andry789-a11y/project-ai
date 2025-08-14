from flask import Flask, request, send_file
from gtts import gTTS
from pydub import AudioSegment
import os
import re

app = Flask(__name__, static_folder='static')

def bersihkan_teks(teks):
    teks = re.sub(r'(\*\*|__)(.*?)\1', r'\2', teks)
    teks = re.sub(r'(\*|_)(.*?)\1', r'\2', teks)
    teks = re.sub(r'#+ ', '', teks)
    teks = re.sub(r'[^\w\s,.!?-]', '', teks)
    return teks

@app.route('/tts', methods=['POST'])
def tts_endpoint():
    data = request.get_json()
    text = data.get("text", "")
    lang = data.get("lang", "id")
    try:
        speed = float(data.get("speed", 1.0))
    except:
        speed = 1.0

    if not text:
        return {"error": "Text tidak boleh kosong"}, 400

    teks_bersih = bersihkan_teks(text)

    try:
        audio_dir = os.path.join(os.path.dirname(__file__), 'static', 'audio')
        os.makedirs(audio_dir, exist_ok=True)
        filename = "output.mp3"
        filepath = os.path.abspath(os.path.join(audio_dir, filename))

        tts = gTTS(text=teks_bersih, lang=lang)
        tts.save(filepath)

        if not os.path.exists(filepath):
            return {"error": "File audio gagal dibuat!"}, 500

        if speed != 1.0:
            sound = AudioSegment.from_file(filepath)
            faster_sound = sound.speedup(playback_speed=speed, chunk_size=50, crossfade=25)
            faster_sound.export(filepath, format="mp3")

        return send_file(filepath, mimetype='audio/mpeg')

    except Exception as e:
        print("Error saat proses TTS:", e)
        import traceback
        traceback.print_exc()
        return {"error": "Terjadi kesalahan saat proses TTS"}, 500

if __name__ == '__main__':
    app.run(debug=True, port=5000)

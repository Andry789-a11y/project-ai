# DryyMate AI

DryyMate AI adalah AI berbasis **Laravel** dengan integrasi **AI Gemini** yang mampu:
- Meringkas dokumen PDF.
- Membacakan ringkasan dengan suara (Text-to-Speech).
- Chat interaktif seperti ChatGPT.

## 🚀 Fitur Utama
- **Upload PDF** → AI otomatis membaca dan meringkas isi dokumen.
- **Ringkasan dengan Suara** → Hasil ringkasan dibacakan secara otomatis.
- **Chat AI** → Bisa tanya jawab seputar dokumen atau topik lain.
- **Dark Mode UI** → Tampilan modern dan nyaman digunakan.

## 📂 Struktur Project
dryymate/
│-- app/
│-- public/
│-- resources/
│-- routes/
│-- storage/
│-- .env
│-- composer.json
│-- README.md

## ⚙️ Instalasi
1. **Clone Repository**
   ```bash
   git clone https://github.com/Andry789-a11y/project-ai.git
   cd project-ai

2. Install Dependencies :
 - composer install
 - npm install
 - npm run build

3. Salin File .env :
 - cp .env.example .env
 
4. Generate Key Laravel :
  - php artisan key:generate

5. Jalankan Server :
  - php artisan serve

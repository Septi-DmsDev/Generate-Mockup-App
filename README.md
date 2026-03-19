# Generate Mockup App

Aplikasi web terintegrasi untuk mengunggah, mengedit, dan melakukan *generate mockup* desain secara dinamis. Dilengkapi dengan *custom font library* dan antarmuka editor visual (*front-end*) untuk menyesuaikan *mockup* sebelum dirender menjadi hasil akhir.

## 🚀 Fitur Utama

**Editor Visual & Tipografi:**
* **Workspace Editor:** Antarmuka pengeditan *mockup* dengan kontrol elemen UI (`editor.js`, `editor.css`).
* **Custom Font Integration:** Mendukung lebih dari 50+ *font* kustom (seperti Berkshire Swash, Boheme Floral, dll.) untuk *rendering* teks yang fleksibel dan estetis.

**Manajemen File & Aset:**
* **Upload System:** Direktori khusus untuk mengelola file gambar mentah yang diunggah pengguna (`uploads/`).
* **Temporary Storage:** Pemrosesan file sementara saat proses *editing* berlangsung (`temp_editor/`).
* **Result Generator:** Penyimpanan hasil akhir *mockup* yang siap diunduh (`temp_results/`).

**Manajemen Pengguna (Opsional):**
* **User Directory:** Modul untuk mengelola aset atau sesi spesifik milik pengguna (`user/`).

## 🛠️ Tech Stack

* **Front-End:** HTML5, CSS3, Vanilla JavaScript (Editor Logic).
* **Back-End:** PHP (Image Processing/Rendering).
* **Assets:** TrueType Fonts (.ttf), Image Files.

## 📂 Struktur Direktori

```text
├── editor.js, editor.css       # Core logic dan styling untuk antarmuka editor mockup
├── fonts/                      # Direktori koleksi file font kustom (.ttf)
├── uploads/                    # Direktori penyimpanan file gambar mentah/aset upload
├── temp_editor/                # Direktori cache/file sementara selama proses editing
├── temp_results/               # Direktori penyimpanan file hasil generate mockup
└── user/                       # Direktori data/aset spesifik per pengguna
⚙️ Panduan Instalasi (Development)
Kloning repositori ini ke document root server lokal lu (XAMPP/LAMP).

Pastikan direktori uploads/, temp_editor/, dan temp_results/ memiliki hak akses tulis (write permission / chmod 777 atau 775 di Linux) agar skrip PHP bisa menyimpan file gambar.

Jika ada database yang menyertai, konfigurasikan koneksinya.

Akses melalui browser di http://localhost/<nama-folder>/.

🛡️ Catatan Keamanan & Server (Mandatory)
Validasi Upload: Pastikan skrip yang menangani upload memvalidasi ekstensi file (hanya izinkan .jpg, .png, .jpeg) dan memverifikasi MIME type untuk mencegah injeksi web shell (file .php yang disusupkan).

Storage Limit: Direktori temp_* akan cepat membengkak seiring penggunaan. Wajib dipasang mekanisme pembersihan otomatis.
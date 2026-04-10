FOKUS KE BACKEND!FRONTEND DIBUAT DIBEDA REPOSITORY, WELL SAYA PAKAI LARAVEL 12, ADJUST AJA PROJECTNYA, TETAP PAKAI LARAVEL 12




Karena kita pakai stack Laravel (Backend) + React (Frontend), kita bakal pakai kombinasi "The Free Trio" yang paling stabil buat demo:1. Database: Aiven (MySQL Free Tier)Lu butuh database yang mendukung spatial data dan bisa diakses secara online oleh backend lu.Kenapa: Aiven punya free tier buat MySQL yang sudah mendukung fitur spatial (koordinat).Cara: Daftar di aiven.io, buat instance MySQL gratis, lalu simpan datanya buat di-input ke .env Laravel lu.2. Backend: Render (Laravel)Render adalah pilihan gratisan terbaik buat hosting aplikasi PHP/Laravel saat ini.Kenapa: Gratis, gampang konek ke GitHub, dan proses deploy-nya otomatis.Catatan: Karena gratisan, kalau aplikasinya nggak dibuka dalam waktu lama, dia bakal "tidur" (spin down). Jadi pas mau interview, pastikan lu buka dulu link-nya 1 menit sebelum mulai biar dia "bangun".3. Frontend: Vercel (React)Buat urusan frontend React, nggak ada yang ngalahin Vercel.Kenapa: Sangat cepat, gratis selamanya (buat personal), dan langsung dapet HTTPS (biar kelihatan aman).Cara: Cukup push folder frontend lu ke GitHub, lalu import di Vercel.🛠️ Rangkuman Skema Deployment GratisanKomponenPlatformPeranFrontendVercelNampilin peta Leaflet.js dan UI Dashboard lu.BackendRenderAPI Laravel buat ambil data koordinat lahan.DatabaseAivenSimpan tabel dengan tipe data POLYGON dan POINT.💡 Tips Biar "Kelihatan" Mahal (Walau Gratisan)Gunakan Custom Subdomain: Kalau lu punya domain sendiri, pasang aja. Kalau nggak, nama di Vercel/Render-nya dibuat keren, misal: geo-acquire-paramount.vercel.app.Seeding Data: Jangan biarkan peta lu kosong pas dibuka. Pakai fitur Seeder di Laravel buat masukin sekitar 10-20 titik lahan dummy di area Gading Serpong (biar relevan sama Paramount).HTTPS: Semua platform di atas otomatis kasih SSL (HTTPS). Ini penting karena beberapa browser bakal blokir fitur lokasi/peta kalau aplikasinya cuma HTTP biasa.Saran gue:Eksekusi di Render seringkali butuh sedikit konfigurasi di file Dockerfile atau setting build command. Kalau lu udah siap mau deploy, kasih tau gue, nanti gue bantu buatin command build yang pas buat Laravel 11 lu biar nggak error pas di-upload.



Ini ide *project portfolio* yang dijamin bakal bikin HRD dan *User* di Paramount Enterprise (perusahaan properti) langsung mikir, *"Wah, anak ini paham bisnis kita nih!"*

Karena mereka fokusnya di pengembangan kota terpadu dan pembebasan lahan (Land Acquisition), lu harus bikin aplikasi yang nyelesaiin masalah itu. 

### Nama Project: **GeoAcquire** (Land Acquisition & Spatial Analysis Dashboard)

**Deskripsi Singkat:** 
Sebuah *web app* untuk memetakan, mengelola, dan menganalisis status pembebasan lahan secara visual. Sistem ini mengubah data tabular menjadi peta interaktif untuk membantu divisi pengadaan lahan mengambil keputusan lebih cepat.

---

### 💡 Core Features (Fitur Utama)

1.  **Interactive Land Map (Peta Interaktif):**
    *   Menampilkan poligon (bentuk tanah) di atas peta dasar (Google Maps/OpenStreetMap).
    *   *Color-coding* otomatis: Hijau (Sudah Dibebaskan), Kuning (Sedang Nego), Merah (Target Belum Disentuh).
2.  **Spatial Data Analysis (Analisis Spasial):**
    *   Fitur menghitung total luas area (dalam meter persegi) secara otomatis berdasarkan bentuk poligon yang digambar atau di-*upload*.
    *   **Buffer Zone Analysis:** Fitur untuk mencari "Tampilkan semua lahan yang jaraknya maksimal 500 meter dari rencana jalan tol."
3.  **GeoJSON Import/Export via Python:**
    *   Fitur di mana *user* bisa *upload* data peta (*Shapefile/GeoJSON*) yang biasa dipakai orang *Engineer*, lalu di-*parsing* masuk ke *database* MySQL.

---

### 🛠️ Tech Stack (Disesuaikan sama keahlian lu)

*   **Backend:** PHP (Laravel 11). Lu bikin REST API yang *return* datanya berbentuk GeoJSON, bukan JSON biasa.
*   **Database:** MySQL 8.0. Pakai tipe data `POLYGON` untuk simpan bentuk tanah dan `POINT` untuk titik kordinat. Jangan lupa set *Spatial Index* biar *query* pencarian lokasinya ngebut.
*   **Frontend:** React 18 + Tailwind CSS. 
*   **GIS Library (Wajib masukin ini!):** **Leaflet.js** atau **Mapbox GL JS** di dalam React lu. Ini *library* JS standar industri buat nampilin peta.
*   **Data Processing:** Python (GeoPandas). Lu bikin satu *script* kecil pakai Python buat ngebaca file peta (*GeoJSON*), ngecek validitas koordinatnya, lalu *push* ke *database*. Ini membuktikan lu paham poin *"Python for Data Analysis"*.

---

### 🚀 Cara Eksekusi pake VS Code & Claude Code

Lu bisa kerjain ini cepat kalau lu manfaatin AI dengan *prompt* yang tepat. 

1.  **Minta Data Dummy ke Claude:** *"Claude, generate a GeoJSON file containing 5 contiguous land parcels in Gading Serpong, with properties: owner_name, status, and price_per_sqm."*
2.  **Bikin Backend:** Suruh Claude buatin *Migration* Laravel pakai kolom spasial dan buatin *Controller* yang nge-query lahan berdasarkan jarak (*ST_Distance* di MySQL).
3.  **Bikin Frontend:** Minta tolong Claude implementasiin `react-leaflet` buat nge-render data dari API lu ke bentuk poligon berwana di atas peta.

Kalau lu cantumin *project* ini di CV, lu udah nge-cover syarat: **PHP, Python, JS, Database Modeling, Geospatial Data, dan Land Acquisition**. Gila nggak tuh?


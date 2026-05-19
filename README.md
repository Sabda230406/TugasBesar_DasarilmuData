# Stroke Risk Prediction Web App

Web aplikasi prediksi risiko stroke berbasis **Laravel (frontend + API gateway)** dan **Flask (ML API)**. Project ini menampilkan alur lengkap: input data pasien → prediksi risiko → simpan riwayat → lihat history terstruktur.

## Ringkasan Fitur

- **Landing page** berisi ringkasan project dan call-to-action.
- **Form prediksi** dengan input pasien yang rapi dan mudah dipahami.
- **History** prediksi lengkap, filter dropdown, dan tampilan input yang terstruktur.
- **ML API (Flask)** untuk load model dan melakukan prediksi.

## Teknologi

- **Laravel** (PHP) untuk UI + data history
- **Flask** (Python) untuk prediksi model
- **Scikit-learn** (model disimpan sebagai `model.pkl`)

## Dataset & Model

- Dataset: **Healthcare Stroke Dataset**
- Model di-load dari file `ml-api/model.pkl`
- Kolom fitur ada di `ml-api/feature_columns.json`

## Struktur Project (Ringkas)

```
.
├── healthcare-dataset-stroke-data.csv
├── run-dev.ps1
├── laravel-app/           # Frontend + history
└── ml-api/                # Flask API + model
```

## Cara Menjalankan (Windows)

Gunakan skrip berikut agar Laravel + Flask otomatis berjalan.

```powershell
./run-dev.ps1
```

Setelah berjalan, buka:

- Laravel app: **http://127.0.0.1:8000**
- Flask API: **http://127.0.0.1:5001**

## Konfigurasi Model (Opsional)

Jika ingin ganti lokasi model:

- `MODEL_PATH` untuk file model
- `FEATURES_PATH` untuk daftar kolom

Contoh env:

```powershell
$env:MODEL_PATH="c:\path\to\model.pkl"
$env:FEATURES_PATH="c:\path\to\feature_columns.json"
```

## Alur Prediksi

1. User isi form prediksi (Laravel)
2. Laravel kirim data ke Flask `/predict`
3. Flask load `model.pkl` dan mengembalikan hasil
4. Hasil disimpan dan ditampilkan di halaman history

## Testing (Laravel)

```powershell
laravel-app\vendor\bin\phpunit.bat -c laravel-app\phpunit.xml
```

## Catatan

- File `ml-api/train_model.py` saat ini kosong (model diambil dari file `model.pkl`).
- Jika ingin training ulang di lokal, tambahkan script training dan export ulang model.

---

Kalau butuh dokumentasi lebih detail (API spec, flow diagram, atau langkah deployment), beri tahu saja.

# Stroke Risk Prediction Web App

README ini diperbarui mengikuti isi repository per 21 Mei 2026.

Aplikasi ini adalah sistem prediksi risiko stroke berbasis web yang menggabungkan:

- `Laravel 12` untuk antarmuka web, autentikasi, dan penyimpanan riwayat prediksi
- `Flask` untuk ML API
- `MySQL` untuk data user dan histori
- model `Decision Tree` untuk klasifikasi risiko stroke

## Fitur Versi Sekarang

- landing page informatif dengan ringkasan model, dataset, dan alur sistem
- login, register, logout, dan akun default dari seeder
- prediksi manual dari form input pasien
- perhitungan `BMI` otomatis dari berat dan tinggi pada form
- prediksi batch lewat upload `CSV`, `XLSX`, atau `XLS`
- validasi per baris saat upload; baris error tidak menghentikan baris valid lain
- penyimpanan riwayat prediksi per user
- filter riwayat prediksi di halaman history
- endpoint ML API `GET /health`, `GET /metadata`, dan `POST /predict`

## Ringkasan Model Aktif

- Model: `Decision Tree`
- Akurasi evaluasi pada metadata model: `66.20%`
- Metode tuning yang tercatat: `GridSearchCV + Pipeline + SMOTENC`
- Output klasifikasi: `Risiko Rendah` atau `Risiko Tinggi`

Fitur input model:

```text
gender
age
hypertension
heart_disease
ever_married
work_type
Residence_type
avg_glucose_level
bmi
smoking_status
```

## Struktur Proyek

```text
.
|-- data/
|   `-- healthcare-dataset-stroke-data.csv
|-- laravel-app/
|   |-- app/
|   |-- database/
|   |-- resources/views/
|   `-- routes/
|-- ml-api/
|   |-- app.py
|   |-- model.pkl
|   |-- feature_columns.json
|   `-- model_metrics.json
|-- run-dev.ps1
`-- README.md
```

## Kebutuhan Sistem

- `PHP 8.2+`
- `Composer`
- `Python 3` + `pip`
- `MySQL`
- `PowerShell` jika ingin memakai `run-dev.ps1` di Windows
- `Node.js` + `npm` opsional untuk pengembangan asset Vite

Catatan: halaman aktif saat ini memakai Bootstrap CDN dan CSS inline, jadi `npm install` bukan syarat utama untuk menjalankan flow aplikasi utama.

## Setup

### 1. Clone Repository

```bash
git clone https://github.com/Sabda230406/TugasBesar_DasarilmuData.git
cd TugasBesar_DasarilmuData
```

### 2. Setup Laravel

```powershell
cd laravel-app
composer install
copy .env.example .env
php artisan key:generate
```

Atur database pada file `.env` Laravel:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=stroke_risk_app
DB_USERNAME=root
DB_PASSWORD=
```

Lalu jalankan migrasi dan seeder:

```powershell
php artisan migrate --seed
```

Seeder akan membuat akun default:

- Email: `user@gmail.com`
- Password: `user123`

### 3. Setup ML API

Jika Anda masih berada di folder `laravel-app`, kembali dulu ke root project lalu jalankan:

```powershell
cd ..
python -m venv .venv
.\.venv\Scripts\Activate.ps1
pip install -r ml-api\requirements.txt
```

Catatan:

- `run-dev.ps1` akan mencari `.venv\Scripts\python.exe` terlebih dahulu
- jika `.venv` tidak ada, script akan mencoba `python` atau `py` dari `PATH`

### 4. Setup Frontend Asset (Opsional)

Bagian ini hanya perlu jika Anda ingin mengembangkan asset Laravel/Vite:

```powershell
cd laravel-app
npm install
npm run build
```

## Menjalankan Aplikasi

### Opsi Cepat di Windows

Dari root project:

```powershell
.\run-dev.ps1
```

Script ini akan:

- menjalankan Flask ML API di `http://127.0.0.1:5001`
- menjalankan Laravel di `http://127.0.0.1:8000`

### Opsi Manual

Terminal 1:

```powershell
cd ml-api
python app.py
```

Terminal 2:

```powershell
cd laravel-app
php artisan serve
```

Alamat yang dipakai:

- Web Laravel: `http://127.0.0.1:8000`
- Health check API: `http://127.0.0.1:5001/health`
- Metadata model: `http://127.0.0.1:5001/metadata`

Catatan penting:

- endpoint root Flask `/` memang tidak disediakan, jadi `404` di root API itu normal
- Laravel saat ini mengirim request prediksi ke `http://127.0.0.1:5001/predict`

## Alur Penggunaan

1. Buka halaman `/`
2. Login atau register
3. Gunakan `/form` untuk prediksi satu pasien
4. Gunakan `/upload` untuk prediksi banyak pasien sekaligus
5. Lihat hasil tersimpan di `/history`

## Format Input Manual

Field yang dipakai model:

- `gender`: `Male`, `Female`, `Other`
- `age`: angka `0` sampai `130`
- `hypertension`: `0` atau `1`
- `heart_disease`: `0` atau `1`
- `ever_married`: `Yes` atau `No`
- `work_type`: `Private`, `Self-employed`, `Govt_job`, `children`, `Never_worked`
- `Residence_type`: `Urban` atau `Rural`
- `avg_glucose_level`: angka
- `bmi`: angka
- `smoking_status`: `formerly smoked`, `never smoked`, `smokes`, `Unknown`

Catatan:

- di form web, `BMI` dihitung otomatis dari input berat badan dan tinggi badan
- nilai yang dikirim ke model tetap berupa field `bmi`

## Format Upload Batch

File yang diterima:

- ekstensi: `csv`, `txt`, `xlsx`, `xls`
- ukuran maksimum: `5 MB`
- jumlah maksimum: `500` baris data per upload

Header minimal yang harus tersedia:

```text
gender,age,hypertension,heart_disease,ever_married,work_type,Residence_type,avg_glucose_level,bmi,smoking_status
```

Contoh isi file:

```csv
gender,age,hypertension,heart_disease,ever_married,work_type,Residence_type,avg_glucose_level,bmi,smoking_status
Female,25,0,0,No,Private,Urban,85,20.2,never smoked
Male,80,1,1,Yes,Private,Urban,250,40,smokes
```

Upload batch saat ini juga mendukung beberapa normalisasi otomatis, misalnya:

- header seperti `sex`, `jenis_kelamin`, `usia`, `pekerjaan`, `gula_darah`, `status_merokok`
- value seperti `ya/tidak`, `pria/wanita`, `kota/desa`, `pernah_merokok`

Jika ada baris tidak valid, baris itu akan ditandai error pada hasil upload tanpa membatalkan prediksi untuk baris valid lainnya.

## Endpoint ML API

### `GET /health`

Contoh response:

```json
{
  "status": "ok"
}
```

### `GET /metadata`

Mengembalikan informasi model aktif, tipe model, akurasi, parameter terbaik, dan metode tuning.

### `POST /predict`

Contoh request:

```json
{
  "input": {
    "gender": "Male",
    "age": 67,
    "hypertension": 1,
    "heart_disease": 0,
    "ever_married": "Yes",
    "work_type": "Private",
    "Residence_type": "Urban",
    "avg_glucose_level": 145.2,
    "bmi": 29.7,
    "smoking_status": "formerly smoked"
  }
}
```

Contoh response sukses:

```json
{
  "status": "success",
  "prediction": 1,
  "high_risk_probability": 0.71,
  "model_name": "Decision Tree",
  "accuracy": 0.6619718309859155
}
```

## Dataset dan Artefak Model

- dataset utama: `data/healthcare-dataset-stroke-data.csv`
- model aktif: `ml-api/model.pkl`
- daftar fitur: `ml-api/feature_columns.json`
- metadata model: `ml-api/model_metrics.json`
- referensi dataset publik: `https://www.kaggle.com/datasets/fedesoriano/stroke-prediction-dataset`

## Catatan

- aplikasi ini adalah alat bantu screening dan edukasi, bukan pengganti diagnosis medis
- setiap prediksi valid akan disimpan ke tabel history milik user yang sedang login
- dokumentasi ini dibuat untuk mencerminkan struktur dan flow aplikasi yang aktif saat ini, bukan versi awal project

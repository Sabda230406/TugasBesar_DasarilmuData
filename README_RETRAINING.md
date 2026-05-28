# README Retraining Model

Dokumen ini menjelaskan fitur **Retraining Model** pada project Laravel + Flask ML API untuk sistem prediksi risiko stroke.

## Tujuan

Fitur retraining dipakai untuk melatih ulang model dengan dataset baru yang sudah memiliki label asli `stroke` bernilai `0` atau `1`.

Penting:

- Upload prediksi biasa hanya untuk menghasilkan prediksi.
- Retraining hanya untuk data berlabel asli.
- Label `stroke` tidak boleh berasal dari hasil prediksi website.
- Sistem hanya mengecek format dan konsistensi data, bukan memastikan kebenaran medis 100%.

## Model Yang Didukung

Saat ini fitur retraining mendukung:

- Decision Tree
- KNN

SVM masih ditampilkan sebagai belum tersedia.

## Cara Akses

Jalankan Laravel:

```bash
php artisan serve
```

Buka menu:

```text
http://127.0.0.1:8000/retraining
```

Saat masuk menu Retraining, user wajib membaca pop-up peringatan terlebih dahulu sebelum form bisa digunakan.

## Opsi Input Dataset

Halaman Retraining punya 2 cara input data:

- Upload File: untuk banyak data sekaligus lewat CSV/XLSX.
- Isi Manual: untuk menambahkan 1 baris data diagnosis secara langsung dari form.

Keduanya tetap wajib menggunakan label asli `stroke`.

## Kolom Dataset Wajib

Dataset retraining wajib punya kolom:

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
stroke
```

Template bisa di-download dari halaman Retraining.

## Aturan Validasi

Sistem akan mengecek:

- Kolom wajib lengkap.
- `stroke` hanya boleh `0` atau `1`.
- `hypertension` hanya boleh `0` atau `1`.
- `heart_disease` hanya boleh `0` atau `1`.
- `age` harus angka dengan range `0-120`.
- `bmi` harus angka dengan range `10-80`.
- `avg_glucose_level` harus angka dengan range `40-400`.
- Kategori harus sesuai pilihan sistem.
- Kolom wajib tidak boleh kosong.
- Upload file harus memiliki dua kelas, yaitu `stroke=0` dan `stroke=1`.

Catatan:

- Input manual boleh menyimpan satu baris saja, jadi tidak wajib langsung punya dua kelas.
- File upload tetap wajib punya dua kelas agar dataset tidak timpang dari awal.

## Nilai Kategori Valid

```text
gender: Male, Female, Other
ever_married: Yes, No
work_type: Private, Self-employed, Govt_job, children, Never_worked
Residence_type: Urban, Rural
smoking_status: formerly smoked, never smoked, smokes, Unknown
```

## Alur Retraining

1. User membuka menu Retraining.
2. User membaca pop-up peringatan.
3. User memilih Upload File atau Isi Manual.
4. User mengisi/unggah data berlabel asli `stroke`.
5. Sistem validasi data.
6. Jika valid, data disimpan sebagai dataset retraining bersih.
7. User memilih model yang ingin dilatih ulang.
8. Laravel mengirim dataset valid ke Flask ML API.
9. Flask menjalankan proses training.
10. Model lama dibackup.
11. Model baru disimpan sebagai model aktif jika proses berhasil.
12. Metrics retraining ditampilkan di halaman web.

## Endpoint Laravel

```text
GET  /retraining
POST /retraining/upload
POST /retraining/manual
POST /retraining/start
```

## Endpoint Flask

```text
POST /retrain
```

Payload utama:

```json
{
  "dataset_path": "path dataset valid",
  "models": ["decision_tree", "knn"],
  "uploaded_by": "nama user"
}
```

## File Penting

```text
laravel-app/app/Http/Controllers/RetrainingController.php
laravel-app/resources/views/retraining.blade.php
laravel-app/public/templates/stroke-retraining-template.csv
ml-api/app.py
ml-api/active_models/
ml-api/backup_models/
```

## Catatan Environment Lokal

Untuk menghindari error lock pada tabel `sessions`, gunakan session dan cache berbasis file pada `.env`:

```env
SESSION_DRIVER=file
CACHE_STORE=file
```

Setelah mengubah `.env`, jalankan:

```bash
php artisan optimize:clear
```

## Troubleshooting

Jika muncul error:

```text
SQLSTATE[HY000]: General error: 1205 Lock wait timeout exceeded
```

Penyebab umumnya:

- Ada request Laravel lama yang masih nyangkut.
- Tabel `sessions` di MySQL terkunci.
- Ada lebih dari satu `php artisan serve` berjalan.

Solusi cepat:

```bash
php artisan optimize:clear
```

Lalu stop proses `php artisan serve` yang lama dan jalankan ulang:

```bash
php artisan serve
```

Jika masih error, restart MySQL dari XAMPP.

## Catatan Penting

Fitur ini membantu proses eksperimen dan pengembangan model. Hasil prediksi tetap bukan pengganti diagnosis dokter atau tenaga medis profesional.

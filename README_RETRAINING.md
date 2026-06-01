# README Retraining Model

Dokumen ini menjelaskan fitur **Retraining Model** pada project Laravel + Flask ML API untuk sistem prediksi risiko stroke.

## Tujuan

Fitur retraining dipakai untuk melatih ulang model dari history prediksi user yang tersimpan di web.

Penting:

- Input user disimpan ke tabel history bersama hasil prediksi model.
- Saat history dipakai untuk retraining, kolom `stroke` diisi dari hasil prediksi sistem.
- Data history bisa diexport sebagai CSV dengan format dataset retraining.
- Admin bisa memasukkan history prediksi valid ke pool retraining dari halaman Admin.

## Model Yang Didukung

Saat ini fitur retraining mendukung:

- Decision Tree
- KNN
- SVM

## Cara Akses

Jalankan Laravel:

```bash
php artisan serve
```

Buka menu Admin:

```text
http://127.0.0.1:8000/admin/retraining
```

Menu retraining hanya bisa diakses akun dengan role `admin`.

## Opsi Input Dataset

Halaman Admin Retraining punya sumber data utama:

- Ambil dari History: input user + hasil prediksi sistem dimasukkan ke pool retraining.
- Download CSV History: export history ke format dataset retraining.

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

CSV history yang didownload sudah memakai kolom ini.

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
- Pool retraining harus memiliki cukup data `stroke=0` dan `stroke=1`.

Catatan:

- Nilai `stroke` dari history diambil dari hasil prediksi sistem.
- Dataset history tetap divalidasi format, range nilai, kategori, dan kelengkapan kolom.

## Nilai Kategori Valid

```text
gender: Male, Female, Other
ever_married: Yes, No
work_type: Private, Self-employed, Govt_job, children, Never_worked
Residence_type: Urban, Rural
smoking_status: formerly smoked, never smoked, smokes, Unknown
```

## Alur Retraining

1. User melakukan prediksi lewat form atau upload file prediksi.
2. Sistem menyimpan input user dan hasil prediksi ke history.
3. Admin membuka menu Admin Retraining.
4. Admin menekan tombol Ambil History ke Pool.
5. Sistem membentuk dataset retraining dari history prediksi user.
6. Kolom `stroke` diisi dari hasil prediksi sistem.
7. Sistem validasi format, kategori, range nilai, dan kelengkapan data.
8. Jika valid, data masuk pool retraining.
9. Jika syarat pool terpenuhi, admin menjalankan retraining semua model.
10. Laravel mengirim dataset valid ke Flask ML API.
11. Flask menjalankan proses training.
12. Model lama dibackup.
13. Model baru disimpan sebagai model aktif jika proses berhasil dan metrik layak.

## Endpoint Laravel

```text
GET  /retraining
POST /retraining/upload
POST /retraining/manual
POST /retraining/start
GET  /admin/retraining
POST /admin/retraining/history/import
GET  /admin/history/export
```

## Endpoint Flask

```text
POST /retrain
```

Payload utama:

```json
{
  "dataset_path": "path dataset valid",
  "models": ["decision_tree", "knn", "svm"],
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

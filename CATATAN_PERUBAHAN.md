# Catatan Perubahan UI Multi Model

Tanggal update: 26 Mei 2026

## Yang Diubah

- Landing page sudah disesuaikan dengan rencana multi-model.
- Form prediksi manual sekarang menampilkan pilihan model:
  - Decision Tree - aktif
  - KNN - belum aktif
  - SVM - belum aktif
- Halaman upload file juga menampilkan pilihan model dengan status yang sama.
- Halaman upload file sekarang punya tombol download template CSV kosong.
- Template CSV tersedia di `laravel-app/public/templates/stroke-input-template.csv`.
- Halaman history mendapat kolom baru `Model`.

## Catatan Penting

- Perubahan ini baru bagian tampilan/UI.
- Prediksi yang benar-benar berjalan saat ini masih memakai Decision Tree.
- Opsi KNN dan SVM dibuat disabled karena file model dan integrasi API-nya belum tersedia.
- Kolom `Model` di history untuk sementara menampilkan `Decision Tree` sebagai default.

## Yang Perlu Dikerjakan Berikutnya

- Train dan export model KNN serta SVM.
- Tambahkan artefak model baru di folder `ml-api`.
- Update Flask API agar bisa memilih model berdasarkan request dari Laravel.
- Tambahkan validasi request model di Laravel.
- Tambahkan kolom database, misalnya `model_name`, agar history bisa menyimpan model yang dipilih user.
- Setelah backend siap, aktifkan opsi KNN dan SVM di form serta upload.
- Test ulang flow manual prediction, upload prediction, dan history.

# Dokumentasi Fitur Retraining

Dokumen ini menjelaskan cara kerja dan langkah-langkah penggunaan fitur retraining model pada ML API.

## Ringkasan Fungsi

Fitur retraining digunakan untuk melatih ulang model menggunakan dataset tambahan. Proses ini akan:

- memvalidasi dataset baru (wajib memiliki kolom fitur + target `stroke`),
- menggabungkan dataset baru dengan dataset utama,
- melakukan training ulang untuk model yang dipilih,
- menyimpan artefak model terbaru, feature columns, dan metrik evaluasi,
- membuat backup artefak lama sebelum ditimpa.

Endpoint retraining berada di `POST /retrain` pada `ml-api/app.py`.

## Format Dataset Retraining

Dataset wajib berupa CSV yang memiliki kolom berikut:

```
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

Catatan penting:

- Kolom `stroke` hanya boleh bernilai `0` atau `1`.
- Dataset harus memiliki kedua kelas (`stroke=0` dan `stroke=1`).
- Nilai kategori harus sesuai mapping di `CATEGORY_MAPS` (lihat `ml-api/app.py`).
- Dataset akan digabung dengan dataset utama `data/healthcare-dataset-stroke-data.csv`.

## Model yang Bisa Diretrain (MVP)

Saat ini retraining mendukung:

- `decision_tree`
- `knn`

Jika model lain belum tersedia, API akan mengembalikan error.

## Alur Kerja Retraining

1. **Upload dataset CSV** ke server dan simpan path-nya.
2. Panggil `POST /retrain` dengan `dataset_path` dan daftar `models`.
3. API akan:
   - melakukan validasi dataset,
   - menggabungkan dataset baru dengan dataset utama,
   - melakukan training ulang per model,
   - menyimpan artefak baru,
   - membuat backup artefak lama.

## Payload Request

Contoh body JSON:

```json
{
  "dataset_path": "C:/path/ke/dataset_baru.csv",
  "models": ["decision_tree", "knn"],
  "uploaded_by": "admin"
}
```

Keterangan:

- `dataset_path` (wajib): path file dataset CSV yang sudah ada di server.
- `models` (wajib): daftar model yang akan diretrain.
- `uploaded_by` (opsional): nama user yang mengunggah dataset.

## Response Sukses (Ringkas)

```json
{
  "status": "success",
  "message": "Retraining selesai.",
  "backup_dir": ".../ml-api/backup_models/20260527-213045",
  "models": {
    "decision_tree": {
      "model_name": "Decision Tree",
      "metrics": {
        "accuracy": 0.662,
        "best_params": {...},
        "classification_report": {...},
        "confusion_matrix": [...]
      }
    }
  }
}
```

## Lokasi Artefak yang Dihasilkan

Setelah retraining, artefak disimpan ke:

```
ml-api/active_models/
```

Dengan nama file:

- `decision_tree_model.pkl`
- `decision_tree_feature_columns.json`
- `decision_tree_metrics.json`
- `knn_model.pkl`
- `knn_feature_columns.json`
- `knn_metrics.json`

Kemudian artefak juga disalin ke root `ml-api` sebagai cadangan “aktif”:

- `DT_model.pkl`, `DT_feature_columns.json`, `DT_model_metrics.json`
- `knn_model.pkl`, `knn_feature_columns.json`, `knn_model_metrics.json`

## Backup Otomatis

Sebelum artefak ditimpa, file lama disalin ke:

```
ml-api/backup_models/<timestamp>/
```

## Catatan Integrasi

- Endpoint `GET /metadata` akan membaca metrik terbaru dari file artefak.
- Endpoint `POST /predict` otomatis memakai model terbaru jika file artefak berubah.
- Tidak ada perubahan ke Laravel atau endpoint prediksi; retraining hanya mengubah artefak model.

## Troubleshooting Umum

- **Error: Dataset retraining belum lengkap** → pastikan semua kolom fitur + `stroke` ada.
- **Error: Kolom stroke hanya boleh bernilai 0 atau 1** → cek label pada dataset.
- **Error: Model belum tersedia untuk retraining** → gunakan `decision_tree` atau `knn`.
- **Error: Dataset retraining harus memiliki stroke=0 dan stroke=1** → dataset harus punya dua kelas.

## Lokasi Implementasi

Logika retraining berada di:

- `ml-api/app.py` → endpoint `/retrain` dan seluruh pipeline training
- `ml-api/active_models/` → artefak model aktif
- `ml-api/backup_models/` → backup otomatis

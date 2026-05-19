<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Prediksi</title>
</head>
<body>
    <h2>Hasil Prediksi</h2>
    <p>Prediction: {{ $prediction }}</p>
    @if($accuracy)
        <p>Akurasi: {{ $accuracy }}</p>
    @endif

    <a href="/">Kembali</a>
</body>
</html>

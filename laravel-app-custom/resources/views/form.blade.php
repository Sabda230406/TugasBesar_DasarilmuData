<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stroke Prediction</title>
</head>
<body>
    <h1>Stroke Prediction</h1>
    <form action="/predict" method="POST">
        @csrf

        <input type="number" name="age" placeholder="Age" required>
        <input type="number" name="hypertension" placeholder="Hypertension" required>
        <input type="number" name="heart_disease" placeholder="Heart Disease" required>
        <input type="number" step="0.01" name="avg_glucose_level" placeholder="Glucose" required>

        <button type="submit">Prediksi</button>
    </form>
</body>
</html>

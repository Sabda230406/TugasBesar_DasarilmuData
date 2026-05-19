<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>History</title>
</head>
<body>
    <h2>History</h2>

    <table border="1">
        <tr>
            <th>Input</th>
            <th>Prediction</th>
        </tr>
        @foreach($data as $item)
            <tr>
                <td>{{ $item->input_data }}</td>
                <td>{{ $item->prediction }}</td>
            </tr>
        @endforeach
    </table>
</body>
</html>

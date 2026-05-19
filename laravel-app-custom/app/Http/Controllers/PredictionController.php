<?php

namespace App\Http\Controllers;

use App\Models\History;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PredictionController extends Controller
{
    public function index()
    {
        return view('form');
    }

    public function predict(Request $request)
    {
        $input = [
            $request->age,
            $request->hypertension,
            $request->heart_disease,
            $request->avg_glucose_level,
        ];

        $response = Http::post('http://127.0.0.1:5000/predict', [
            'input' => $input,
        ]);

        $result = $response->json();

        History::create([
            'input_data' => json_encode($input),
            'prediction' => $result['prediction'] ?? 'N/A',
        ]);

        return view('result', [
            'prediction' => $result['prediction'] ?? 'N/A',
            'accuracy' => $result['accuracy'] ?? null,
        ]);
    }

    public function history()
    {
        $data = History::latest()->get();

        return view('history', compact('data'));
    }
}

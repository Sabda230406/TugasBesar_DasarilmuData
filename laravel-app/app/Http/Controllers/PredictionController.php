<?php

namespace App\Http\Controllers;

use App\Models\History;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PredictionController extends Controller
{
	public function landing()
	{
		return view('landing');
	}

	public function index()
	{
		return view('form');
	}

	public function predict(Request $request)
	{
		$validated = $request->validate([
			'gender' => 'required|in:Male,Female,Other',
			'age' => 'required|integer|min:0|max:130',
			'hypertension' => 'required|integer|min:0|max:1',
			'heart_disease' => 'required|integer|min:0|max:1',
			'ever_married' => 'required|in:Yes,No',
			'work_type' => 'required|in:Private,Self-employed,Govt_job,children,Never_worked',
			'Residence_type' => 'required|in:Urban,Rural',
			'avg_glucose_level' => 'required|numeric|min:0|max:500',
			'bmi' => 'required|numeric|min:0|max:100',
			'smoking_status' => 'required|in:formerly smoked,never smoked,smokes,Unknown',
		]);

		$input = [
			'gender' => $validated['gender'],
			'age' => $validated['age'],
			'hypertension' => $validated['hypertension'],
			'heart_disease' => $validated['heart_disease'],
			'ever_married' => $validated['ever_married'],
			'work_type' => $validated['work_type'],
			'Residence_type' => $validated['Residence_type'],
			'avg_glucose_level' => $validated['avg_glucose_level'],
			'bmi' => $validated['bmi'],
			'smoking_status' => $validated['smoking_status'],
		];

		$response = Http::timeout(5)->post('http://127.0.0.1:5001/predict', [
			'input' => $input,
		]);

		if (! $response->ok()) {
			return back()
				->withErrors(['api' => 'ML API error: ' . $response->body()])
				->withInput();
		}

		$result = $response->json() ?? [];
		if (! array_key_exists('prediction', $result)) {
			return back()
				->withErrors(['api' => 'Prediction not found in API response.'])
				->withInput();
		}

		History::create([
			'user_id' => $request->user()->id,
			'input_data' => json_encode($input),
			'prediction' => $result['prediction'],
		]);

		$isHighRisk = (int) $result['prediction'] === 1;
		$riskLabel = $isHighRisk ? 'Risiko Tinggi' : 'Risiko Rendah';
		$riskTone = $isHighRisk ? 'high' : 'low';
		$riskMessage = $isHighRisk
			? 'Perlu perhatian lebih lanjut. Disarankan konsultasi dengan tenaga medis.'
			: 'Risiko tergolong rendah. Tetap jaga pola hidup sehat.';

		return view('result', [
			'prediction' => $result['prediction'],
			'riskLabel' => $riskLabel,
			'riskTone' => $riskTone,
			'riskMessage' => $riskMessage,
			'accuracy' => $result['accuracy'] ?? null,
		]);
	}

	public function history()
	{
		$data = History::where('user_id', auth()->id())->latest()->get();

		return view('history', compact('data'));
	}
}

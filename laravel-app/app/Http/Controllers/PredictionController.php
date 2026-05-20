<?php

namespace App\Http\Controllers;

use App\Models\History;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PredictionController extends Controller
{
	public function landing()
	{
		return view('landing', [
			'modelMetrics' => $this->modelMetrics(),
		]);
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
		$riskTips = $isHighRisk
			? [
				'Jadwalkan konsultasi dengan tenaga medis untuk evaluasi lanjutan.',
				'Pantau tekanan darah, kadar gula, dan kolesterol secara rutin.',
				'Perbaiki pola makan: kurangi garam, gula, dan lemak jenuh.',
				'Perbanyak aktivitas fisik ringan sesuai saran dokter.',
			]
			: [
				'Kesehatanmu sudah terjaga dengan baik, pertahankan pola hidup sehat.',
				'Tetap rutin olahraga ringan dan tidur cukup.',
				'Pertahankan pola makan seimbang dan hidrasi yang cukup.',
				'Lakukan pemeriksaan berkala untuk memastikan kondisi tetap stabil.',
			];

		$modelMetrics = $this->modelMetrics();
		$accuracy = $result['accuracy'] ?? $modelMetrics['accuracy'] ?? null;

		return view('result', [
			'prediction' => $result['prediction'],
			'riskLabel' => $riskLabel,
			'riskTone' => $riskTone,
			'riskMessage' => $riskMessage,
			'riskTips' => $riskTips,
			'accuracy' => $accuracy,
			'accuracyDisplay' => $this->formatAccuracy($accuracy),
			'modelName' => $result['model_name'] ?? $modelMetrics['model_name'] ?? 'Decision Tree',
		]);
	}

	public function history()
	{
		$data = History::where('user_id', auth()->id())->latest()->paginate(10);

		return view('history', compact('data'));
	}

	private function modelMetrics(): array
	{
		$defaults = [
			'model_name' => 'Decision Tree',
			'accuracy' => null,
			'accuracy_display' => null,
		];
		$path = dirname(base_path()) . DIRECTORY_SEPARATOR . 'ml-api' . DIRECTORY_SEPARATOR . 'model_metrics.json';

		if (! is_file($path)) {
			return $defaults;
		}

		$metrics = json_decode((string) file_get_contents($path), true);
		if (! is_array($metrics)) {
			return $defaults;
		}

		$accuracy = $metrics['accuracy'] ?? null;
		$metrics['model_name'] = $metrics['model_name'] ?? $defaults['model_name'];
		$metrics['accuracy_display'] = $this->formatAccuracy($accuracy);

		return array_merge($defaults, $metrics);
	}

	private function formatAccuracy($accuracy): ?string
	{
		if (! is_numeric($accuracy)) {
			return null;
		}

		$value = (float) $accuracy;
		if ($value <= 1) {
			$value *= 100;
		}

		return number_format($value, 2) . '%';
	}
}

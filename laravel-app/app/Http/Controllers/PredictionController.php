<?php

namespace App\Http\Controllers;

use App\Models\History;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

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

	public function upload()
	{
		return view('upload', [
			'modelMetrics' => $this->modelMetrics(),
			'requiredColumns' => $this->featureColumns(),
		]);
	}

	public function predict(Request $request)
	{
		$validated = $request->validate($this->predictionRules());

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

		try {
			$result = $this->predictInput($input);
		} catch (Throwable $exception) {
			return back()
				->withErrors(['api' => $exception->getMessage()])
				->withInput();
		}

		History::create([
			'user_id' => $request->user()->id,
			'input_data' => json_encode($input),
			'prediction' => $result['prediction'],
		]);

		$risk = $this->riskDetails((int) $result['prediction']);

		$modelMetrics = $this->modelMetrics();
		$accuracy = $modelMetrics['accuracy'] ?? $result['accuracy'] ?? null;
		$modelName = $modelMetrics['model_name'] ?? $result['model_name'] ?? 'Decision Tree';

		return view('result', [
			'prediction' => $result['prediction'],
			'riskLabel' => $risk['label'],
			'riskTone' => $risk['tone'],
			'riskMessage' => $risk['message'],
			'riskTips' => $risk['tips'],
			'accuracy' => $accuracy,
			'accuracyDisplay' => $this->formatAccuracy($accuracy),
			'modelName' => $modelName,
		]);
	}

	public function predictUpload(Request $request)
	{
		$validated = $request->validate([
			'prediction_file' => 'required|file|mimes:csv,txt,xlsx,xls|max:5120',
		]);

		try {
			$rows = $this->rowsFromSpreadsheet($validated['prediction_file']);
		} catch (Throwable $exception) {
			return back()
				->withErrors(['prediction_file' => $exception->getMessage()])
				->withInput();
		}

		if ($rows === []) {
			return back()
				->withErrors(['prediction_file' => 'File tidak memiliki data yang bisa diproses.'])
				->withInput();
		}

		if (count($rows) > 500) {
			return back()
				->withErrors(['prediction_file' => 'Maksimal 500 baris per upload agar proses tetap stabil.'])
				->withInput();
		}

		$results = [];
		$validCount = 0;
		$highCount = 0;
		$lowCount = 0;

		foreach ($rows as $index => $row) {
			$rowNumber = $index + 2;
			$input = $this->normalizeBatchRow($row);
			$validator = Validator::make($input, $this->predictionRules());

			if ($validator->fails()) {
				$results[] = [
					'row' => $rowNumber,
					'input' => $input,
					'status' => 'error',
					'message' => implode(' ', $validator->errors()->all()),
				];
				continue;
			}

			try {
				$predictionResult = $this->predictInput($validator->validated());
				$prediction = (int) $predictionResult['prediction'];
				$risk = $this->riskDetails($prediction);
				$probability = $predictionResult['high_risk_probability'] ?? null;

				History::create([
					'user_id' => $request->user()->id,
					'input_data' => json_encode($validator->validated()),
					'prediction' => $prediction,
				]);

				$validCount++;
				$prediction === 1 ? $highCount++ : $lowCount++;

				$results[] = [
					'row' => $rowNumber,
					'input' => $validator->validated(),
					'status' => 'success',
					'prediction' => $prediction,
					'riskLabel' => $risk['label'],
					'riskTone' => $risk['tone'],
					'probability' => $probability,
					'probabilityDisplay' => $this->formatAccuracy($probability),
				];
			} catch (Throwable $exception) {
				$results[] = [
					'row' => $rowNumber,
					'input' => $validator->validated(),
					'status' => 'error',
					'message' => $exception->getMessage(),
				];
			}
		}

		$modelMetrics = $this->modelMetrics();

		return view('upload-result', [
			'fileName' => $validated['prediction_file']->getClientOriginalName(),
			'results' => $results,
			'summary' => [
				'total' => count($rows),
				'success' => $validCount,
				'errors' => count($rows) - $validCount,
				'high' => $highCount,
				'low' => $lowCount,
			],
			'modelName' => $modelMetrics['model_name'] ?? 'Decision Tree',
			'accuracyDisplay' => $modelMetrics['accuracy_display'] ?? null,
		]);
	}

	public function history()
	{
		$data = History::where('user_id', auth()->id())->latest()->paginate(10);

		return view('history', compact('data'));
	}

	private function predictionRules(): array
	{
		return [
			'gender' => 'required|in:Male,Female,Other',
			'age' => 'required|numeric|min:0|max:130',
			'hypertension' => 'required|integer|min:0|max:1',
			'heart_disease' => 'required|integer|min:0|max:1',
			'ever_married' => 'required|in:Yes,No',
			'work_type' => 'required|in:Private,Self-employed,Govt_job,children,Never_worked',
			'Residence_type' => 'required|in:Urban,Rural',
			'avg_glucose_level' => 'required|numeric|min:0|max:500',
			'bmi' => 'required|numeric|min:0|max:100',
			'smoking_status' => 'required|in:formerly smoked,never smoked,smokes,Unknown',
		];
	}

	private function featureColumns(): array
	{
		return [
			'gender',
			'age',
			'hypertension',
			'heart_disease',
			'ever_married',
			'work_type',
			'Residence_type',
			'avg_glucose_level',
			'bmi',
			'smoking_status',
		];
	}

	private function predictInput(array $input): array
	{
		$response = Http::timeout(20)->post('http://127.0.0.1:5001/predict', [
			'input' => $input,
		]);

		if (! $response->ok()) {
			throw new \RuntimeException('ML API error: ' . $response->body());
		}

		$result = $response->json() ?? [];
		if (! array_key_exists('prediction', $result)) {
			throw new \RuntimeException('Prediction not found in API response.');
		}

		return $result;
	}

	private function riskDetails(int $prediction): array
	{
		$isHighRisk = $prediction === 1;

		return [
			'label' => $isHighRisk ? 'Risiko Tinggi' : 'Risiko Rendah',
			'tone' => $isHighRisk ? 'high' : 'low',
			'message' => $isHighRisk
				? 'Perlu perhatian lebih lanjut. Disarankan konsultasi dengan tenaga medis.'
				: 'Risiko tergolong rendah. Tetap jaga pola hidup sehat.',
			'tips' => $isHighRisk
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
				],
		];
	}

	private function rowsFromSpreadsheet($file): array
	{
		$spreadsheet = IOFactory::load($file->getRealPath());
		$sheet = $spreadsheet->getActiveSheet();
		$rawRows = $sheet->toArray(null, true, true, true);

		if (count($rawRows) < 2) {
			return [];
		}

		$headerRow = array_shift($rawRows);
		$headers = [];
		foreach ($headerRow as $column => $value) {
			$headers[$column] = $this->normalizeHeader((string) $value);
		}

		$missing = array_diff($this->featureColumns(), array_values($headers));
		if ($missing !== []) {
			throw new \RuntimeException('Kolom wajib belum ada: ' . implode(', ', $missing));
		}

		$rows = [];
		foreach ($rawRows as $rawRow) {
			$isEmpty = true;
			$row = [];

			foreach ($headers as $column => $header) {
				if ($header === '') {
					continue;
				}

				$value = $rawRow[$column] ?? null;
				if ($value !== null && trim((string) $value) !== '') {
					$isEmpty = false;
				}
				$row[$header] = $value;
			}

			if (! $isEmpty) {
				$rows[] = $row;
			}
		}

		return $rows;
	}

	private function normalizeHeader(string $header): string
	{
		$key = strtolower(trim($header));
		$key = str_replace([' ', '-', '.'], '_', $key);

		return [
			'sex' => 'gender',
			'jenis_kelamin' => 'gender',
			'umur' => 'age',
			'usia' => 'age',
			'high_blood_pressure' => 'hypertension',
			'tekanan_darah_tinggi' => 'hypertension',
			'penyakit_jantung' => 'heart_disease',
			'ever_married' => 'ever_married',
			'pernah_menikah' => 'ever_married',
			'work' => 'work_type',
			'pekerjaan' => 'work_type',
			'residence_type' => 'Residence_type',
			'residence' => 'Residence_type',
			'tempat_tinggal' => 'Residence_type',
			'glucose' => 'avg_glucose_level',
			'gula_darah' => 'avg_glucose_level',
			'avg_glucose' => 'avg_glucose_level',
			'status_merokok' => 'smoking_status',
		][$key] ?? $header;
	}

	private function normalizeBatchRow(array $row): array
	{
		return [
			'gender' => $this->normalizeChoice($row['gender'] ?? null, [
				'male' => 'Male',
				'laki_laki' => 'Male',
				'pria' => 'Male',
				'female' => 'Female',
				'perempuan' => 'Female',
				'wanita' => 'Female',
				'other' => 'Other',
				'lainnya' => 'Other',
			]),
			'age' => $this->normalizeNumber($row['age'] ?? null),
			'hypertension' => $this->normalizeBinary($row['hypertension'] ?? null),
			'heart_disease' => $this->normalizeBinary($row['heart_disease'] ?? null),
			'ever_married' => $this->normalizeChoice($row['ever_married'] ?? null, [
				'yes' => 'Yes',
				'ya' => 'Yes',
				'y' => 'Yes',
				'1' => 'Yes',
				'no' => 'No',
				'tidak' => 'No',
				'n' => 'No',
				'0' => 'No',
			]),
			'work_type' => $this->normalizeChoice($row['work_type'] ?? null, [
				'private' => 'Private',
				'self_employed' => 'Self-employed',
				'self-employed' => 'Self-employed',
				'wiraswasta' => 'Self-employed',
				'govt_job' => 'Govt_job',
				'govt' => 'Govt_job',
				'government' => 'Govt_job',
				'pns' => 'Govt_job',
				'children' => 'children',
				'anak' => 'children',
				'never_worked' => 'Never_worked',
				'never_worked_' => 'Never_worked',
				'belum_pernah_bekerja' => 'Never_worked',
			]),
			'Residence_type' => $this->normalizeChoice($row['Residence_type'] ?? null, [
				'urban' => 'Urban',
				'kota' => 'Urban',
				'perkotaan' => 'Urban',
				'rural' => 'Rural',
				'desa' => 'Rural',
				'pedesaan' => 'Rural',
			]),
			'avg_glucose_level' => $this->normalizeNumber($row['avg_glucose_level'] ?? null),
			'bmi' => $this->normalizeNumber($row['bmi'] ?? null),
			'smoking_status' => $this->normalizeChoice($row['smoking_status'] ?? null, [
				'formerly_smoked' => 'formerly smoked',
				'formerly smoked' => 'formerly smoked',
				'pernah_merokok' => 'formerly smoked',
				'never_smoked' => 'never smoked',
				'never smoked' => 'never smoked',
				'tidak_pernah_merokok' => 'never smoked',
				'smokes' => 'smokes',
				'merokok' => 'smokes',
				'unknown' => 'Unknown',
				'tidak_diketahui' => 'Unknown',
			]),
		];
	}

	private function normalizeChoice($value, array $map): ?string
	{
		if ($value === null || trim((string) $value) === '') {
			return null;
		}

		$raw = trim((string) $value);
		$key = strtolower(str_replace([' ', '-'], '_', $raw));

		return $map[$key] ?? $raw;
	}

	private function normalizeBinary($value): ?int
	{
		if ($value === null || trim((string) $value) === '') {
			return null;
		}

		$key = strtolower(trim((string) $value));

		return [
			'1' => 1,
			'yes' => 1,
			'ya' => 1,
			'y' => 1,
			'true' => 1,
			'0' => 0,
			'no' => 0,
			'tidak' => 0,
			'n' => 0,
			'false' => 0,
		][$key] ?? (is_numeric($value) ? (int) $value : null);
	}

	private function normalizeNumber($value): ?float
	{
		if ($value === null || trim((string) $value) === '') {
			return null;
		}

		$normalized = str_replace(',', '.', trim((string) $value));

		return is_numeric($normalized) ? (float) $normalized : null;
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

<?php

namespace App\Http\Controllers;

use App\Models\History;
use App\Models\ModelVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class PredictionController extends Controller
{
	public function landing()
	{
		$selectedModelKey = $this->defaultModelKey();

		return view('landing', [
			'modelMetrics' => $this->modelMetrics($selectedModelKey),
			'models' => $this->availableModels(),
			'selectedModelKey' => $selectedModelKey,
		]);
	}

	public function index()
	{
		return view('form', [
			'modelMetrics' => $this->modelMetrics(),
			'models' => $this->availableModels(),
			'selectedModelKey' => $this->defaultModelKey(),
		]);
	}

	public function retryWithInput(Request $request)
	{
		$request->validate(array_merge($this->predictionRules(), $this->formOnlyRules(), [
			'model' => 'nullable|string',
		]));

		return redirect()
			->route('form')
			->withInput($request->only($this->formPrefillKeys()));
	}

	public function upload()
	{
		$selectedModelKey = $this->defaultModelKey();

		return view('upload', [
			'modelMetrics' => $this->modelMetrics($selectedModelKey),
			'models' => $this->availableModels(),
			'selectedModelKey' => $selectedModelKey,
			'requiredColumns' => $this->featureColumns($selectedModelKey),
		]);
	}

	public function predict(Request $request)
	{
		$validated = $request->validate($this->predictionRules());

		try {
			$modelKey = $this->resolveRequestedModelKey($request->input('model'));
		} catch (Throwable $exception) {
			return back()
				->withErrors(['model' => $exception->getMessage()])
				->withInput();
		}

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
			$result = $this->predictInput($input, $modelKey);
		} catch (Throwable $exception) {
			return back()
				->withErrors(['api' => $exception->getMessage()])
				->withInput();
		}

		$risk = $this->riskDetails((int) $result['prediction']);

		$modelMetrics = $this->modelMetrics($modelKey);
		$accuracy = $modelMetrics['accuracy'] ?? $result['accuracy'] ?? null;
		$modelName = $result['model_name'] ?? $modelMetrics['model_name'] ?? $this->modelDisplayName($modelKey);
		$probability = $result['high_risk_probability'] ?? null;

		$this->storeHistory($request, $input, (int) $result['prediction'], $modelName);

		return view('result', [
			'prediction' => $result['prediction'],
			'riskLabel' => $risk['label'],
			'riskTone' => $risk['tone'],
			'riskMessage' => $risk['message'],
			'riskTips' => $risk['tips'],
			'accuracy' => $accuracy,
			'accuracyDisplay' => $this->formatAccuracy($accuracy),
			'probabilityDisplay' => $this->formatAccuracy($probability),
			'modelName' => $modelName,
			'formInput' => $this->formPrefillInput($request, $input, $modelKey),
		]);
	}

	public function predictUpload(Request $request)
	{
		$validated = $request->validate([
			'prediction_file' => 'required|file|mimes:csv,txt,xlsx,xls|max:5120',
			'model' => 'nullable|string',
		]);

		try {
			$modelKey = $this->resolveRequestedModelKey($validated['model'] ?? null);
		} catch (Throwable $exception) {
			return back()
				->withErrors(['model' => $exception->getMessage()])
				->withInput();
		}

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
		$modelMetrics = $this->modelMetrics($modelKey);
		$modelName = $modelMetrics['model_name'] ?? $this->modelDisplayName($modelKey);

		foreach ($rows as $index => $row) {
			$rowNumber = $index + 2;
			$input = $this->normalizeBatchRow($row);
			$validator = Validator::make($input, $this->predictionRules());

			if ($validator->fails()) {
				$results[] = [
					'row' => $rowNumber,
					'input' => $input,
					'status' => 'error',
					'modelName' => $modelName,
					'message' => implode(' ', $validator->errors()->all()),
				];
				continue;
			}

			try {
				$predictionResult = $this->predictInput($validator->validated(), $modelKey);
				$prediction = (int) $predictionResult['prediction'];
				$risk = $this->riskDetails($prediction);
				$probability = $predictionResult['high_risk_probability'] ?? null;
				$rowModelName = $predictionResult['model_name'] ?? $modelName;

				$this->storeHistory($request, $validator->validated(), $prediction, $rowModelName);

				$validCount++;
				$prediction === 1 ? $highCount++ : $lowCount++;

				$results[] = [
					'row' => $rowNumber,
					'input' => $validator->validated(),
					'status' => 'success',
					'modelName' => $rowModelName,
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
					'modelName' => $modelName,
					'message' => $exception->getMessage(),
				];
			}
		}

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
			'modelName' => $modelName,
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

	private function formOnlyRules(): array
	{
		return [
			'weight' => 'nullable|numeric|min:0|max:500',
			'height' => 'nullable|numeric|min:0|max:300',
		];
	}

	private function formPrefillKeys(): array
	{
		return array_merge(array_keys($this->predictionRules()), ['model', 'weight', 'height']);
	}

	private function formPrefillInput(Request $request, array $input, string $modelKey): array
	{
		return array_merge($input, [
			'model' => $modelKey,
			'weight' => $request->input('weight'),
			'height' => $request->input('height'),
		]);
	}

	private function featureColumns(?string $modelKey = null): array
	{
		$modelKey = $modelKey ? $this->normalizeModelKey($modelKey) : $this->defaultModelKey();
		$artifacts = $this->resolveModelArtifacts($modelKey);
		$path = $artifacts['feature_path'] ?? null;

		if ($path && is_file($path)) {
			$payload = json_decode((string) file_get_contents($path), true);
			$columns = is_array($payload) && array_key_exists('feature_columns', $payload)
				? $payload['feature_columns']
				: $payload;

			if (is_array($columns)) {
				return $columns;
			}
		}

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

	private function predictInput(array $input, ?string $modelKey = null): array
	{
		$response = Http::timeout(20)->post('http://127.0.0.1:5001/predict', [
			'input' => $input,
			'model' => $modelKey ?? $this->defaultModelKey(),
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

	private function mlApiDirectory(): string
	{
		return dirname(base_path()) . DIRECTORY_SEPARATOR . 'ml-api' . DIRECTORY_SEPARATOR;
	}

	private function modelDefinitions(): array
	{
		return [
			'decision_tree' => [
				'label' => 'Decision Tree',
				'icon' => 'fa-tree',
				'aliases' => ['decision_tree', 'decision-tree', 'dt', 'tree', 'Decision Tree'],
				'artifacts' => [
					['model' => 'active_models/decision_tree_model.pkl', 'features' => 'active_models/decision_tree_feature_columns.json', 'metrics' => 'active_models/decision_tree_metrics.json'],
					['model' => 'DT_model.pkl', 'features' => 'DT_feature_columns.json', 'metrics' => 'DT_model_metrics.json'],
					['model' => 'dt_model.pkl', 'features' => 'dt_feature_columns.json', 'metrics' => 'dt_model_metrics.json'],
					['model' => 'model.pkl', 'features' => 'feature_columns.json', 'metrics' => 'model_metrics.json'],
				],
			],
			'knn' => [
				'label' => 'KNN',
				'icon' => 'fa-diagram-project',
				'aliases' => ['knn', 'KNN'],
				'artifacts' => [
					['model' => 'active_models/knn_model.pkl', 'features' => 'active_models/knn_feature_columns.json', 'metrics' => 'active_models/knn_metrics.json'],
					['model' => 'knn_model.pkl', 'features' => 'knn_feature_columns.json', 'metrics' => 'knn_model_metrics.json'],
					['model' => 'KNN_model.pkl', 'features' => 'KNN_feature_columns.json', 'metrics' => 'KNN_model_metrics.json'],
				],
			],
			'svm' => [
				'label' => 'SVM',
				'icon' => 'fa-vector-square',
				'aliases' => ['svm', 'SVM'],
				'artifacts' => [
					['model' => 'active_models/svm_model.pkl', 'features' => 'active_models/svm_feature_columns.json', 'metrics' => 'active_models/svm_metrics.json'],
					['model' => 'svm_model.pkl', 'features' => 'svm_feature_columns.json', 'metrics' => 'svm_model_metrics.json'],
					['model' => 'SVM_model.pkl', 'features' => 'SVM_feature_columns.json', 'metrics' => 'SVM_model_metrics.json'],
				],
			],
		];
	}

	private function canonicalModelKey(?string $modelKey): ?string
	{
		if ($modelKey === null || trim($modelKey) === '') {
			return null;
		}

		$needle = strtolower(str_replace([' ', '-'], '_', trim($modelKey)));
		foreach ($this->modelDefinitions() as $key => $definition) {
			$aliases = array_map(
				fn ($alias) => strtolower(str_replace([' ', '-'], '_', $alias)),
				$definition['aliases']
			);

			if ($needle === $key || in_array($needle, $aliases, true)) {
				return $key;
			}
		}

		throw new \RuntimeException('Model yang dipilih tidak dikenal.');
	}

	private function normalizeModelKey(?string $modelKey): string
	{
		return $this->canonicalModelKey($modelKey) ?? $this->defaultModelKey();
	}

	private function defaultModelKey(): string
	{
		if (Schema::hasTable('model_versions')) {
			$activeVersion = ModelVersion::where('is_default', true)
				->where('is_active', true)
				->first();
			if ($activeVersion && $this->modelIsAvailable($activeVersion->model_key)) {
				return $activeVersion->model_key;
			}
		}

		try {
			$preferred = $this->canonicalModelKey(env('ML_ACTIVE_MODEL', 'decision_tree')) ?? 'decision_tree';
		} catch (Throwable) {
			$preferred = 'decision_tree';
		}

		if ($this->modelIsAvailable($preferred)) {
			return $preferred;
		}

		foreach (array_keys($this->modelDefinitions()) as $key) {
			if ($this->modelIsAvailable($key)) {
				return $key;
			}
		}

		return 'decision_tree';
	}

	private function resolveModelArtifacts(string $modelKey): array
	{
		$modelKey = $this->canonicalModelKey($modelKey) ?? 'decision_tree';
		$definition = $this->modelDefinitions()[$modelKey];
		$basePath = $this->mlApiDirectory();

		foreach ($definition['artifacts'] as $artifact) {
			$modelPath = $basePath . $artifact['model'];
			$featurePath = $basePath . $artifact['features'];
			$metricsPath = $basePath . $artifact['metrics'];

			if (is_file($modelPath) && is_file($featurePath)) {
				return [
					'available' => true,
					'model_path' => $modelPath,
					'feature_path' => $featurePath,
					'metrics_path' => is_file($metricsPath) ? $metricsPath : null,
				];
			}
		}

		return [
			'available' => false,
			'model_path' => null,
			'feature_path' => null,
			'metrics_path' => null,
		];
	}

	private function modelIsAvailable(string $modelKey): bool
	{
		return $this->resolveModelArtifacts($modelKey)['available'];
	}

	private function resolveRequestedModelKey(?string $modelKey): string
	{
		$modelKey = $this->normalizeModelKey($modelKey);

		if (! $this->modelIsAvailable($modelKey)) {
			throw new \RuntimeException($this->modelDisplayName($modelKey) . ' belum tersedia untuk prediksi.');
		}

		return $modelKey;
	}

	private function availableModels(): array
	{
		$models = [];

		foreach ($this->modelDefinitions() as $key => $definition) {
			$artifacts = $this->resolveModelArtifacts($key);
			$metrics = $this->readModelMetrics($key, $artifacts);
			$accuracyDisplay = $this->formatAccuracy($metrics['accuracy'] ?? null);
			$strokeMetrics = $metrics['classification_report']['1'] ?? [];

			$models[$key] = [
				'key' => $key,
				'name' => $metrics['model_name'] ?? $definition['label'],
				'label' => $definition['label'],
				'icon' => $definition['icon'],
				'available' => $artifacts['available'],
				'accuracy_display' => $accuracyDisplay,
				'recall_display' => $this->formatAccuracy($strokeMetrics['recall'] ?? null),
				'f1_display' => $this->formatAccuracy($strokeMetrics['f1-score'] ?? null),
				'status_label' => $artifacts['available'] ? 'Siap Prediksi' : 'Belum Aktif',
				'meta' => $artifacts['available']
					? 'Siap digunakan untuk prediksi'
					: 'Artefak model belum tersedia',
				'metrics' => array_merge($metrics, [
					'accuracy_display' => $accuracyDisplay,
					'model_key' => $key,
				]),
			];
		}

		return $models;
	}

	private function modelDisplayName(string $modelKey): string
	{
		$modelKey = $this->canonicalModelKey($modelKey) ?? 'decision_tree';
		$definition = $this->modelDefinitions()[$modelKey];
		$metrics = $this->readModelMetrics($modelKey);

		return $metrics['model_name'] ?? $definition['label'];
	}

	private function readModelMetrics(string $modelKey, ?array $artifacts = null): array
	{
		$modelKey = $this->canonicalModelKey($modelKey) ?? 'decision_tree';
		$definition = $this->modelDefinitions()[$modelKey];
		$artifacts ??= $this->resolveModelArtifacts($modelKey);

		$defaults = [
			'model_name' => $definition['label'],
			'accuracy' => null,
		];

		$path = $artifacts['metrics_path'] ?? null;
		if (! $path || ! is_file($path)) {
			return $defaults;
		}

		$metrics = json_decode((string) file_get_contents($path), true);
		if (! is_array($metrics)) {
			return $defaults;
		}

		$metrics['model_name'] = $metrics['model_name'] ?? $defaults['model_name'];

		return array_merge($defaults, $metrics);
	}

	private function modelMetrics(?string $modelKey = null): array
	{
		$modelKey = $modelKey ? $this->normalizeModelKey($modelKey) : $this->defaultModelKey();
		$metrics = $this->readModelMetrics($modelKey);
		$metrics['model_key'] = $modelKey;
		$metrics['accuracy_display'] = $this->formatAccuracy($metrics['accuracy'] ?? null);

		return $metrics;
	}

	private function storeHistory(Request $request, array $input, int $prediction, string $modelName): void
	{
		$payload = [
			'user_id' => $request->user()->id,
			'input_data' => json_encode($input),
			'prediction' => $prediction,
		];

		if ($this->historiesHaveModelNameColumn()) {
			$payload['model_name'] = $modelName;
		}

		History::create($payload);
	}

	private function historiesHaveModelNameColumn(): bool
	{
		static $hasColumn = null;

		if ($hasColumn === null) {
			$hasColumn = Schema::hasColumn('histories', 'model_name');
		}

		return $hasColumn;
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

<?php

namespace App\Http\Controllers;

use App\Models\RetrainingDataset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class RetrainingController extends Controller
{
	private const MIN_TOTAL_ROWS = 50;
	private const MIN_CLASS_ROWS = 10;

	private const REQUIRED_COLUMNS = [
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
		'stroke',
	];

	private const CATEGORY_VALUES = [
		'gender' => ['Male', 'Female', 'Other'],
		'ever_married' => ['Yes', 'No'],
		'work_type' => ['Private', 'Self-employed', 'Govt_job', 'children', 'Never_worked'],
		'Residence_type' => ['Urban', 'Rural'],
		'smoking_status' => ['formerly smoked', 'never smoked', 'smokes', 'Unknown'],
	];

	public function index()
	{
		$models = $this->modelOptions();
		$pool = $this->poolSummary($models);

		return view('retraining', [
			'dataset' => session('retraining_dataset'),
			'result' => session('retraining_result'),
			'status' => $pool['status_label'],
			'models' => $models,
			'pool' => $pool,
			'datasets' => RetrainingDataset::latest()->limit(20)->get(),
		]);
	}

	public function upload(Request $request)
	{
		$validated = $request->validate([
			'retraining_file' => 'required|file|mimes:csv,txt,xlsx|max:5120',
			'data_consent' => 'accepted',
		], [
			'data_consent.accepted' => 'Centang pernyataan bahwa label stroke berasal dari diagnosis/sumber data kesehatan terpercaya.',
		]);

		$sourceName = $validated['retraining_file']->getClientOriginalName();

		try {
			$rows = $this->rowsFromSpreadsheet($validated['retraining_file']);
			$validation = $this->validateRows($rows, requireBothStrokeClasses: false);
		} catch (Throwable $exception) {
			$dataset = $this->storeDatasetRecord([
				'source_type' => 'upload',
				'source_name' => $sourceName,
				'status' => RetrainingDataset::STATUS_INVALID,
				'summary' => ['total_rows' => 0, 'valid_rows' => 0, 'stroke_0' => 0, 'stroke_1' => 0],
				'preview' => [],
				'errors' => [$this->rowError('-', '-', $exception->getMessage())],
			]);

			session([
				'retraining_dataset' => $this->datasetSessionPayload($dataset),
				'retraining_result' => null,
			]);

			return redirect()->route('retraining')
				->withErrors(['retraining_file' => $exception->getMessage()]);
		}

		$dataset = $this->persistValidationResult(
			sourceType: 'upload',
			sourceName: $sourceName,
			validation: $validation,
			previewRows: $validation['is_valid'] ? $validation['clean_rows'] : $rows,
		);

		session([
			'retraining_dataset' => $this->datasetSessionPayload($dataset),
			'retraining_result' => null,
		]);

		if (! $validation['is_valid']) {
			return redirect()->route('retraining')
				->withErrors(['dataset' => 'Dataset gagal validasi dan tidak masuk pool retraining.']);
		}

		return redirect()->route('retraining')->with('success', 'Dataset valid dan sudah masuk pool data retraining.');
	}

	public function manual(Request $request)
	{
		$validated = $request->validate([
			'gender' => 'required|in:Male,Female,Other',
			'age' => 'required|numeric|min:0|max:120',
			'hypertension' => 'required|in:0,1',
			'heart_disease' => 'required|in:0,1',
			'ever_married' => 'required|in:Yes,No',
			'work_type' => 'required|in:Private,Self-employed,Govt_job,children,Never_worked',
			'Residence_type' => 'required|in:Urban,Rural',
			'avg_glucose_level' => 'required|numeric|min:40|max:400',
			'bmi' => 'required|numeric|min:10|max:80',
			'smoking_status' => 'required|in:formerly smoked,never smoked,smokes,Unknown',
			'stroke' => 'required|in:0,1',
			'data_consent' => 'accepted',
		], [
			'data_consent.accepted' => 'Centang pernyataan bahwa label stroke berasal dari diagnosis/sumber data kesehatan terpercaya.',
		]);

		$row = [];
		foreach (self::REQUIRED_COLUMNS as $column) {
			$row[$column] = $validated[$column];
		}

		$validation = $this->validateRows([$row], requireBothStrokeClasses: false);
		$dataset = $this->persistValidationResult(
			sourceType: 'manual',
			sourceName: 'Input manual retraining',
			validation: $validation,
			previewRows: [$row],
		);

		session([
			'retraining_dataset' => $this->datasetSessionPayload($dataset),
			'retraining_result' => null,
		]);

		if (! $validation['is_valid']) {
			return redirect()->route('retraining')
				->withErrors(['dataset' => 'Input manual gagal validasi dan tidak masuk pool retraining.']);
		}

		return redirect()->route('retraining')->with('success', 'Data manual valid dan sudah masuk pool data retraining.');
	}

	public function archive(RetrainingDataset $dataset)
	{
		$dataset->update([
			'status' => RetrainingDataset::STATUS_ARCHIVED,
			'archived_at' => now(),
		]);

		session()->forget('retraining_result');

		return redirect()->route('retraining')->with('success', 'Dataset dipindahkan ke arsip.');
	}

	public function start(Request $request)
	{
		$models = $this->modelOptions();
		$pool = $this->poolSummary($models);

		if (! $pool['data_ready']) {
			return redirect()->route('retraining')
				->withErrors(['pool' => 'Data retraining belum mencukupi. ' . implode(' ', $pool['missing_messages'])]);
		}

		if (! $pool['models_ready']) {
			return redirect()->route('retraining')
				->withErrors(['models' => 'Retraining penuh belum bisa dijalankan. Model belum tersedia: ' . implode(', ', $pool['missing_models']) . '.']);
		}

		if ($pool['training_in_progress']) {
			return redirect()->route('retraining')
				->withErrors(['retraining' => 'Masih ada proses retraining yang sedang berjalan. Tunggu sampai selesai.']);
		}

		$validDatasets = RetrainingDataset::where('status', RetrainingDataset::STATUS_VALID)
			->whereNotNull('stored_path')
			->oldest()
			->get();

		if ($validDatasets->isEmpty()) {
			return redirect()->route('retraining')
				->withErrors(['pool' => 'Belum ada dataset valid di pool retraining.']);
		}

		$combinedPath = $this->combinePoolDatasets($validDatasets);
		$modelKeys = array_keys(array_filter($models, fn ($model) => $model['available']));

		session(['retraining_result' => null]);
		if (! Cache::add('retraining_in_progress', true, now()->addMinutes(30))) {
			return redirect()->route('retraining')
				->withErrors(['retraining' => 'Masih ada proses retraining yang sedang berjalan. Tunggu sampai selesai.']);
		}

		try {
			$response = Http::timeout(240)->post('http://127.0.0.1:5001/retrain', [
				'dataset_path' => Storage::path($combinedPath),
				'models' => $modelKeys,
				'uploaded_by' => $request->user()->name,
			]);

			if (! $response->ok()) {
				throw new \RuntimeException('ML API error: ' . $response->body());
			}

			$result = $response->json() ?? [];
			if (($result['status'] ?? null) !== 'success') {
				throw new \RuntimeException($result['message'] ?? 'Retraining gagal tanpa pesan detail.');
			}
		} catch (Throwable $exception) {
			session([
				'retraining_result' => [
					'status' => 'error',
					'message' => $exception->getMessage(),
				],
			]);
			Cache::forget('retraining_in_progress');

			return redirect()->route('retraining')
				->withErrors(['retraining' => $exception->getMessage()]);
		}

		RetrainingDataset::whereIn('id', $validDatasets->pluck('id'))->update([
			'status' => RetrainingDataset::STATUS_USED,
			'used_at' => now(),
			'updated_at' => now(),
		]);

		session([
			'retraining_result' => $result,
			'retraining_dataset' => null,
		]);
		Cache::forget('retraining_in_progress');

		return redirect()->route('retraining')->with('success', 'Retraining selesai. Data pool yang dipakai sudah ditandai Used for Retraining.');
	}

	private function persistValidationResult(string $sourceType, string $sourceName, array $validation, array $previewRows): RetrainingDataset
	{
		$storedPath = $validation['is_valid']
			? $this->storeCleanDataset($validation['clean_rows'])
			: null;

		return $this->storeDatasetRecord([
			'source_type' => $sourceType,
			'source_name' => $sourceName,
			'stored_path' => $storedPath,
			'status' => $validation['is_valid'] ? RetrainingDataset::STATUS_VALID : RetrainingDataset::STATUS_INVALID,
			'summary' => $validation['summary'],
			'preview' => array_slice($previewRows, 0, 5),
			'errors' => $validation['errors'],
		]);
	}

	private function storeDatasetRecord(array $payload): RetrainingDataset
	{
		$summary = $payload['summary'] ?? [];

		return RetrainingDataset::create([
			'user_id' => auth()->id(),
			'source_type' => $payload['source_type'],
			'source_name' => $payload['source_name'],
			'stored_path' => $payload['stored_path'] ?? null,
			'status' => $payload['status'],
			'total_rows' => (int) ($summary['total_rows'] ?? 0),
			'valid_rows' => (int) ($summary['valid_rows'] ?? 0),
			'stroke_0' => (int) ($summary['stroke_0'] ?? 0),
			'stroke_1' => (int) ($summary['stroke_1'] ?? 0),
			'preview' => $payload['preview'] ?? [],
			'errors' => $payload['errors'] ?? [],
		]);
	}

	private function datasetSessionPayload(RetrainingDataset $dataset): array
	{
		return [
			'uploaded_name' => $dataset->source_name,
			'is_valid' => $dataset->status === RetrainingDataset::STATUS_VALID,
			'preview' => $dataset->preview ?? [],
			'summary' => [
				'total_rows' => $dataset->total_rows,
				'valid_rows' => $dataset->valid_rows,
				'stroke_0' => $dataset->stroke_0,
				'stroke_1' => $dataset->stroke_1,
			],
			'errors' => $dataset->errors ?? [],
			'status' => $dataset->status,
		];
	}

	private function rowsFromSpreadsheet($file): array
	{
		$spreadsheet = IOFactory::load($file->getRealPath());
		$sheet = $spreadsheet->getActiveSheet();
		$rawRows = $sheet->toArray(null, true, true, true);

		if (count($rawRows) < 2) {
			throw new \RuntimeException('File tidak memiliki data.');
		}

		if (count($rawRows) - 1 > 5000) {
			throw new \RuntimeException('Maksimal 5.000 baris per dataset retraining.');
		}

		$headerRow = array_shift($rawRows);
		$headers = [];
		foreach ($headerRow as $column => $value) {
			$headers[$column] = trim((string) $value);
		}

		$missing = array_diff(self::REQUIRED_COLUMNS, array_values($headers));
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
				$row[$header] = is_string($value) ? trim($value) : $value;
			}

			if (! $isEmpty) {
				$rows[] = $row;
			}
		}

		return $rows;
	}

	private function validateRows(array $rows, bool $requireBothStrokeClasses = true): array
	{
		$errors = [];
		$cleanRows = [];
		$strokeCounts = [0 => 0, 1 => 0];

		foreach ($rows as $index => $row) {
			$rowNumber = $index + 2;
			$clean = [];

			foreach (self::REQUIRED_COLUMNS as $column) {
				$value = $row[$column] ?? null;
				if ($value === null || trim((string) $value) === '') {
					$errors[] = $this->rowError($rowNumber, $column, 'Nilai wajib diisi.');
					continue;
				}

				if (array_key_exists($column, self::CATEGORY_VALUES)) {
					$value = trim((string) $value);
					if (! in_array($value, self::CATEGORY_VALUES[$column], true)) {
						$errors[] = $this->rowError($rowNumber, $column, 'Kategori tidak valid.');
						continue;
					}
					$clean[$column] = $value;
					continue;
				}

				if (in_array($column, ['hypertension', 'heart_disease', 'stroke'], true)) {
					if (! in_array((string) $value, ['0', '1'], true)) {
						$errors[] = $this->rowError($rowNumber, $column, 'Nilai harus 0 atau 1.');
						continue;
					}
					$clean[$column] = (int) $value;
					continue;
				}

				if (! is_numeric($value)) {
					$errors[] = $this->rowError($rowNumber, $column, 'Nilai harus angka.');
					continue;
				}

				$numericValue = (float) $value;
				$rangeError = $this->rangeError($column, $numericValue);
				if ($rangeError !== null) {
					$errors[] = $this->rowError($rowNumber, $column, $rangeError);
					continue;
				}

				$clean[$column] = $numericValue;
			}

			if (count($clean) === count(self::REQUIRED_COLUMNS)) {
				$strokeCounts[$clean['stroke']]++;
				$cleanRows[] = $clean;
			}

			if (count($errors) >= 100) {
				$errors[] = [
					'row' => '-',
					'column' => '-',
					'message' => 'Error dibatasi 100 baris pertama agar tampilan tetap ringan.',
				];
				break;
			}
		}

		if (count($cleanRows) === 0) {
			$errors[] = $this->rowError('-', '-', 'Tidak ada baris valid yang bisa dipakai.');
		}

		if ($requireBothStrokeClasses && ($strokeCounts[0] === 0 || $strokeCounts[1] === 0)) {
			$errors[] = $this->rowError('-', 'stroke', 'Dataset harus memiliki stroke=0 dan stroke=1.');
		}

		return [
			'is_valid' => $errors === [],
			'clean_rows' => $cleanRows,
			'errors' => $errors,
			'summary' => [
				'total_rows' => count($rows),
				'valid_rows' => count($cleanRows),
				'stroke_0' => $strokeCounts[0],
				'stroke_1' => $strokeCounts[1],
			],
		];
	}

	private function rowError($row, string $column, string $message): array
	{
		return [
			'row' => $row,
			'column' => $column,
			'message' => $message,
		];
	}

	private function rangeError(string $column, float $value): ?string
	{
		return match ($column) {
			'age' => $value < 0 || $value > 120 ? 'Age harus di range 0-120.' : null,
			'bmi' => $value < 10 || $value > 80 ? 'BMI harus di range 10-80.' : null,
			'avg_glucose_level' => $value < 40 || $value > 400 ? 'Avg glucose level harus di range 40-400.' : null,
			default => null,
		};
	}

	private function storeCleanDataset(array $rows): string
	{
		$relativePath = 'retraining/validated/retraining_' . now()->format('Ymd_His') . '_' . Str::random(8) . '.csv';
		$handle = fopen('php://temp', 'r+');

		fputcsv($handle, self::REQUIRED_COLUMNS);
		foreach ($rows as $row) {
			fputcsv($handle, array_map(fn ($column) => $row[$column], self::REQUIRED_COLUMNS));
		}

		rewind($handle);
		$csv = stream_get_contents($handle);
		fclose($handle);

		Storage::put($relativePath, $csv);

		return $relativePath;
	}

	private function combinePoolDatasets($datasets): string
	{
		$relativePath = 'retraining/combined/retraining_pool_' . now()->format('Ymd_His') . '_' . Str::random(8) . '.csv';
		$handle = fopen('php://temp', 'r+');
		fputcsv($handle, self::REQUIRED_COLUMNS);

		foreach ($datasets as $dataset) {
			if (! $dataset->stored_path || ! Storage::exists($dataset->stored_path)) {
				continue;
			}

			$file = fopen(Storage::path($dataset->stored_path), 'r');
			if ($file === false) {
				continue;
			}

			$headerSkipped = false;
			while (($row = fgetcsv($file)) !== false) {
				if (! $headerSkipped) {
					$headerSkipped = true;
					continue;
				}
				fputcsv($handle, $row);
			}
			fclose($file);
		}

		rewind($handle);
		$csv = stream_get_contents($handle);
		fclose($handle);

		Storage::put($relativePath, $csv);

		return $relativePath;
	}

	private function poolSummary(array $models): array
	{
		$validDatasets = RetrainingDataset::where('status', RetrainingDataset::STATUS_VALID);
		$totalRows = (int) (clone $validDatasets)->sum('valid_rows');
		$stroke0 = (int) (clone $validDatasets)->sum('stroke_0');
		$stroke1 = (int) (clone $validDatasets)->sum('stroke_1');

		$missingMessages = [];
		if ($totalRows < self::MIN_TOTAL_ROWS) {
			$missingMessages[] = 'Tambahkan ' . (self::MIN_TOTAL_ROWS - $totalRows) . ' data valid lagi.';
		}
		if ($stroke0 < self::MIN_CLASS_ROWS) {
			$missingMessages[] = 'Tambahkan ' . (self::MIN_CLASS_ROWS - $stroke0) . ' data pasien tidak stroke lagi.';
		}
		if ($stroke1 < self::MIN_CLASS_ROWS) {
			$missingMessages[] = 'Tambahkan ' . (self::MIN_CLASS_ROWS - $stroke1) . ' data pasien stroke lagi.';
		}

		$missingModels = array_values(array_map(
			fn ($model) => $model['name'],
			array_filter($models, fn ($model) => ! $model['available'])
		));

		$dataReady = $totalRows >= self::MIN_TOTAL_ROWS
			&& $stroke0 >= self::MIN_CLASS_ROWS
			&& $stroke1 >= self::MIN_CLASS_ROWS;
		$modelsReady = $missingModels === [];
		$trainingInProgress = (bool) Cache::get('retraining_in_progress', false);
		if ($trainingInProgress) {
			$missingMessages[] = 'Masih ada proses retraining yang sedang berjalan.';
		}
		$canRetrain = $dataReady && $modelsReady && ! $trainingInProgress;

		$statusLabel = 'Belum siap retraining';
		if ($canRetrain) {
			$statusLabel = 'Siap retraining';
		} elseif ($trainingInProgress) {
			$statusLabel = 'Sedang training';
		} elseif ($dataReady && ! $modelsReady) {
			$statusLabel = 'Data siap, menunggu model';
		}

		return [
			'total_rows' => $totalRows,
			'stroke_0' => $stroke0,
			'stroke_1' => $stroke1,
			'min_total_rows' => self::MIN_TOTAL_ROWS,
			'min_class_rows' => self::MIN_CLASS_ROWS,
			'progress' => min(100, (int) round(($totalRows / self::MIN_TOTAL_ROWS) * 100)),
			'missing_messages' => $missingMessages,
			'missing_models' => $missingModels,
			'data_ready' => $dataReady,
			'models_ready' => $modelsReady,
			'training_in_progress' => $trainingInProgress,
			'can_retrain' => $canRetrain,
			'status_label' => $statusLabel,
		];
	}

	private function modelOptions(): array
	{
		return [
			'decision_tree' => [
				'name' => 'Decision Tree',
				'icon' => 'fa-tree',
				'available' => $this->modelArtifactAvailable('decision_tree'),
			],
			'knn' => [
				'name' => 'KNN',
				'icon' => 'fa-diagram-project',
				'available' => $this->modelArtifactAvailable('knn'),
			],
			'svm' => [
				'name' => 'SVM',
				'icon' => 'fa-vector-square',
				'available' => $this->modelArtifactAvailable('svm'),
			],
		];
	}

	private function modelArtifactAvailable(string $modelKey): bool
	{
		$basePath = base_path('../ml-api/');
		$artifacts = [
			'decision_tree' => [
				['model' => 'DT_model.pkl', 'features' => 'DT_feature_columns.json'],
				['model' => 'model.pkl', 'features' => 'feature_columns.json'],
				['model' => 'active_models/decision_tree_model.pkl', 'features' => 'active_models/decision_tree_feature_columns.json'],
			],
			'knn' => [
				['model' => 'knn_model.pkl', 'features' => 'knn_feature_columns.json'],
				['model' => 'KNN_model.pkl', 'features' => 'KNN_feature_columns.json'],
				['model' => 'active_models/knn_model.pkl', 'features' => 'active_models/knn_feature_columns.json'],
			],
			'svm' => [
				['model' => 'svm_model.pkl', 'features' => 'svm_feature_columns.json'],
				['model' => 'SVM_model.pkl', 'features' => 'SVM_feature_columns.json'],
				['model' => 'active_models/svm_model.pkl', 'features' => 'active_models/svm_feature_columns.json'],
			],
		];

		foreach ($artifacts[$modelKey] ?? [] as $artifact) {
			if (is_file($basePath . $artifact['model']) && is_file($basePath . $artifact['features'])) {
				return true;
			}
		}

		return false;
	}
}

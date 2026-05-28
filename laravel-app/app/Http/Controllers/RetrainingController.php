<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class RetrainingController extends Controller
{
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
		return view('retraining', [
			'dataset' => session('retraining_dataset'),
			'result' => session('retraining_result'),
			'status' => session('retraining_status', 'Belum mulai'),
			'models' => $this->modelOptions(),
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

		try {
			$rows = $this->rowsFromSpreadsheet($validated['retraining_file']);
			$validation = $this->validateRows($rows);
		} catch (Throwable $exception) {
			return back()
				->withErrors(['retraining_file' => $exception->getMessage()])
				->withInput();
		}

		if (! $validation['is_valid']) {
			session([
				'retraining_status' => 'Validasi gagal',
				'retraining_dataset' => [
					'uploaded_name' => $validated['retraining_file']->getClientOriginalName(),
					'is_valid' => false,
					'preview' => array_slice($rows, 0, 5),
					'summary' => $validation['summary'],
					'errors' => $validation['errors'],
				],
				'retraining_result' => null,
			]);

			return redirect()->route('retraining');
		}

		$storedPath = $this->storeCleanDataset($validation['clean_rows']);

		session([
			'retraining_status' => 'Siap retraining',
			'retraining_dataset' => [
				'uploaded_name' => $validated['retraining_file']->getClientOriginalName(),
				'stored_path' => $storedPath,
				'absolute_path' => Storage::path($storedPath),
				'is_valid' => true,
				'preview' => array_slice($validation['clean_rows'], 0, 5),
				'summary' => $validation['summary'],
				'errors' => [],
			],
			'retraining_result' => null,
		]);

		return redirect()->route('retraining')->with('success', 'Dataset valid dan siap untuk retraining.');
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
		if (! $validation['is_valid']) {
			session([
				'retraining_status' => 'Validasi gagal',
				'retraining_dataset' => [
					'uploaded_name' => 'Input manual retraining',
					'is_valid' => false,
					'preview' => [$row],
					'summary' => $validation['summary'],
					'errors' => $validation['errors'],
				],
				'retraining_result' => null,
			]);

			return redirect()->route('retraining');
		}

		$storedPath = $this->storeCleanDataset($validation['clean_rows']);

		session([
			'retraining_status' => 'Siap retraining',
			'retraining_dataset' => [
				'uploaded_name' => 'Input manual retraining',
				'stored_path' => $storedPath,
				'absolute_path' => Storage::path($storedPath),
				'is_valid' => true,
				'preview' => $validation['clean_rows'],
				'summary' => $validation['summary'],
				'errors' => [],
			],
			'retraining_result' => null,
		]);

		return redirect()->route('retraining')->with('success', 'Data manual valid dan siap untuk retraining.');
	}

	public function start(Request $request)
	{
		$validated = $request->validate([
			'models' => 'required|array|min:1',
			'models.*' => 'in:decision_tree,knn',
		]);

		$dataset = session('retraining_dataset');
		if (! is_array($dataset) || ! ($dataset['is_valid'] ?? false) || empty($dataset['absolute_path'])) {
			return redirect()->route('retraining')
				->withErrors(['dataset' => 'Upload dataset valid terlebih dahulu sebelum retraining.']);
		}

		if (! is_file($dataset['absolute_path'])) {
			return redirect()->route('retraining')
				->withErrors(['dataset' => 'File dataset valid tidak ditemukan. Silakan upload ulang.']);
		}

		session(['retraining_status' => 'Sedang training']);

		try {
			$response = Http::timeout(240)->post('http://127.0.0.1:5001/retrain', [
				'dataset_path' => $dataset['absolute_path'],
				'models' => $validated['models'],
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
				'retraining_status' => 'Gagal',
				'retraining_result' => [
					'status' => 'error',
					'message' => $exception->getMessage(),
				],
			]);

			return redirect()->route('retraining')
				->withErrors(['retraining' => $exception->getMessage()]);
		}

		session([
			'retraining_status' => 'Selesai',
			'retraining_result' => $result,
		]);

		return redirect()->route('retraining')->with('success', 'Retraining selesai. Model lama sudah dibackup sebelum model baru disimpan.');
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

	private function modelOptions(): array
	{
		return [
			'decision_tree' => [
				'name' => 'Decision Tree',
				'icon' => 'fa-tree',
				'available' => true,
			],
			'knn' => [
				'name' => 'KNN',
				'icon' => 'fa-diagram-project',
				'available' => true,
			],
			'svm' => [
				'name' => 'SVM',
				'icon' => 'fa-vector-square',
				'available' => false,
			],
		];
	}
}

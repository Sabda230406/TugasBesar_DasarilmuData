<?php

namespace App\Jobs;

use App\Models\HistoryRetrainingUsage;
use App\Models\ModelVersion;
use App\Models\RetrainingDataset;
use App\Models\RetrainingRun;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class RunRetrainingJob implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	public int $timeout = 600;

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

	public function __construct(public int $runId)
	{
	}

	public function handle(): void
	{
		$run = RetrainingRun::findOrFail($this->runId);

		try {
			$this->updateRun($run, [
				'status' => RetrainingRun::STATUS_RUNNING,
				'stage' => 'preparing_dataset',
				'progress' => 12,
				'message' => 'Menyiapkan dataset terpilih.',
				'started_at' => now(),
			]);

			$datasets = RetrainingDataset::whereIn('id', $run->selected_dataset_ids ?? [])
				->where('status', RetrainingDataset::STATUS_VALID)
				->whereNotNull('stored_path')
				->oldest()
				->get();

			if ($datasets->isEmpty()) {
				throw new \RuntimeException('Tidak ada dataset valid terpilih untuk retraining.');
			}

			$combinedPath = $this->combinePoolDatasets($datasets);
			$progressPath = 'retraining/progress/run_' . $run->id . '.json';
			Storage::put($progressPath, json_encode([
				'stage' => 'preparing_dataset',
				'progress' => 15,
				'message' => 'Dataset berhasil digabung.',
			], JSON_PRETTY_PRINT));

			$this->updateRun($run, [
				'combined_dataset_path' => $combinedPath,
				'progress_path' => $progressPath,
				'stage' => 'training_models',
				'progress' => 20,
				'message' => 'Mengirim dataset ke ML API.',
			]);

			$response = Http::timeout(540)->post('http://127.0.0.1:5001/retrain', [
				'dataset_path' => Storage::path($combinedPath),
				'models' => $run->selected_model_keys ?? [],
				'uploaded_by' => $run->user?->name ?? 'system',
				'progress_path' => Storage::path($progressPath),
			]);

			if (! $response->ok()) {
				throw new \RuntimeException('ML API error: ' . $response->body());
			}

			$result = $response->json() ?? [];
			if (($result['status'] ?? null) !== 'success') {
				throw new \RuntimeException($result['message'] ?? 'Retraining gagal tanpa pesan detail.');
			}

			$this->storeModelVersions($run, $result);

			$datasetIds = $datasets->pluck('id')->all();

			RetrainingDataset::whereIn('id', $datasetIds)->update([
				'status' => RetrainingDataset::STATUS_USED,
				'used_at' => now(),
				'updated_at' => now(),
			]);

			HistoryRetrainingUsage::whereIn('retraining_dataset_id', $datasetIds)->update([
				'retraining_run_id' => $run->id,
				'used_at' => now(),
				'updated_at' => now(),
			]);

			if ((bool) ($result['activated'] ?? false)) {
				RetrainingRun::where('id', '!=', $run->id)->update([
					'is_active' => false,
					'updated_at' => now(),
				]);
			}

			$this->updateRun($run, [
				'status' => RetrainingRun::STATUS_COMPLETED,
				'is_active' => (bool) ($result['activated'] ?? false),
				'stage' => (bool) ($result['activated'] ?? false) ? 'completed' : 'completed_not_activated',
				'progress' => 100,
				'message' => $result['message'] ?? 'Retraining selesai.',
				'result' => $result,
				'finished_at' => now(),
				'activated_at' => (bool) ($result['activated'] ?? false) ? now() : null,
			]);
		} catch (Throwable $exception) {
			$this->updateRun($run, [
				'status' => RetrainingRun::STATUS_FAILED,
				'stage' => 'failed',
				'progress' => 100,
				'message' => 'Retraining gagal.',
				'error_message' => $exception->getMessage(),
				'finished_at' => now(),
			]);

			if ($run->progress_path) {
				Storage::put($run->progress_path, json_encode([
					'stage' => 'failed',
					'progress' => 100,
					'message' => $exception->getMessage(),
				], JSON_PRETTY_PRINT));
			}

			throw $exception;
		}
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

	private function storeModelVersions(RetrainingRun $run, array $result): void
	{
		$activated = (bool) ($result['activated'] ?? false);
		$defaultModelKey = $run->selected_model_keys[0] ?? array_key_first($result['models'] ?? []) ?? null;

		if ($activated) {
			ModelVersion::query()->update([
				'is_default' => false,
				'updated_at' => now(),
			]);
		}

		foreach (($result['models'] ?? []) as $modelKey => $modelResult) {
			$version = $modelResult['version'] ?? [];
			$metrics = $modelResult['metrics'] ?? [];
			$eligibility = $modelResult['eligibility'] ?? [];
			$isActive = $activated && (bool) ($eligibility['accepted'] ?? false);
			$isDefault = $isActive && $modelKey === $defaultModelKey;
			$versionUid = $version['version_id'] ?? ($modelKey . '-' . now()->format('YmdHis') . '-' . Str::random(6));

			if ($isActive) {
				ModelVersion::where('model_key', $modelKey)
					->where('status', ModelVersion::STATUS_ACTIVE)
					->update([
						'status' => ModelVersion::STATUS_AVAILABLE,
						'is_active' => false,
						'updated_at' => now(),
					]);
			}

			ModelVersion::updateOrCreate(
				['version_uid' => $versionUid],
				[
					'model_key' => $modelKey,
					'model_name' => $modelResult['model_name'] ?? $metrics['model_name'] ?? ucfirst(str_replace('_', ' ', $modelKey)),
					'status' => $isActive
						? ModelVersion::STATUS_ACTIVE
						: ((bool) ($eligibility['accepted'] ?? false) ? ModelVersion::STATUS_AVAILABLE : ModelVersion::STATUS_REJECTED),
					'is_active' => $isActive,
					'is_default' => $isDefault,
					'metrics' => $metrics,
					'evaluation_metrics' => $metrics['evaluation_metrics'] ?? $metrics,
					'training_metrics' => $metrics['training_validation_metrics'] ?? null,
					'eligibility' => $eligibility,
					'artifact_model_path' => $version['model_path'] ?? null,
					'artifact_features_path' => $version['features_path'] ?? null,
					'artifact_metrics_path' => $version['metrics_path'] ?? null,
					'retraining_run_id' => $run->id,
					'retrained_at' => $this->parseRetrainedAt($metrics['retrained_at'] ?? null),
					'activated_at' => $isActive ? now() : null,
				]
			);
		}
	}

	private function parseRetrainedAt(?string $value): Carbon
	{
		if (! $value) {
			return now();
		}

		try {
			return Carbon::createFromFormat('Ymd-His', $value);
		} catch (Throwable) {
			try {
				return Carbon::parse($value);
			} catch (Throwable) {
				return now();
			}
		}
	}

	private function updateRun(RetrainingRun $run, array $attributes): void
	{
		$run->forceFill($attributes)->save();
		$run->refresh();
	}
}

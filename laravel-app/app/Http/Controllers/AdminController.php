<?php

namespace App\Http\Controllers;

use App\Models\History;
use App\Models\HistoryRetrainingUsage;
use App\Models\ModelVersion;
use App\Models\RetrainingDataset;
use App\Models\RetrainingRun;
use App\Models\User;
use App\Jobs\RunRetrainingJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    private const MIN_TOTAL_ROWS = 50;
    private const MIN_CLASS_ROWS = 10;
    private const RETRAINING_LOCK_KEY = 'retraining_in_progress';
    private const RETRAINING_LOCK_STARTED_AT_KEY = 'retraining_started_at';
    private const RETRAINING_LOCK_TTL_MINUTES = 15;
    private const RETRAINING_COLUMNS = [
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

    public function index(): View
    {
        $models = $this->modelOptions();
        $pool = $this->poolSummary($models);

        return view('admin.dashboard', [
            'stats' => [
                'users' => User::count(),
                'histories' => History::count(),
                'validRetrainingRows' => $pool['total_rows'],
                'readyModels' => count(array_filter($models, fn ($model) => $model['available'])),
                'totalModels' => count($models),
            ],
            'models' => $models,
            'pool' => $pool,
            'latestDatasets' => RetrainingDataset::with('user')->latest()->limit(5)->get(),
            'latestHistories' => History::with('user')->latest()->limit(5)->get(),
        ]);
    }

    public function users(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $role = $request->query('role');

        $users = User::query()
            ->withCount('histories')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when(in_array($role, ['admin', 'user'], true), fn ($query) => $query->where('role', $role))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.users', [
            'users' => $users,
            'adminCount' => $this->adminCount(),
            'filters' => [
                'search' => $search,
                'role' => $role,
            ],
        ]);
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'in:admin,user'],
        ]);

        if ($user->is($request->user()) && $validated['role'] !== 'admin' && $this->adminCount() <= 1) {
            return back()->withErrors(['role' => 'Role akun admin terakhir tidak boleh diturunkan.']);
        }

        $user->update(['role' => $validated['role']]);

        return back()->with('success', 'Role user berhasil diperbarui.');
    }

    public function destroyUser(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['user' => 'Admin tidak boleh menghapus akun dirinya sendiri.']);
        }

        if ($user->isAdmin() && $this->adminCount() <= 1) {
            return back()->withErrors(['user' => 'Admin terakhir tidak boleh dihapus.']);
        }

        // History dan dataset tetap disimpan sebagai arsip sistem setelah user dihapus.
        $user->histories()->update(['user_id' => null]);
        $user->retrainingDatasets()->update(['user_id' => null]);
        $user->delete();

        return back()->with('success', 'User berhasil dihapus. Riwayat terkait tetap disimpan sebagai data sistem.');
    }

    public function retraining(Request $request): View
    {
        $models = $this->modelOptions();
        $pool = $this->poolSummary($models);
        $historySummary = $this->historyRetrainingSummary();
        $latestRun = RetrainingRun::with('user')->latest()->first();
        $status = $request->query('status');
        $source = $request->query('source');
        $search = trim((string) $request->query('search', ''));

        $datasets = RetrainingDataset::query()
            ->with('user')
            ->when(in_array($status, $this->datasetStatuses(), true), fn ($query) => $query->where('status', $status))
            ->when(in_array($source, ['upload', 'manual', 'history'], true), fn ($query) => $query->where('source_type', $source))
            ->when($search !== '', fn ($query) => $query->where('source_name', 'like', "%{$search}%"))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.retraining', [
            'models' => $models,
            'pool' => $pool,
            'historySummary' => $historySummary,
            'datasets' => $datasets,
            'statuses' => $this->datasetStatuses(),
            'result' => session('retraining_result'),
            'latestRun' => $latestRun,
            'filters' => [
                'status' => $status,
                'source' => $source,
                'search' => $search,
            ],
        ]);
    }

    public function importHistoryToRetraining(Request $request): RedirectResponse
    {
        $historyData = $this->collectHistoryRetrainingRows();

        if ($historyData['rows'] === []) {
            return back()->withErrors(['history' => 'Belum ada history prediksi valid yang bisa dimasukkan ke pool retraining.']);
        }

        $storedPath = $this->storeRetrainingRows($historyData['rows'], 'history_predictions');
        $previewRows = array_map(fn ($row) => collect($row)->except('__history_id')->all(), array_slice($historyData['rows'], 0, 5));
        $dataset = RetrainingDataset::create([
            'user_id' => $request->user()->id,
            'source_type' => 'history',
            'source_name' => 'History prediksi user - ' . now()->format('Y-m-d H:i:s'),
            'stored_path' => $storedPath,
            'status' => RetrainingDataset::STATUS_VALID,
            'total_rows' => $historyData['total_rows'],
            'valid_rows' => count($historyData['rows']),
            'stroke_0' => $historyData['stroke_0'],
            'stroke_1' => $historyData['stroke_1'],
            'preview' => $previewRows,
            'errors' => array_slice($historyData['errors'], 0, 20),
        ]);

        foreach ($historyData['rows'] as $row) {
            if (! isset($row['__history_id'])) {
                continue;
            }

            HistoryRetrainingUsage::firstOrCreate(
                ['history_id' => $row['__history_id']],
                [
                    'retraining_dataset_id' => $dataset->id,
                    'imported_at' => now(),
                ]
            );
        }

        session()->forget('retraining_result');

        return back()->with(
            'success',
            "History prediksi berhasil dimasukkan ke pool retraining sebagai dataset #{$dataset->id}. Total valid: {$dataset->valid_rows} data."
        );
    }

    public function archiveDataset(RetrainingDataset $dataset): RedirectResponse
    {
        if ($dataset->status === RetrainingDataset::STATUS_USED) {
            return back()->withErrors(['dataset' => 'Dataset yang sudah dipakai retraining tidak bisa diarsipkan dari sini.']);
        }

        $dataset->update([
            'status' => RetrainingDataset::STATUS_ARCHIVED,
            'archived_at' => now(),
        ]);

        return back()->with('success', 'Dataset berhasil dipindahkan ke arsip.');
    }

    public function restoreDataset(RetrainingDataset $dataset): RedirectResponse
    {
        if ($dataset->status !== RetrainingDataset::STATUS_ARCHIVED) {
            return back()->withErrors(['dataset' => 'Hanya dataset berstatus Archived yang bisa direstore.']);
        }

        if ($dataset->valid_rows <= 0 || blank($dataset->stored_path)) {
            return back()->withErrors(['dataset' => 'Dataset ini tidak bisa direstore karena tidak memiliki data valid tersimpan.']);
        }

        $dataset->update([
            'status' => RetrainingDataset::STATUS_VALID,
            'archived_at' => null,
        ]);

        return back()->with('success', 'Dataset berhasil direstore ke pool valid.');
    }

    public function resetRetrainingLock(): RedirectResponse
    {
        $this->clearRetrainingLock();
        RetrainingRun::whereIn('status', [RetrainingRun::STATUS_QUEUED, RetrainingRun::STATUS_RUNNING])->update([
            'status' => RetrainingRun::STATUS_FAILED,
            'stage' => 'failed',
            'progress' => 100,
            'message' => 'Status retraining direset admin.',
            'error_message' => 'Status retraining direset admin.',
            'finished_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Status retraining berhasil direset. Jika Flask masih memproses training, tunggu prosesnya selesai sebelum mulai ulang.');
    }

    public function startRetraining(Request $request): RedirectResponse
    {
        $this->syncLegacyModelVersions();

        $validated = $request->validate([
            'dataset_ids' => ['required', 'array', 'min:1'],
            'dataset_ids.*' => ['integer', 'exists:retraining_datasets,id'],
            'models' => ['nullable', 'array'],
            'models.*' => ['string', 'in:decision_tree,knn,svm'],
        ], [
            'dataset_ids.required' => 'Pilih minimal satu dataset valid untuk retraining.',
        ]);

        $runningRun = RetrainingRun::whereIn('status', [RetrainingRun::STATUS_QUEUED, RetrainingRun::STATUS_RUNNING])->latest()->first();
        if ($runningRun) {
            return back()->withErrors(['retraining' => 'Masih ada proses retraining yang berjalan. Pantau progress sebelum mulai lagi.']);
        }

        $datasets = RetrainingDataset::whereIn('id', $validated['dataset_ids'])
            ->where('status', RetrainingDataset::STATUS_VALID)
            ->whereNotNull('stored_path')
            ->get();

        if ($datasets->count() !== count(array_unique($validated['dataset_ids']))) {
            return back()->withErrors(['dataset_ids' => 'Hanya dataset berstatus Valid yang belum pernah dipakai yang boleh dipilih.']);
        }

        $models = $this->modelOptions();
        $modelKeys = $validated['models'] ?? array_keys(array_filter($models, fn ($model) => $model['available']));
        $missingModels = array_values(array_filter($modelKeys, fn ($modelKey) => empty($models[$modelKey]['available'])));
        if ($missingModels !== []) {
            return back()->withErrors(['models' => 'Ada model yang belum tersedia untuk retraining: ' . implode(', ', $missingModels)]);
        }

        $selectedPool = $this->poolSummaryForDatasets($datasets, $models);
        if (! $selectedPool['data_ready']) {
            return back()->withErrors(['pool' => 'Dataset terpilih belum mencukupi. ' . implode(' ', $selectedPool['missing_messages'])]);
        }

        $run = RetrainingRun::create([
            'user_id' => $request->user()->id,
            'status' => RetrainingRun::STATUS_QUEUED,
            'stage' => 'queued',
            'progress' => 5,
            'message' => 'Retraining masuk antrean.',
            'selected_dataset_ids' => $datasets->pluck('id')->values()->all(),
            'selected_model_keys' => array_values($modelKeys),
        ]);

        RunRetrainingJob::dispatch($run->id);

        session()->forget('retraining_result');

        return back()->with('success', "Retraining #{$run->id} dimulai di background. Kamu boleh pindah halaman, proses tetap berjalan.");
    }

    public function retrainingRunStatus(?RetrainingRun $run = null)
    {
        $run ??= RetrainingRun::latest()->first();
        if (! $run) {
            return response()->json(['status' => 'empty']);
        }

        $payload = $run->toArray();
        $progressPayload = [];
        if ($run->progress_path && Storage::exists($run->progress_path)) {
            $decoded = json_decode((string) Storage::get($run->progress_path), true);
            if (is_array($decoded)) {
                $progressPayload = $decoded;
            }
        }

        return response()->json([
            'status' => 'success',
            'run' => array_merge($payload, [
                'stage' => $progressPayload['stage'] ?? $run->stage,
                'progress' => $progressPayload['progress'] ?? $run->progress,
                'message' => $progressPayload['message'] ?? $run->message,
            ]),
        ]);
    }

    public function models(): View
    {
        $this->syncLegacyModelVersions();

        $runs = RetrainingRun::query()
            ->with([
                'user',
                'modelVersions' => fn ($query) => $query->orderBy('model_key'),
            ])
            ->where('status', RetrainingRun::STATUS_COMPLETED)
            ->whereHas('modelVersions')
            ->latest('is_active')
            ->latest('activated_at')
            ->latest('finished_at')
            ->latest()
            ->get();

        return view('admin.models', [
            'runs' => $runs,
            'activeRun' => $runs->firstWhere('is_active', true),
        ]);
    }

    public function activateRetrainingRun(RetrainingRun $run): RedirectResponse
    {
        if ($run->status !== RetrainingRun::STATUS_COMPLETED) {
            return back()->withErrors(['model' => 'Hanya retraining yang sudah completed yang bisa dijadikan aktif.']);
        }

        $versions = $run->modelVersions()
            ->where('status', '!=', ModelVersion::STATUS_REJECTED)
            ->orderBy('model_key')
            ->get();

        if ($versions->isEmpty()) {
            return back()->withErrors(['model' => 'Retraining ini tidak punya model yang lolos evaluasi untuk diaktifkan.']);
        }

        foreach ($versions as $version) {
            if (! $version->artifact_model_path || ! is_file($version->artifact_model_path)) {
                return back()->withErrors(['model' => "Artefak {$version->model_name} pada retraining #{$run->id} tidak ditemukan."]);
            }

            $response = Http::timeout(60)->post('http://127.0.0.1:5001/models/activate', [
                'model_key' => $version->model_key,
                'version_id' => $version->version_uid,
            ]);

            if (! $response->ok()) {
                return back()->withErrors(['model' => "ML API gagal mengaktifkan {$version->model_name}: " . $response->body()]);
            }
        }

        RetrainingRun::query()->update([
            'is_active' => false,
            'updated_at' => now(),
        ]);
        $run->update([
            'is_active' => true,
            'activated_at' => now(),
        ]);

        ModelVersion::query()->update([
            'is_default' => false,
            'updated_at' => now(),
        ]);
        ModelVersion::where('status', ModelVersion::STATUS_ACTIVE)
            ->orWhere('is_active', true)
            ->update([
                'status' => ModelVersion::STATUS_AVAILABLE,
                'is_active' => false,
                'updated_at' => now(),
            ]);

        $defaultModelKey = $run->selected_model_keys[0] ?? $versions->first()->model_key;
        foreach ($versions as $version) {
            $version->update([
                'status' => ModelVersion::STATUS_ACTIVE,
                'is_active' => true,
                'is_default' => $version->model_key === $defaultModelKey,
                'activated_at' => now(),
            ]);
        }

        return back()->with('success', "Retraining #{$run->id} sekarang aktif. Prediksi memakai paket model dari retraining ini.");
    }

    public function exportHistory(): StreamedResponse
    {
        $filename = 'history-retraining-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, self::RETRAINING_COLUMNS);

            History::with('user')
                ->whereDoesntHave('retrainingUsage')
                ->oldest()
                ->chunk(500, function ($histories) use ($output) {
                foreach ($histories as $history) {
                    $row = $this->historyToRetrainingRow($history);

                    if ($row !== null) {
                        fputcsv($output, array_map(fn ($column) => $row[$column], self::RETRAINING_COLUMNS));
                    }
                }
            });

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function adminCount(): int
    {
        return User::where('role', 'admin')->count();
    }

    private function datasetStatuses(): array
    {
        return [
            RetrainingDataset::STATUS_VALID,
            RetrainingDataset::STATUS_INVALID,
            RetrainingDataset::STATUS_USED,
            RetrainingDataset::STATUS_ARCHIVED,
        ];
    }

    private function historyRetrainingSummary(): array
    {
        $data = $this->collectHistoryRetrainingRows(includeErrors: false);
        $importedCount = HistoryRetrainingUsage::count();
        $usedCount = HistoryRetrainingUsage::whereNotNull('used_at')->count();

        return [
            'total_histories' => History::count(),
            'available_histories' => $data['total_rows'],
            'valid_rows' => count($data['rows']),
            'stroke_0' => $data['stroke_0'],
            'stroke_1' => $data['stroke_1'],
            'imported_rows' => $importedCount,
            'used_rows' => $usedCount,
        ];
    }

    private function collectHistoryRetrainingRows(bool $includeErrors = true, bool $onlyUnused = true): array
    {
        $rows = [];
        $errors = [];
        $totalRows = 0;
        $stroke0 = 0;
        $stroke1 = 0;

        History::query()
            ->when($onlyUnused, fn ($query) => $query->whereDoesntHave('retrainingUsage'))
            ->oldest()
            ->chunk(500, function ($histories) use (&$rows, &$errors, &$totalRows, &$stroke0, &$stroke1, $includeErrors) {
            foreach ($histories as $history) {
                $totalRows++;
                $row = $this->historyToRetrainingRow($history);

                if ($row === null) {
                    if ($includeErrors) {
                        $errors[] = [
                            'history_id' => $history->id,
                            'message' => 'History tidak lengkap atau nilai tidak valid untuk format retraining.',
                        ];
                    }
                    continue;
                }

                $row['stroke'] === 1 ? $stroke1++ : $stroke0++;
                $row['__history_id'] = $history->id;
                $rows[] = $row;
            }
        });

        return [
            'total_rows' => $totalRows,
            'rows' => $rows,
            'errors' => $errors,
            'stroke_0' => $stroke0,
            'stroke_1' => $stroke1,
        ];
    }

    private function historyToRetrainingRow(History $history): ?array
    {
        $input = $this->decodeInputData($history->input_data);
        if ($input === [] || ! in_array((string) $history->prediction, ['0', '1'], true)) {
            return null;
        }

        $row = [];
        foreach (self::RETRAINING_COLUMNS as $column) {
            if ($column === 'stroke') {
                $row[$column] = (int) $history->prediction;
                continue;
            }

            $row[$column] = $input[$column] ?? null;
        }

        return $this->normalizeRetrainingRow($row);
    }

    private function normalizeRetrainingRow(array $row): ?array
    {
        $clean = [];

        foreach (self::RETRAINING_COLUMNS as $column) {
            $value = $row[$column] ?? null;
            if ($value === null || trim((string) $value) === '') {
                return null;
            }

            if (array_key_exists($column, self::CATEGORY_VALUES)) {
                $value = trim((string) $value);
                if (! in_array($value, self::CATEGORY_VALUES[$column], true)) {
                    return null;
                }
                $clean[$column] = $value;
                continue;
            }

            if (in_array($column, ['hypertension', 'heart_disease', 'stroke'], true)) {
                if (! in_array((string) $value, ['0', '1'], true)) {
                    return null;
                }
                $clean[$column] = (int) $value;
                continue;
            }

            if (! is_numeric($value)) {
                return null;
            }

            $numericValue = (float) $value;
            if ($this->rangeError($column, $numericValue)) {
                return null;
            }
            $clean[$column] = $numericValue;
        }

        return $clean;
    }

    private function rangeError(string $column, float $value): bool
    {
        return match ($column) {
            'age' => $value < 0 || $value > 130,
            'bmi' => $value < 0 || $value > 100,
            'avg_glucose_level' => $value < 0 || $value > 500,
            default => false,
        };
    }

    private function storeRetrainingRows(array $rows, string $prefix): string
    {
        $relativePath = 'retraining/validated/' . $prefix . '_' . now()->format('Ymd_His') . '_' . Str::random(8) . '.csv';
        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, self::RETRAINING_COLUMNS);
        foreach ($rows as $row) {
            fputcsv($handle, array_map(fn ($column) => $row[$column], self::RETRAINING_COLUMNS));
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        Storage::put($relativePath, $csv);

        return $relativePath;
    }

    private function poolSummary(array $models): array
    {
        $this->releaseStaleRetrainingLock();

        $validDatasets = RetrainingDataset::where('status', RetrainingDataset::STATUS_VALID);
        $totalRows = (int) (clone $validDatasets)->sum('valid_rows');
        $stroke0 = (int) (clone $validDatasets)->sum('stroke_0');
        $stroke1 = (int) (clone $validDatasets)->sum('stroke_1');
        $missingMessages = [];

        if ($totalRows < self::MIN_TOTAL_ROWS) {
            $missingMessages[] = 'Butuh ' . (self::MIN_TOTAL_ROWS - $totalRows) . ' data valid lagi.';
        }

        if ($stroke0 < self::MIN_CLASS_ROWS) {
            $missingMessages[] = 'Butuh ' . (self::MIN_CLASS_ROWS - $stroke0) . ' data pasien tidak stroke lagi.';
        }

        if ($stroke1 < self::MIN_CLASS_ROWS) {
            $missingMessages[] = 'Butuh ' . (self::MIN_CLASS_ROWS - $stroke1) . ' data pasien stroke lagi.';
        }

        $availableModels = array_values(array_filter($models, fn ($model) => $model['available']));
        $missingModels = array_values(array_map(
            fn ($model) => $model['name'],
            array_filter($models, fn ($model) => ! $model['available'])
        ));

        if ($availableModels === []) {
            $missingMessages[] = 'Belum ada model ML yang tersedia untuk retraining.';
        }

        $trainingInProgress = (bool) Cache::get(self::RETRAINING_LOCK_KEY, false)
            || RetrainingRun::whereIn('status', [RetrainingRun::STATUS_QUEUED, RetrainingRun::STATUS_RUNNING])->exists();
        if ($trainingInProgress) {
            $missingMessages[] = 'Masih ada proses retraining yang sedang berjalan.';
        }

        $dataReady = $totalRows >= self::MIN_TOTAL_ROWS
            && $stroke0 >= self::MIN_CLASS_ROWS
            && $stroke1 >= self::MIN_CLASS_ROWS;
        $modelsReady = $availableModels !== [];
        $canRetrain = $dataReady && $modelsReady && ! $trainingInProgress;

        return [
            'total_rows' => $totalRows,
            'stroke_0' => $stroke0,
            'stroke_1' => $stroke1,
            'min_total_rows' => self::MIN_TOTAL_ROWS,
            'min_class_rows' => self::MIN_CLASS_ROWS,
            'progress' => min(100, (int) round(($totalRows / self::MIN_TOTAL_ROWS) * 100)),
            'data_ready' => $dataReady,
            'models_ready' => $modelsReady,
            'training_in_progress' => $trainingInProgress,
            'can_retrain' => $canRetrain,
            'missing_models' => $missingModels,
            'missing_messages' => $missingMessages,
            'status_label' => $canRetrain ? 'Siap retraining' : ($trainingInProgress ? 'Sedang training' : 'Belum siap retraining'),
        ];
    }

    private function poolSummaryForDatasets($datasets, array $models): array
    {
        $totalRows = (int) $datasets->sum('valid_rows');
        $stroke0 = (int) $datasets->sum('stroke_0');
        $stroke1 = (int) $datasets->sum('stroke_1');
        $missingMessages = [];

        if ($totalRows < self::MIN_TOTAL_ROWS) {
            $missingMessages[] = 'Butuh ' . (self::MIN_TOTAL_ROWS - $totalRows) . ' data valid lagi.';
        }

        if ($stroke0 < self::MIN_CLASS_ROWS) {
            $missingMessages[] = 'Butuh ' . (self::MIN_CLASS_ROWS - $stroke0) . ' data pasien tidak stroke lagi.';
        }

        if ($stroke1 < self::MIN_CLASS_ROWS) {
            $missingMessages[] = 'Butuh ' . (self::MIN_CLASS_ROWS - $stroke1) . ' data pasien stroke lagi.';
        }

        return [
            'total_rows' => $totalRows,
            'stroke_0' => $stroke0,
            'stroke_1' => $stroke1,
            'missing_messages' => $missingMessages,
            'missing_models' => [],
            'data_ready' => $totalRows >= self::MIN_TOTAL_ROWS
                && $stroke0 >= self::MIN_CLASS_ROWS
                && $stroke1 >= self::MIN_CLASS_ROWS,
        ];
    }

    private function syncLegacyModelVersions(): void
    {
        $models = $this->modelOptions();
        $availableModelKeys = array_keys(array_filter($models, fn ($model) => $model['available']));
        if ($availableModelKeys === []) {
            return;
        }

        $hasActiveRun = RetrainingRun::where('is_active', true)->exists();
        $legacyRun = RetrainingRun::firstOrCreate(
            ['stage' => 'legacy_baseline'],
            [
                'status' => RetrainingRun::STATUS_COMPLETED,
                'is_active' => ! $hasActiveRun,
                'progress' => 100,
                'message' => 'Baseline model sebelum retraining.',
                'selected_dataset_ids' => [],
                'selected_model_keys' => $availableModelKeys,
                'finished_at' => now(),
                'activated_at' => ! $hasActiveRun ? now() : null,
            ]
        );

        if (! $hasActiveRun && ! $legacyRun->is_active) {
            $legacyRun->update([
                'is_active' => true,
                'activated_at' => now(),
            ]);
            $legacyRun->refresh();
        }

        foreach ($models as $modelKey => $model) {
            if (! $model['available']) {
                continue;
            }

            $paths = $this->activeModelArtifactPaths($modelKey);
            if (! $paths || ! is_file($paths['model'])) {
                continue;
            }

            $metrics = is_file($paths['metrics'])
                ? json_decode((string) file_get_contents($paths['metrics']), true)
                : [];
            $metrics = is_array($metrics) ? $metrics : [];

            $versionDir = base_path('../ml-api/model_versions/legacy-' . $modelKey);
            if (! is_dir($versionDir)) {
                mkdir($versionDir, 0775, true);
            }

            $versionPaths = [
                'model' => $versionDir . DIRECTORY_SEPARATOR . "{$modelKey}_model.pkl",
                'features' => $versionDir . DIRECTORY_SEPARATOR . "{$modelKey}_feature_columns.json",
                'metrics' => $versionDir . DIRECTORY_SEPARATOR . "{$modelKey}_metrics.json",
            ];

            foreach (['model', 'features', 'metrics'] as $type) {
                if (! is_file($paths[$type] ?? '') || is_file($versionPaths[$type])) {
                    continue;
                }

                copy($paths[$type], $versionPaths[$type]);
            }

            if (! is_file($versionPaths['model']) || ! is_file($versionPaths['features'])) {
                continue;
            }

            ModelVersion::updateOrCreate(
                ['version_uid' => 'legacy-' . $modelKey],
                [
                    'model_key' => $modelKey,
                    'model_name' => $metrics['model_name'] ?? $model['name'],
                    'status' => $legacyRun->is_active ? ModelVersion::STATUS_ACTIVE : ModelVersion::STATUS_AVAILABLE,
                    'is_active' => $legacyRun->is_active,
                    'is_default' => $legacyRun->is_active && $modelKey === $this->defaultModelKey(),
                    'metrics' => $metrics,
                    'evaluation_metrics' => $metrics,
                    'artifact_model_path' => $versionPaths['model'],
                    'artifact_features_path' => $versionPaths['features'],
                    'artifact_metrics_path' => is_file($versionPaths['metrics']) ? $versionPaths['metrics'] : null,
                    'retraining_run_id' => $legacyRun->id,
                    'activated_at' => $legacyRun->is_active ? ($legacyRun->activated_at ?? now()) : null,
                ]
            );
        }
    }

    private function activeModelArtifactPaths(string $modelKey): ?array
    {
        $basePath = base_path('../ml-api/');
        $artifacts = [
            'decision_tree' => [
                ['model' => 'active_models/decision_tree_model.pkl', 'features' => 'active_models/decision_tree_feature_columns.json', 'metrics' => 'active_models/decision_tree_metrics.json'],
                ['model' => 'DT_model.pkl', 'features' => 'DT_feature_columns.json', 'metrics' => 'DT_model_metrics.json'],
                ['model' => 'dt_model.pkl', 'features' => 'dt_feature_columns.json', 'metrics' => 'dt_model_metrics.json'],
                ['model' => 'model.pkl', 'features' => 'feature_columns.json', 'metrics' => 'model_metrics.json'],
            ],
            'knn' => [
                ['model' => 'active_models/knn_model.pkl', 'features' => 'active_models/knn_feature_columns.json', 'metrics' => 'active_models/knn_metrics.json'],
                ['model' => 'knn_model.pkl', 'features' => 'knn_feature_columns.json', 'metrics' => 'knn_model_metrics.json'],
                ['model' => 'KNN_model.pkl', 'features' => 'KNN_feature_columns.json', 'metrics' => 'KNN_model_metrics.json'],
            ],
            'svm' => [
                ['model' => 'active_models/svm_model.pkl', 'features' => 'active_models/svm_feature_columns.json', 'metrics' => 'active_models/svm_metrics.json'],
                ['model' => 'svm_model.pkl', 'features' => 'svm_feature_columns.json', 'metrics' => 'svm_model_metrics.json'],
                ['model' => 'SVM_model.pkl', 'features' => 'SVM_feature_columns.json', 'metrics' => 'SVM_model_metrics.json'],
            ],
        ];

        if (! isset($artifacts[$modelKey])) {
            return null;
        }

        foreach ($artifacts[$modelKey] as $artifact) {
            $modelPath = $basePath . $artifact['model'];
            $featuresPath = $basePath . $artifact['features'];

            if (is_file($modelPath) && is_file($featuresPath)) {
                return [
                    'model' => $modelPath,
                    'features' => $featuresPath,
                    'metrics' => $basePath . $artifact['metrics'],
                ];
            }
        }

        return null;
    }

    private function releaseStaleRetrainingLock(): void
    {
        if (! Cache::get(self::RETRAINING_LOCK_KEY, false)) {
            return;
        }

        $startedAt = Cache::get(self::RETRAINING_LOCK_STARTED_AT_KEY);
        if (! $startedAt || $this->isLockExpired((string) $startedAt)) {
            $this->clearRetrainingLock();
        }
    }

    private function isLockExpired(string $startedAt): bool
    {
        $timestamp = strtotime($startedAt);

        return ! $timestamp || (time() - $timestamp) >= (self::RETRAINING_LOCK_TTL_MINUTES * 60);
    }

    private function clearRetrainingLock(): void
    {
        Cache::forget(self::RETRAINING_LOCK_KEY);
        Cache::forget(self::RETRAINING_LOCK_STARTED_AT_KEY);
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

    private function defaultModelKey(): string
    {
        if (Schema::hasTable('model_versions')) {
            $defaultVersion = ModelVersion::where('is_default', true)->first();
            if ($defaultVersion) {
                return $defaultVersion->model_key;
            }
        }

        $preferred = env('ML_ACTIVE_MODEL', 'decision_tree');
        if (is_string($preferred) && $this->modelArtifactAvailable($preferred)) {
            return $preferred;
        }

        foreach (array_keys($this->modelOptions()) as $modelKey) {
            if ($this->modelArtifactAvailable($modelKey)) {
                return $modelKey;
            }
        }

        return 'decision_tree';
    }

    private function decodeInputData(?string $inputData): array
    {
        if (blank($inputData)) {
            return [];
        }

        $decoded = json_decode($inputData, true);
        if (! is_array($decoded)) {
            return [];
        }

        return $decoded;
    }

    private function formatPrediction(string|int|null $prediction): string
    {
        if ((string) $prediction === '1') {
            return 'Risiko Tinggi';
        }

        if ((string) $prediction === '0') {
            return 'Risiko Rendah';
        }

        return (string) ($prediction ?? '-');
    }
}

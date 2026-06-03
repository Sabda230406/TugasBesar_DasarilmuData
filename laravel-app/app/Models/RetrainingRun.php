<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RetrainingRun extends Model
{
	public const STATUS_QUEUED = 'queued';
	public const STATUS_RUNNING = 'running';
	public const STATUS_COMPLETED = 'completed';
	public const STATUS_FAILED = 'failed';

	protected $fillable = [
		'user_id',
		'status',
		'is_active',
		'stage',
		'progress',
		'message',
		'selected_dataset_ids',
		'selected_model_keys',
		'combined_dataset_path',
		'progress_path',
		'result',
		'error_message',
		'started_at',
		'finished_at',
		'activated_at',
		'archived_at',
	];

	protected $casts = [
		'is_active' => 'boolean',
		'selected_dataset_ids' => 'array',
		'selected_model_keys' => 'array',
		'result' => 'array',
		'started_at' => 'datetime',
		'finished_at' => 'datetime',
		'activated_at' => 'datetime',
		'archived_at' => 'datetime',
	];

	public function user(): BelongsTo
	{
		return $this->belongsTo(User::class);
	}

	public function historyUsages(): HasMany
	{
		return $this->hasMany(HistoryRetrainingUsage::class);
	}

	public function modelVersions(): HasMany
	{
		return $this->hasMany(ModelVersion::class);
	}

	public function isRunning(): bool
	{
		return in_array($this->status, [self::STATUS_QUEUED, self::STATUS_RUNNING], true);
	}
}

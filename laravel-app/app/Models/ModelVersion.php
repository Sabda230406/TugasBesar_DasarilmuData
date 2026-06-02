<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModelVersion extends Model
{
	public const STATUS_ACTIVE = 'active';
	public const STATUS_AVAILABLE = 'available';
	public const STATUS_REJECTED = 'rejected';

	protected $fillable = [
		'version_uid',
		'model_key',
		'model_name',
		'status',
		'is_active',
		'is_default',
		'metrics',
		'evaluation_metrics',
		'training_metrics',
		'eligibility',
		'artifact_model_path',
		'artifact_features_path',
		'artifact_metrics_path',
		'retraining_run_id',
		'retrained_at',
		'activated_at',
	];

	protected $casts = [
		'is_active' => 'boolean',
		'is_default' => 'boolean',
		'metrics' => 'array',
		'evaluation_metrics' => 'array',
		'training_metrics' => 'array',
		'eligibility' => 'array',
		'retrained_at' => 'datetime',
		'activated_at' => 'datetime',
	];

	public function retrainingRun(): BelongsTo
	{
		return $this->belongsTo(RetrainingRun::class);
	}
}

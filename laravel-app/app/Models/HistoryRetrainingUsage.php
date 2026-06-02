<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoryRetrainingUsage extends Model
{
	protected $fillable = [
		'history_id',
		'retraining_dataset_id',
		'retraining_run_id',
		'imported_at',
		'used_at',
	];

	protected $casts = [
		'imported_at' => 'datetime',
		'used_at' => 'datetime',
	];

	public function history(): BelongsTo
	{
		return $this->belongsTo(History::class);
	}

	public function retrainingDataset(): BelongsTo
	{
		return $this->belongsTo(RetrainingDataset::class);
	}

	public function retrainingRun(): BelongsTo
	{
		return $this->belongsTo(RetrainingRun::class);
	}
}

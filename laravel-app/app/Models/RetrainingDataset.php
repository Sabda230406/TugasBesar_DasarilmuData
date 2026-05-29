<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetrainingDataset extends Model
{
	public const STATUS_VALID = 'Valid';
	public const STATUS_INVALID = 'Invalid';
	public const STATUS_USED = 'Used for Retraining';
	public const STATUS_ARCHIVED = 'Archived';

	protected $fillable = [
		'user_id',
		'source_type',
		'source_name',
		'stored_path',
		'status',
		'total_rows',
		'valid_rows',
		'stroke_0',
		'stroke_1',
		'preview',
		'errors',
		'used_at',
		'archived_at',
	];

	protected $casts = [
		'preview' => 'array',
		'errors' => 'array',
		'used_at' => 'datetime',
		'archived_at' => 'datetime',
	];

	public function user(): BelongsTo
	{
		return $this->belongsTo(User::class);
	}
}

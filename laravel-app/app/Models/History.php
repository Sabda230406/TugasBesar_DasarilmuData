<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class History extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'input_data', 'prediction', 'model_name'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function retrainingUsage(): HasOne
    {
        return $this->hasOne(HistoryRetrainingUsage::class);
    }
}

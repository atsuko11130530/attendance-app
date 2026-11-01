<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\CorrectionRequest;


class ProposalBreak extends Model
{
    use HasFactory;

    protected $fillable = [
        'correction_request_id',
        'break_in',
        'break_out',
    ];

    public function correctionRequest()
    {
        return $this->belongsTo(CorrectionRequest::class);
    }

    

    
}

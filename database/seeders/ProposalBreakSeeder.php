<?php

namespace Database\Seeders;

use App\Models\ProposalBreak;
use App\Models\CorrectionRequest;
use Illuminate\Database\Seeder;

class ProposalBreakSeeder extends Seeder
{
    public function run(): void
    {
        $correctionRequests = CorrectionRequest::all();
        foreach ($correctionRequests as $correctionRequest) {

            ProposalBreak::factory()
                ->create([
                    'correction_request_id' => $correctionRequest->id,
                    ]);
            }
    }
}

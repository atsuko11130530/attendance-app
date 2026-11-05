<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceRecordResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'user_id' => $this->user_id,

            'user' => $this->whenLoaded('user'),

            'date' => $this->date,

            'clock_in' => $this->clock_in,

            'clock_out' => $this->clock_out,

            'total_time' => $this->total_time,

            'total_break_time' => $this->total_break_time,

            'comment' => $this->comment,

            'breaks' => $this->whenLoaded('breaks'),

            'applications' => $this->whenLoaded('applications'),
        ];
    }
}

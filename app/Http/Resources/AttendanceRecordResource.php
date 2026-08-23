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
            'id'               => $this->id,
            'user_id'          => $this->user_id,
            'user'             => new \App\Http\Resources\UserResource($this->whenLoaded('user')),
            'date'             => $this->date,
            'clock_in'         => $this->clock_in ? \Carbon\Carbon::parse($this->clock_in)->format('H:i:s') : null,
            'clock_out'        => $this->clock_out ? \Carbon\Carbon::parse($this->clock_out)->format('H:i:s') : null,
            'total_time'       => $this->total_time,
            'total_break_time' => $this->total_break_time,
            'comment'          => $this->comment,
            'breaks'           => \App\Http\Resources\AttendanceBreakResource::collection($this->whenLoaded('breaks')),
            'applications' => $this->whenLoaded('correction', function () {
                return $this->correction
                    ? [new \App\Http\Resources\ApplicationResource($this->correction)]
                    : [];
            }, []),
        ];
    }
}

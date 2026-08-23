<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'requested_clock_in'  => $this->requested_clock_in
                ? \Carbon\Carbon::parse($this->requested_clock_in)->format('H:i:s') : null,
            'requested_clock_out' => $this->requested_clock_out
                ? \Carbon\Carbon::parse($this->requested_clock_out)->format('H:i:s') : null,
            'reason'              => $this->reason,
            'status'              => $this->status,
        ];
    }
}

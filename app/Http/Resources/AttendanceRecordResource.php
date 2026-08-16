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
            'work_date' => $this->work_date?->format('Y-m-d'),
            'clock_in' => $this->clock_in?->format('H:i:s'),
            'clock_out' => $this->clock_out?->format('H:i:s'),

            // ユーザー情報（読み込まれている場合のみ含める）
            'user' => $this->whenLoaded('user', fn() =>[
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),

            //　休憩（読み込まれている場合のみ）
            'breaks' => $this->whenLoaded('breaks', fn() =>
                $this->breaks->map(fn($break) =>[
                    'break_in' => $break->break_in?->format('H:i:s'),
                    'break_out' => $break->break_out?->format('H:i:s'),
                ])
            ),

            // 修正申請（読み込まれている場合のみ）
            'correction' => $this->whenLoaded('correction'),

            'created_at' => $this->created_at,
            'updated_at'=> $this->updated_at,
        ];
    }
}

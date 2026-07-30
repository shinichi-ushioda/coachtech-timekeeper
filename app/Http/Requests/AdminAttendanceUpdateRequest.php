<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminAttendanceUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'clock_in'    => ['required', 'date_format:H:i'],
            'clock_out'   => ['required', 'date_format:H:i'],
            'breaks'      => ['nullable', 'array'],
            'breaks.*.in' => ['nullable', 'date_format:H:i'],
            'breaks.*.out' => ['nullable', 'date_format:H:i'],
            'comment'     => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'comment.required' => '備考を記入してください',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $in  = $this->input('clock_in');
            $out = $this->input('clock_out');

            // 出勤 > 退勤
            if ($in && $out && $in >= $out) {
                $validator->errors()->add('clock_in', '出勤時間もしくは退勤時間が不適切な値です');
            }

            // 休憩のチェック
            foreach ($this->input('breaks', []) as $index => $break) {
                $bIn  = $break['in']  ?? null;
                $bOut = $break['out'] ?? null;

                if (! $bIn && ! $bOut) {
                    continue;
                }

                if ($bIn && $in && $out && ($bIn < $in || $bIn > $out)) {
                    $validator->errors()->add("breaks.$index.in", '休憩時間が不適切な値です');
                }

                if ($bOut && $out && $bOut > $out) {
                    $validator->errors()->add("breaks.$index.out", '休憩時間もしくは退勤時間が不適切な値です');
                }
            }
        });
    }
}

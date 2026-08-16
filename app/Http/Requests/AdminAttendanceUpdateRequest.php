<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class AdminAttendanceUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'clock_in'     => ['required', 'date_format:H:i'],
            'clock_out'    => ['required', 'date_format:H:i'],
            'breaks'       => ['nullable', 'array'],
            'breaks.*.in'  => ['nullable', 'date_format:H:i'],
            'breaks.*.out' => ['nullable', 'date_format:H:i'],
            'comment'      => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * バリデーションメッセージ
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // 備考（FN039-4）
            'comment.required' => '備考を記入してください',

            // 出勤・退勤（未入力・形式）
            'clock_in.required'     => '出勤時間を入力してください',
            'clock_out.required'    => '退勤時間を入力してください',
            'clock_in.date_format'  => '出勤時間の形式が正しくありません',
            'clock_out.date_format' => '退勤時間の形式が正しくありません',

            // 休憩（形式）
            'breaks.*.in.date_format'  => '休憩時間の形式が正しくありません',
            'breaks.*.out.date_format' => '休憩時間の形式が正しくありません',
        ];
    }

    /**
     * 単純なルールでは表現できない「前後関係」のチェックはここで行う
     *
     * @param  Validator  $validator
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
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

                // 休憩開始が出勤前 or 退勤後
                if ($bIn && $in && $out && ($bIn < $in || $bIn > $out)) {
                    $validator->errors()->add("breaks.$index.in", '休憩時間が不適切な値です');
                }

                // 休憩開始が休憩終了より後（逆転）
                if ($bIn && $bOut && $bIn > $bOut) {
                    $validator->errors()->add("breaks.$index.in", '休憩時間が不適切な値です');
                }

                // 休憩終了が退勤後
                if ($bOut && $out && $bOut > $out) {
                    $validator->errors()->add("breaks.$index.out", '休憩時間もしくは退勤時間が不適切な値です');
                }
            }
        });
    }
}

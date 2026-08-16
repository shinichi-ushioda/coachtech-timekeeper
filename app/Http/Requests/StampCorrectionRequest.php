<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StampCorrectionRequest extends FormRequest
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
            'attendance_id'        => ['required'],
            'requested_clock_in'   => ['required', 'date_format:H:i'],
            'requested_clock_out'  => ['required', 'date_format:H:i'],

            // 休憩（既存分）
            'requested_breaks'         => ['nullable', 'array'],
            'requested_breaks.*.in'    => ['nullable', 'date_format:H:i'],
            'requested_breaks.*.out'   => ['nullable', 'date_format:H:i'],

            // 休憩（追加枠）
            'requested_breaks_new.in'  => ['nullable', 'date_format:H:i'],
            'requested_breaks_new.out' => ['nullable', 'date_format:H:i'],

            // 備考
            'reason' => ['required', 'string', 'max:255'],
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
            // FN029-4（採点対象）
            'reason.required' => '備考を記入してください',
            'reason.max'      => '備考は255文字以内で入力してください',

            // 出勤・退勤の必須／形式（FN029に文言指定はないため補助的な文言にしている）
            'requested_clock_in.required'   => '出勤時間を入力してください',
            'requested_clock_out.required'  => '退勤時間を入力してください',
            'requested_clock_in.date_format'  => '出勤時間の形式が正しくありません',
            'requested_clock_out.date_format' => '退勤時間の形式が正しくありません',
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
                 $in  = $this->input('requested_clock_in');
                 $out = $this->input('requested_clock_out');

                 // FN029-1：出勤 > 退勤（＝退勤が出勤より前）は不正
                if ($in && $out && $in >= $out) {
                     $validator->errors()->add(
                        'requested_clock_in',
                        '出勤時間もしくは退勤時間が不適切な値です'
                    );
                }

                // 休憩（既存分）
                foreach ($this->input('requested_breaks', []) as $index => $break) {
                    $this->validateBreak($validator, $break, "requested_breaks.$index", $in, $out);
                }

                // 休憩（追加枠）
                $this->validateBreak(
                     $validator,
                     $this->input('requested_breaks_new', []),
                     'requested_breaks_new',
                     $in,
                     $out
                );
            });
        }

    /**
    * 休憩1件分の前後関係チェック
    * ・"HH:MM" のゼロ埋め文字列は辞書順比較で時刻比較になる（09:00 < 18:00）
    * @param  Validator     $validator
    * @param  array<mixed>  $break
    * @param  string        $key
    * @param  string|null   $in
    * @param  string|null   $out
    * @return void
    */
        private function validateBreak(Validator $validator, array $break, string $key, ?string $in, ?string $out): void
        {
             $breakIn  = $break['in']  ?? null;
             $breakOut = $break['out'] ?? null;

             // 両方空なら未入力の枠とみなしてスキップ
             if (!$breakIn && !$breakOut) {
                return;
            }

            // FN029-2：休憩開始が出勤前 or 退勤後 → 「休憩時間が不適切な値です」
             if ($breakIn && $in && $out && ($breakIn < $in || $breakIn > $out)) {
                $validator->errors()->add("$key.in", '休憩時間が不適切な値です');
            }

            // ★ 休憩開始が休憩終了より後（逆転）→「休憩時間が不適切な値です」
            if ($breakIn && $breakOut && $breakIn > $breakOut) {
                $validator->errors()->add("$key.in", '休憩時間が不適切な値です');
            }

            // FN029-3：休憩終了が退勤後 → 「休憩時間もしくは退勤時間が不適切な値です」
            if ($breakOut && $out && $breakOut > $out) {
                $validator->errors()->add("$key.out", '休憩時間もしくは退勤時間が不適切な値です');
            }
        }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceCorrectionRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'new_clock_in' => [
                'required',
                'date_format:H:i:s',
                'before:new_clock_out',
            ],

            'new_clock_out' => [
                'required',
                'date_format:H:i:s',
                'after:new_clock_in',
            ],

            'new_break_in.*' => [
                'nullable',
                'date_format:H:i:s',
            ],

            'new_break_out.*' => [
                'nullable',
                'date_format:H:i:s',
            ],

            'comment' => [
                'required',
            ],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $clockIn = $this->new_clock_in;
            $clockOut = $this->new_clock_out;

            foreach ($this->new_break_in ?? [] as $index => $breakIn) {

                $breakOut =
                    $this->new_break_out[$index] ?? null;

                if (
                    empty($breakIn)
                    ||
                    empty($breakOut)
                ) {
                    continue;
                }

                // 休憩開始 < 休憩終了
                if (
                    strtotime($breakIn)
                    >=
                    strtotime($breakOut)
                ) {

                    $validator->errors()->add(
                        'new_break_in.' . $index,
                        '休憩時間が不適切な値です'
                    );
                }

                // 休憩開始 >= 出勤
                if (
                    strtotime($breakIn)
                    <
                    strtotime($clockIn)
                ) {

                    $validator->errors()->add(
                        'new_break_in.' . $index,
                        '休憩時間が不適切な値です'
                    );
                }

                // 休憩開始 < 退勤
                if (
                    strtotime($breakIn)
                    >
                    strtotime($clockOut)
                ) {

                    $validator->errors()->add(
                        'new_break_in.' . $index,
                        '休憩時間が不適切な値です'
                    );
                }

                // 休憩終了 < 退勤
                if (
                    strtotime($breakOut)
                    >
                    strtotime($clockOut)
                ) {

                    $validator->errors()->add(
                        'new_break_out.' . $index,
                        '休憩時間もしくは退勤時間が不適切な値です'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [

            'new_clock_in.required'
            => '出勤時間を入力してください',

            'new_clock_in.date_format'
            => '出勤時間をHH:MM:SS形式で入力してください',

            'new_clock_out.required'
            => '退勤時間を入力してください',

            'new_clock_out.date_format'
            => '退勤時間をHH:MM:SS形式で入力してください',

            'new_break_in.*.date_format'
            => '休憩開始時間をHH:MM:SS形式で入力してください',

            'new_break_out.*.date_format'
            => '休憩終了時間をHH:MM:SS形式で入力してください',

            'comment.required'
            => '備考を記入してください',

            'new_clock_in.before'
            => '出勤時間もしくは退勤時間が不適切な値です',

            'new_clock_out.after'
            => '出勤時間もしくは退勤時間が不適切な値です',
            
        ];
    }

    protected function prepareForValidation()
    {
        $breakIns = [];

        foreach ($this->new_break_in ?? [] as $value) {
            $breakIns[] = mb_convert_kana($value, 'as');
        }

        $breakOuts = [];

        foreach ($this->new_break_out ?? [] as $value) {
            $breakOuts[] = mb_convert_kana($value, 'as');
        }

        $this->merge([

            'new_clock_in' => mb_convert_kana(
                $this->new_clock_in,
                'as'
            ),

            'new_clock_out' => mb_convert_kana(
                $this->new_clock_out,
                'as'
            ),

            'new_break_in' => $breakIns,

            'new_break_out' => $breakOuts,
        ]);
    }
}

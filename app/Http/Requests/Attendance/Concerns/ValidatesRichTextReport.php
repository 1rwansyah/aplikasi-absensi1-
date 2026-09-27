<?php

namespace App\Http\Requests\Attendance\Concerns;

use App\Support\RichTextSanitizer;
use Illuminate\Validation\Validator;

trait ValidatesRichTextReport
{
    protected function validateRichTextReport(
        Validator $validator,
        string $field,
        string $minMessage,
        int $minLength = 10,
    ): void
    {
        $validator->after(function (Validator $validator) use ($field, $minMessage, $minLength) {
            if ($validator->errors()->has($field)) {
                return;
            }

            $plain = RichTextSanitizer::toPlainText($this->input($field));

            if (mb_strlen($plain ?? '') < $minLength) {
                $validator->errors()->add($field, $minMessage);
            }
        });
    }
}

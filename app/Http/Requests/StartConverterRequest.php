<?php

namespace App\Http\Requests;

use App\Rules\VideoRule;
use Illuminate\Foundation\Http\FormRequest;

class StartConverterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['file', 'bail', new VideoRule, 'nullable'],
            'url' => 'string|url|nullable|bail',
            // 'keepResolution' => 'required|boolean',
            'audio' => 'required|boolean',
            'audioQuality' => 'required|numeric|min:0,01|max:1',
            'trimStart' => 'nullable|numeric|min:1',
            'trimEnd' => 'nullable|numeric|min:1',
            'maxSize' => 'nullable|numeric|min:10|max:500',
            'autoCrop' => 'nullable|boolean',
            'watermark' => 'nullable|boolean',
            // 'interpolation' => 'nullable|boolean',
        ];
    }
}

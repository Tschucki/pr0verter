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
            'trimStart' => 'nullable|string',
            'trimEnd' => 'nullable|string',
            'maxSize' => 'nullable|numeric|min:10|max:500',
            'segments' => 'nullable|array',
            'segments.*.start' => 'required|numeric|min:0',
            'segments.*.duration' => 'nullable|numeric|min:1',
            'autoCrop' => 'nullable|boolean',
            'watermark' => 'nullable|boolean',
            'audio_only' => 'nullable|boolean',
            // 'interpolation' => 'nullable|boolean',
        ];
    }
}

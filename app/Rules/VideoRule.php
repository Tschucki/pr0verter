<?php

namespace App\Rules;

use Closure;
use FFMpeg\Exception\ExecutableNotFoundException;
use FFMpeg\FFProbe;
use Illuminate\Contracts\Validation\ValidationRule;
use Throwable;

class VideoRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            app(FFProbe::class)->format($value)->get('duration');
        } catch (ExecutableNotFoundException $e) {
            $fail('Konnte nicht testen ob die Datei ein Video ist.');
        } catch (Throwable $t) {
            $fail('Die Datei muss ein Video sein.');
        }
    }
}

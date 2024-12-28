<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class LegalNoticeController extends Controller
{
    public function __invoke(Request $request)
    {
        $markdown = file_get_contents(base_path('resources/markdown/LegalNotice.md'));

        $content = Str::markdown($markdown);

        return Inertia::render('LegalNotice', [
            'content' => $content,
        ]);
    }
}

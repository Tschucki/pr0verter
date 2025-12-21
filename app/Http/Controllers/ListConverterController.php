<?php

namespace App\Http\Controllers;

use App\Models\Conversion;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Inertia\Response;

class ListConverterController extends Controller
{
    public function index(): Response
    {
        $conversions = Conversion::with('file')->where('session_id', Session::getId())->get();

        return Inertia::render('Converter/List', [
            'conversions' => $conversions->toArray(),
        ]);
    }

    public function myConversions(): array
    {
        $conversions = Conversion::with('file')->where('session_id', Session::getId())->get();

        return $conversions->toArray();
    }
}

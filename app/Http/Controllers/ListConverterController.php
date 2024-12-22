<?php

namespace App\Http\Controllers;

use App\Models\Conversion;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ListConverterController extends Controller
{
    private string $sessionId;

    public function __construct(Request $request)
    {
        $this->sessionId = $request->session()->getId();
    }

    public function index(Request $request): Response
    {
        $conversions = Conversion::with('file')->whereHas('file', function ($query) {
            $query->where('session_id', $this->sessionId);
        })->get();

        return Inertia::render('Converter/List', [
            'conversions' => $conversions,
        ]);
    }

    public function myConversions(Request $request): array
    {
        $conversions = Conversion::with('file')->whereHas('file', function ($query) {
            $query->where('session_id', $this->sessionId);
        })->get();

        return $conversions->toArray();
    }
}

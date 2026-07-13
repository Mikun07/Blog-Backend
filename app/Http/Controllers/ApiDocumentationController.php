<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class ApiDocumentationController extends Controller
{
    public function index()
    {
        return view('api-docs', [
            'specUrl' => route('api.docs.openapi', [], false),
        ]);
    }

    public function openApi(): JsonResponse
    {
        $path = base_path('docs/openapi.json');
        $spec = json_decode(file_get_contents($path), true);

        abort_if(! is_array($spec), 500, 'OpenAPI documentation is not valid JSON.');

        return response()
            ->json($spec)
            ->header('Cache-Control', 'no-store');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\CapturedEmail;
use Illuminate\Http\Request;

class CaptureEmailAddressController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'source' => 'required|string',
        ]);

        CapturedEmail::firstOrCreate([
            'email' => $request->email,
            'source' => $request->source,
            'domain' => $this->getDomain($request->source),
        ]);

        return response(['status' => 'ok']);
    }

    private function getDomain(string $url): string
    {
        return parse_url($url, PHP_URL_HOST);
    }
}

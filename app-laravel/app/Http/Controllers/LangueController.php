<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class LangueController extends Controller
{
    /** Les seules langues servies par le site. */
    public const LANGUES = ['fr', 'en'];

    public function basculer(string $code): RedirectResponse
    {
        abort_unless(in_array($code, self::LANGUES, true), 404);

        session(['langue' => $code]);

        return back();
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\Store\StoreFormRequest;
use App\Support\DemoData;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class StoreController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('stores/Index', [
            'stores' => DemoData::stores(),
        ]);
    }

    public function store(StoreFormRequest $request): RedirectResponse
    {
        return back()->with('success', 'Toko tersimpan (demo — belum masuk database).');
    }
}

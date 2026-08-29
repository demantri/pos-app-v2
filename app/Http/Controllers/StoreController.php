<?php

namespace App\Http\Controllers;

use App\Http\Requests\Store\StoreFormRequest;
use App\Models\Store;
use App\Models\User;
use App\Support\StoreData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StoreController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $viewer Rute ini selalu di bawah middleware auth. */
        $viewer = $request->user();

        return Inertia::render('stores/Index', [
            'stores' => StoreData::stores($viewer),
        ]);
    }

    public function store(StoreFormRequest $request): RedirectResponse
    {
        $store = Store::create([
            ...$request->validated(),
            // Setting toko memakai default kolom (lihat migration stores);
            // header struk diisi nama toko supaya tidak kosong sejak awal.
            'receipt_header' => $request->validated('name'),
        ]);

        return back()->with('success', "Toko {$store->name} tersimpan.");
    }
}

<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\SettingRequest;
use App\Support\DemoData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function edit(Request $request): Response
    {
        $store = $request->attributes->get('store');

        return Inertia::render('stores/settings/Edit', [
            'settings' => DemoData::settings($store['id']),
        ]);
    }

    public function update(SettingRequest $request): RedirectResponse
    {
        return back()->with('success', 'Setting toko tersimpan (demo — belum masuk database).');
    }
}

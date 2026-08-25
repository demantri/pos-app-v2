<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Support\DemoData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(Request $request): Response
    {
        $store = $request->attributes->get('store');

        return Inertia::render('stores/transactions/Index', [
            'transactions' => DemoData::transactions($store['id']),
        ]);
    }
}

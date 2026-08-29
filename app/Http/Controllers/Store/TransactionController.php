<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Support\StoreData;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function index(Store $store): Response
    {
        return Inertia::render('stores/transactions/Index', [
            'transactions' => StoreData::transactions($store),
        ]);
    }
}

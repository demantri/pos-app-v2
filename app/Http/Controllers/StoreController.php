<?php

namespace App\Http\Controllers;

use App\Support\DemoData;
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
}

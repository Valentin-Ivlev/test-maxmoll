<?php

namespace App\Http\Controllers;

use App\Models\Product;

use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Получить список товаров с остатками по складам
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function index()
    {
        $products = Product::with('stocks')->get();

        return response()->json($products);
    }
}

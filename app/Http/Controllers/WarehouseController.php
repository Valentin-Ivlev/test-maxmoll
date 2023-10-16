<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;

use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    /**
     * Получить список складов
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function index()
    {
        $warehouses = Warehouse::all();

        return response()->json($warehouses);
    }
}

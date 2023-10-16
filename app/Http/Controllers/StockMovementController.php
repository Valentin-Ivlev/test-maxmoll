<?php

namespace App\Http\Controllers;

use App\Models\StockMovement;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    /**
     * Просмотреть историю движений товаров с фильтрами и пагинацией.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Извлечение параметров запроса
        $perPage = $request->input('per_page', 10);

        // Построение запроса для получения истории движений с фильтрами
        $query = StockMovement::query()
            ->with('product', 'warehouse')
            ->when($request->has('product_id'), function ($query) use ($request) {
                // Применение фильтра по product_id
                $query->where('product_id', $request->input('product_id'));
            })
            ->when($request->has('warehouse_id'), function ($query) use ($request) {
                // Применение фильтра по warehouse_id
                $query->where('warehouse_id', $request->input('warehouse_id'));
            })
            ->when($request->has('start_date'), function ($query) use ($request) {
                // Применение фильтра по дате начала движения
                $query->whereDate('created_at', '>=', $request->input('start_date'));
            })
            ->when($request->has('end_date'), function ($query) use ($request) {
                // Применение фильтра по дате окончания движения
                $query->whereDate('created_at', '<=', $request->input('end_date'));
            });

        // Пагинация
        $movements = $query->paginate($perPage);

        return response()->json($movements);
    }

    /**
     * Запись движения товаров в историю.
     *
     * @param  int  $product_id
     * @param  int  $warehouse_id
     * @param  int  $count
     * @param  string  $movementType
     * @return void
     */
    public function recordStockMovement($product_id, $warehouse_id, $count, $movementType)
    {
        $stockMovement = new StockMovement([
            'product_id' => $product_id,
            'warehouse_id' => $warehouse_id,
            'count' => $count,
            'movement_type' => $movementType,
            'created_at' => now(),
        ]);

        $stockMovement->save();
    }
}

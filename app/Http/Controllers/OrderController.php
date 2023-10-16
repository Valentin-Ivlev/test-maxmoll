<?php

namespace App\Http\Controllers;
use App\Http\Controllers\StockMovementController;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Stock;
use App\Models\StockMovement;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{

    protected $stockMovementController;

    public function __construct(StockMovementController $stockMovementController)
    {
        $this->stockMovementController = $stockMovementController;
    }

    /**
     * Списать товары из остатков.
     *
     * @param  \App\Models\Order  $order
     * @return bool true, если списание прошло успешно, иначе false
     */
    private function decreaseStocks(Order $order)
    {
        $success = true; // Переменная для отслеживания успешного списания

        foreach ($order->orderItems as $orderItem) {
            $product = $orderItem->product;

            // Проверка остатков товаров на складе
            $stock = Stock::where('product_id', $product->id)
                ->where('warehouse_id', $order->warehouse_id)
                ->first();

            if ($stock && $stock->stock >= $orderItem->count) {
                // Списание товаров
                $stock->stock -= $orderItem->count;
                $stock->save();

                $this->stockMovementController->recordStockMovement(
                    $orderItem->product_id,
                    $order->warehouse_id,
                    $orderItem->count,
                    'outgoing'
                );

            } else {
                // Если товаров недостаточно, устанавливаем флаг ошибки и завершаем цикл
                $success = false;
                break;
            }
        }

        return $success;
    }

    /**
     * Вернуть товары на склад.
     *
     * @param  \App\Models\Order  $order
     * @return void
     */
    private function increaseStocks(Order $order)
    {
        foreach ($order->orderItems as $orderItem) {
            $product = $orderItem->product;

            // Поиск остатков товаров на складе
            $stock = Stock::where('product_id', $product->id)
                ->where('warehouse_id', $order->warehouse_id)
                ->first();

            if ($stock) {
                // Возврат товаров на склад
                $stock->stock += $orderItem->count;
                $stock->save();

                $this->stockMovementController->recordStockMovement(
                    $orderItem->product_id,
                    $order->warehouse_id,
                    $orderItem->count,
                    'incoming'
                );
            }
        }
    }

    /**
     * Получить список заказов с фильтрами и пагинацией.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Извлечение параметров запроса
        $filters = $request->input('filters', []);
        $perPage = $request->input('per_page', 10);

        // Построение запроса для получения списка заказов с фильтрами
        $query = Order::query()
            ->with('orderItems.product', 'warehouse')
            ->when($filters, function ($query) use ($filters) {
                // Применение фильтров к запросу
                if (isset($filters['status'])) {
                    $query->where('status', $filters['status']);
                }

                if (isset($filters['customer'])) {
                    $query->where('customer', 'like', '%'.$filters['customer'].'%');
                }

                if (isset($filters['warehouse_id'])) {
                    $query->where('warehouse_id', $filters['warehouse_id']);
                }

                // Фильтр по продукту
                if (isset($filters['product'])) {
                    $query->whereHas('orderItems.product', function ($q) use ($filters) {
                        $q->where('name', 'like', '%'.$filters['product'].'%');
                    });
                }
            });

        // Пагинация
        $orders = $query->paginate($perPage);

        return response()->json($orders);
    }

    /**
     * Создать заказ (в заказе может быть несколько позиций с разным количеством).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Создание заказа
        $order = Order::create([
            'customer' => $request->input('customer'),
            'created_at' => now(),
            'completed_at' => null,
            'warehouse_id' => $request->input('warehouse_id'),
            'status' => 'active',
        ]);

        // Добавление позиций к заказу
        foreach ($request->input('items') as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'count' => $item['count'],
            ]);
        }

        return response()->json(['message' => 'Заказ создан успешно.']);
    }

    /**
     * Обновить заказ (данные покупателя и список позиций, но не статус).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Order $order)
    {
        // Обновление данных заказа
        $order->customer = $request->input('customer');
        $order->warehouse_id = $request->input('warehouse_id');
        $order->save();

        // Обновление позиций заказа
        $order->orderItems()->delete();
        foreach ($request->input('items') as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'count' => $item['count'],
            ]);
        }

        return response()->json(['message' => 'Заказ обновлен успешно.']);
    }

    /**
     * Завершить заказ.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\JsonResponse
     */
    public function complete(Order $order)
    {
        // Списание товаров
        $success = $this->decreaseStocks($order);

        if ($success) {
            // Завершение заказа
            $order->status = 'completed';
            $order->completed_at = now();
            $order->save();

            return response()->json(['message' => 'Заказ завершен успешно.']);
        } else {
            return response()->json(['error' => 'Товаров на складе недостаточно для списания.'], 400);
        }
    }

    /**
     * Отменить заказ.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel(Order $order)
    {
        // Возврат товаров на склад
        $this->increaseStocks($order);

        // Отмена заказа
        $order->status = 'canceled';
        $order->save();

        return response()->json(['message' => 'Заказ отменен успешно.']);
    }

    /**
     * Возобновить заказ (перевод из отмены в работу).
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\JsonResponse
     */
    public function resume(Order $order)
    {
        // Списание товаров
        $success = $this->decreaseStocks($order);

        if ($success) {
            // Возобновление заказа
            $order->status = 'active';
            $order->save();

            return response()->json(['message' => 'Заказ возобновлен успешно.']);
        } else {
            return response()->json(['error' => 'Товаров на складе недостаточно для списания.'], 400);
        }
    }
}

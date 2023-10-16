<?php

namespace Database\Seeders;

use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Stock;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ComplexSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {

        $warehouse = Warehouse::factory()->count(3)->create();

        foreach ($warehouse as $wh) {

            for ($i = 0; $i < 5; $i++) {

                $order = Order::factory()->create([
                    'warehouse_id' => $wh->id
                ]);

                for ($j = 0; $j < rand(2, 5); $j++) {

                    $product = Product::factory()->create();

                    $stock = Stock::factory()->create([
                        'product_id' => $product->id,
                        'warehouse_id' => $wh->id
                    ]);

                    $order_item = OrderItem::factory()->create([
                        'order_id' => $order->id,
                        'product_id' => $product->id
                    ]);

                    if ($order->status !== 'active') {

                        StockMovement::factory()->create([
                            'product_id' => $product->id,
                            'warehouse_id' => $wh->id,
                            'count' => $order_item->count,
                            'movement_type' => $order->status === 'completed' ? 'outgoing' : 'incoming',
                            'created_at' => $order->status === 'completed' ? $order->completed_at : $order->created_at
                        ]);

                    }

                }

            }

        }

    }

}

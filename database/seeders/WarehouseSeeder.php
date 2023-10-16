<?php

use Illuminate\Database\Seeder;
use App\Models\Warehouse;
use Database\Factories\WarehouseFactory;

class WarehouseSeeder extends Seeder
{
    public function run()
    {
        Warehouse::factory()->count(3)->create();
    }
}

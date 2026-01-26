<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Market;
use App\Models\MarketStock;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(1)->create();

        // User::factory()->create([
        //     'name' => 'Agent User',
        //     'login' => 'agent1',
        // ]);

        Group::factory(5)->create();

        // Создадим 20 торговых точек (Market)
        // Каждая точка автоматически создаст свою группу благодаря фабрике MarketFactory
        Market::factory(20)->create();

        // Создадим 50 продуктов
        Product::factory(50)->create();


        // 2. Создаем связи и зависимые данные

        // Заполняем остатки продуктов на складах (ProductStock)
        // Создадим 200 записей остатков.
        // Каждая запись автоматически создаст и привяжет случайный Продукт и случайный Маркет.
        ProductStock::factory(200)->create();

        // Создаем данные по складским запасам (MarketStock)
        MarketStock::factory(50)->create();
        
        // Создаем историю визитов (Visit)
        Visit::factory(100)->create();
    }
}

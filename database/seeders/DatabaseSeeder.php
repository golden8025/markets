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

        // $user1 = User::factory()->create([
        //     'name' => 'Javohir',
        //     'login' => 'admin',
        //     'role' => 'admin',
        // ]);

        // $user2 = User::factory()->create([
        //     'name' => 'admin User',
        //     'login' => 'admin',
        //     'role' => 'admin',
        // ]);

        
        // $group = Group::create(['name' => 'Gurux 1']);


        // // 3. Создаем продукты
        // $product1 = Product::create(['name' => 'Coca-cola 0.5', 'price' => 50.00]);
        // $product2 = Product::create(['name' => 'pepsi 0.5', 'price' => 45.00]);
        // $product3 = Product::create(['name' => 'Chips Lays', 'price' => 120.00]);

        // // 4. Создаем маркеты
        // $market1 = Market::create([
        //     'group_id' => $group->id,
        //     'name' => 'Market1',
        //     'key' => 'm_center_01',
        //     'type' => 'metan',
        //     'latitude' => 41.2995,
        //     'longitude' => 69.2401,
        // ]);

        // $market2 = Market::create([
        //     'group_id' => $group->id,
        //     'name' => 'Market 2',
        //     'key' => 'm_suburb_02',
        //     'type' => 'propan',
        //     'latitude' => 41.3111,
        //     'longitude' => 69.2797,
        // ]);

        // 5. Заполняем остатки (ProductStock)
        // ProductStock::create([
        //     'market_id' => $market1->id,
        //     'product_id' => $product1->id,
        //     'qty' => 100
        // ]);

        // ProductStock::create([
        //     'market_id' => $market1->id,
        //     'product_id' => $product2->id,
        //     'qty' => 50
        // ]);

        // ProductStock::create([
        //     'market_id' => $market2->id,
        //     'product_id' => $product3->id,
        //     'qty' => 30
        // ]);

        // 6. Привязываем пользователей к маркетам (Связь Многие-ко-многим)
        // Привяжем первых 5 юзеров к первому маркету
        // $market1->users()->attach([$user1->id, $user2->id]);

        // Привяжем 3-го и 6-го юзера ко второму маркету (демонстрация пересечения)
        // $market2->users()->attach([$user1->id, $user2->id]);
        // Group::factory(5)->create();

        // // Создадим 20 торговых точек (Market)
        // // Каждая точка автоматически создаст свою группу благодаря фабрике MarketFactory
        // Market::factory(20)->create();

        // // Создадим 50 продуктов
        // Product::factory(50)->create();


        // // 2. Создаем связи и зависимые данные

        // // Заполняем остатки продуктов на складах (ProductStock)
        // // Создадим 200 записей остатков.
        // // Каждая запись автоматически создаст и привяжет случайный Продукт и случайный Маркет.
        // ProductStock::factory(200)->create();

        // // Создаем данные по складским запасам (MarketStock)
        // MarketStock::factory(50)->create();
        
        // // Создаем историю визитов (Visit)
        // Visit::factory(100)->create();
    }
}

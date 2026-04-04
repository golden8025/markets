<?php

namespace App\Http\Controllers;

use App\Models\ProductStock;
use App\Models\Visit;
use App\Models\VisitInfo;
use App\Models\VisitImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Requests\StoreVisitRequest;
use App\Models\Group;
use App\Models\Market;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class VisitController extends Controller
{




// public function index(Request $request)
// {
//     $user = Auth::user();
//     // 1. Формируем запрос с фильтрами
//     $visits = Visit::query()
//         // Важно: подгружаем info.product для расчетов, чтобы не было N+1
//         ->with(['market.group', 'info.product', 'user'])
//         ->when($user->role === 'agent', function ($query) use ($user) {
//             // Если агент — принудительно фильтруем только его визиты
//             return $query->where('user_id', $user->id);
//         }, function ($query) use ($request) {
//             // Если НЕ агент (админ), разрешаем фильтрацию по выбранному user_id из запроса
//             return $query->when($request->user_id, function ($q, $userId) {
//                 $q->where('user_id', $userId);
//             });
//         })
        
//         // Фильтр по дате "ОТ"
//         ->when($request->date_from, function ($query, $date) {
//             $query->whereDate('created_at', '>=', $date);
//         })
//         // Фильтр по дате "ДО"
//         ->when($request->date_to, function ($query, $date) {
//             $query->whereDate('created_at', '<=', $date);
//         })
//         // Фильтр по пользователю
//         ->when($request->user_id, function ($query, $userId) {
//             $query->where('user_id', $userId);
//         })
//         // Фильтр по магазину
//         ->when($request->market_id, function ($query, $marketId) {
//             $query->where('market_id', $marketId);
//         })
//         // Фильтр по группе магазинов
//         ->when($request->group_id, function ($query, $groupId) {
//         $query->whereHas('market', function ($q) use ($groupId) {
//         $q->where('group_id', $groupId);
//     });
//         })
//         ->orderBy('created_at', 'desc')
//         ->get();

//     // 2. Трансформируем результат (ваша логика расчетов)
//     $data = $visits->map(function ($visit) {
//         $totals = $visit->info->reduce(function ($carry, $info) {
//             $sold = $info->loaded - $info->left;
//             $expected = $sold * ($info->product->price ?? 0);
            
//             // Считаем "минус"
//             $minus = max(0, $expected - $info->profit);

//             $carry['sold'] += $sold;
//             $carry['profit'] += $info->profit;
//             $carry['minus'] += $minus;
            
//             return $carry;
//         }, ['sold' => 0, 'profit' => 0, 'minus' => 0]);

//         return [
//             'id'             => $visit->id,
//             'market_name'    => $visit->market->name ?? 'B/N',
//             'visit_time'     => $visit->created_at->format('d.m.Y H:i'),
//             'total_sold'     => $totals['sold'],
//             'total_profit'   => $totals['profit'],
//             'total_minus'    => $totals['minus'],
//             'short_comment'  => Str::words($visit->comment, 3, '...'),
//         ];
//     });

//     return response()->json($data);
// }


    public function index(Request $request)
    {
        $user = Auth::user();

        // 1. Формируем запрос с фильтрами
        $visits = Visit::query()
            // Подгружаем info.product для получения цены товара
            ->with(['market.group', 'info.product', 'user'])
            // Логика доступа: агент видит только своё, админ — всех
            ->when($user->role === 'agent', function ($query) use ($user) {
                return $query->where('user_id', $user->id);
            }, function ($query) use ($request) {
                return $query->when($request->user_id, function ($q, $userId) {
                    $q->where('user_id', $userId);
                });
            })
            // Фильтры по датам и сущностям
            ->when($request->date_from, function ($query, $date) {
                $query->whereDate('created_at', '>=', $date);
            })
            ->when($request->date_to, function ($query, $date) {
                $query->whereDate('created_at', '<=', $date);
            })
            ->when($request->market_id, function ($query, $marketId) {
                $query->where('market_id', $marketId);
            })
            ->when($request->group_id, function ($query, $groupId) {
                $query->whereHas('market', function ($q) use ($groupId) {
                    $q->where('group_id', $groupId);
                });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        // 2. Трансформируем результат
        $data = $visits->map(function ($visit) {
            $totals = $visit->info->reduce(function ($carry, $info) {
                // ТЕПЕРЬ БЕРЕМ ГОТОВОЕ ПОЛЕ ИЗ БАЗЫ
                $soldQty = $info->sold ?? 0; 
                
                // Расчет ожидаемой суммы (сколько должны были сдать денег за проданный товар)
                $expectedMoney = $soldQty * ($info->product->price ?? 0);
                
                // Расчет "минуса" (дебиторки): разница между тем что должны и тем что принес агент
                // Если принес больше чем продал (бывает при возврате долга), минус будет 0
                $minus = max(0, $expectedMoney - $info->profit);

                $carry['sold_qty'] += $soldQty;
                $carry['profit']   += $info->profit;
                $carry['minus']    += $minus;
                
                return $carry;
            }, ['sold_qty' => 0, 'profit' => 0, 'minus' => 0]);

            return [
                'id'              => $visit->id,
                'market_name'     => $visit->market->name ?? 'B/N',
                'agent_name'      => $visit->user->name ?? 'B/N', // Полезно для админа
                'visit_time'      => $visit->created_at->format('d.m.Y H:i'),
                'total_sold_qty'  => $totals['sold_qty'], // Количество штук
                'total_profit'    => $totals['profit'],   // Касса (сум)
                'total_minus'     => $totals['minus'],    // Долг (сум)
                'short_comment'   => \Illuminate\Support\Str::words($visit->comment, 3, '...'),
            ];
        });

        return response()->json($data);
    }

// public function index(Request $request)
// {
//     $user = Auth::user();

//     $data = Visit::query()
//         // Используем select для ограничения выбираемых колонок (экономия памяти)
//         ->select('id', 'market_id', 'user_id', 'created_at', 'comment')
//         // Eager loading только необходимых полей из связей
//         ->with([
//             'market:id,name,group_id', 
//             'market.group:id,name', 
//             'info:id,visit_id,product_id,loaded,left,profit', 
//             'info.product:id,price', 
//             'user:id,name'
//         ])
//         // Объединяем логику прав доступа и фильтрации пользователей
//         ->when($user->role === 'agent', function ($query) use ($user) {
//             $query->where('user_id', $user->id);
//         }, function ($query) use ($request) {
//             $query->when($request->user_id, fn($q, $id) => $q->where('user_id', $id));
//         })
//         // Фильтры по датам
//         ->when($request->date_from, fn($q, $date) => $q->whereDate('created_at', '>=', $date))
//         ->when($request->date_to, fn($q, $date) => $q->whereDate('created_at', '<=', $date))
//         // Фильтр по магазину
//         ->when($request->market_id, fn($q, $id) => $q->where('market_id', $id))
//         // Фильтр по группе (оптимизируем через whereIn, если это возможно, но whereHas тоже ок)
//         ->when($request->group_id, function ($query, $groupId) {
//             $query->whereHas('market', fn($q) => $q->where('group_id', $groupId));
//         })
//         ->orderBy('created_at', 'desc')
//         // Рекомендую использовать paginate(20) вместо get() для мобильного приложения
//         ->paginate($request->per_page ?? 20);

//     // Трансформируем коллекцию (в пагинации данные лежат в items())
//     $transformedItems = $data->getCollection()->map(function ($visit) {
//         $totals = $visit->info->reduce(function ($carry, $info) {
//             $sold = $info->loaded - $info->left;
//             $price = $info->product->price ?? 0;
            
//             // Расчет "минуса"
//             $minus = max(0, ($sold * $price) - $info->profit);

//             $carry['sold'] += $sold;
//             $carry['profit'] += $info->profit;
//             $carry['minus'] += $minus;
            
//             return $carry;
//         }, ['sold' => 0, 'profit' => 0, 'minus' => 0]);

//         return [
//             'id'             => $visit->id,
//             'market_name'    => $visit->market->name ?? 'Н/Д',
//             'visit_time'     => $visit->created_at->format('d.m.Y H:i'),
//             'total_sold'     => (int)$totals['sold'],
//             'total_profit'   => (int)$totals['profit'],
//             'total_minus'    => (int)$totals['minus'],
//             'short_comment'  => Str::words($visit->comment, 3, '...'),
//             'user_name'      => $visit->user->name ?? '',
//         ];
//     });

//     // Заменяем коллекцию в объекте пагинации на трансформированную
//     $data->setCollection($transformedItems);

//     return response()->json($data);
// }

    public function getFilterData() {
        return response()->json([
            'users' => User::all(['id', 'name']),
            'groups' => Group::all(['id', 'name']),
            'markets' => Market::all(['id', 'name', 'group_id']),
        ]);
    }


    // public function store(StoreVisitRequest $request)
    // {
    //     // $id = Auth::user()->id;
    //     return DB::transaction(function () use ($request) {
            
    //         $visit = Visit::create([
    //             'user_id'   => Auth::user()->id, //auth()->id(), 
    //             'market_id' => $request->market_id,
    //             'comment'   => $request->comment,
    //         ]);

    //         // 2. Добавляем информацию о товарах (info)

    //         if ($request->has('products')) {
    //             foreach ($request->products as $item) {
    //                 $qty = ProductStock::where('product_id', $item['id'])
    //                     ->where('market_id', $request->market_id)
    //                     ->value('qty');
    //                 VisitInfo::create([
    //                     'visit_id'   => $visit->id,
    //                     'product_id' => $item['id'],
    //                     'loaded'     => $item['loaded'],
    //                     'left'       => $item['left'],
    //                     'profit'     => $item['profit'],
    //                     'sold'       => ($qty - $item['left']),
    //                 ]);
                    
                   
    //                 ProductStock::updateOrCreate(
    //                 [
    //                     'market_id'  => $request->market_id,
    //                     'product_id' => $item['id'],
    //                 ],
    //                 [
    //                     'qty' => $item['left'] + $item['loaded']
    //                 ]
    //             );
    //             }
    //         }

    //         // 3. Сохраняем изображения (visit_images)
    //         if ($request->hasFile('images')) {
    //             foreach ($request->file('images') as $image) {
    //                 $path = $image->store('visits', 'public');
                    
    //                 VisitImage::create([
    //                     'visit_id' => $visit->id,
    //                     'image'    => $path,
    //                 ]);
    //             }
    //         }

    //         return response()->json(['message' => 'Yaratildi!', 'visit' => $visit], 201);
    //     });
    // }

    public function store(StoreVisitRequest $request)
    {
        return DB::transaction(function () use ($request) {
            try{
            $visit = Visit::create([
                'user_id'   => Auth::id(), 
                'market_id' => $request->market_id,
                'comment'   => $request->comment,
            ]);

            if ($request->has('products')) {
                foreach ($request->products as $item) {
                    // 1. Получаем текущий остаток (или 0, если товара еще нет в этом магазине)
                    $currentStock = ProductStock::where('product_id', $item['id'])
                        ->where('market_id', $request->market_id)
                        ->first();

                    $oldQty = $currentStock ? $currentStock->qty : 0;

                    // 2. Считаем проданное количество
                    // Если остаток в магазине ($item['left']) больше, чем было (ошибка ввода), запишем 0
                    $soldCount = $oldQty - $item['left'];

                    VisitInfo::create([
                        'visit_id'   => $visit->id,
                        'product_id' => $item['id'],
                        'loaded'     => $item['loaded'],
                        'left'       => $item['left'],
                        'profit'     => $item['profit'],
                        'sold'       => $soldCount,
                    ]);

                    // 3. Обновляем склад: то что осталось + новый завоз
                    ProductStock::updateOrCreate(
                        [
                            'market_id'  => $request->market_id,
                            'product_id' => $item['id'],
                        ],
                        [
                            'qty' => $item['left'] + $item['loaded']
                        ]
                    );
                }
            }

            // 4. Сохраняем изображения
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('visits', 'public');
                    
                    VisitImage::create([
                        'visit_id' => $visit->id,
                        'image'    => $path,
                    ]);
                }
            }
            } catch(\Exception $e) {
        // Записываем ошибку в storage/logs/laravel.log
        Log::error('Visit Store Error: ' . $e->getMessage(), [
            'user_id' => Auth::id(),
            'payload' => $request->all(),
            'trace'   => $e->getTraceAsString()
        ]);
            }
            return response()->json([
                'message' => 'Muvaffaqiyatli saqlandi!', 
                'visit' => $visit->load('infos')
            ], 201);
        });
    
    }

    /**
     * Display the specified resource.
     */
    // public function show(string $id)
    // {
    //     // Загружаем визит со всеми вложенными связями
    //     $visit = Visit::with(['market', 'info.product', 'images'])->findOrFail($id);

    //     $report = $visit->info->map(function ($info) {
    //         // 1. Считаем сколько реально продано штук
    //         $soldCount = $info->loaded - $info->left;
            
    //         // 2. Ожидаемая сумма по прайсу
    //         $expectedAmount = $soldCount * ($info->product->price ?? 0);
            
    //         // 3. Расчет "минуса" (недостачи)
    //         // Если прибыль больше ожидаемого, минус будет 0
    //         $minus = max(0, $expectedAmount - $info->profit);

    //         return [
    //             'product_id'   => $info->product_id,
    //             'product_name' => $info->product->name ?? 'Удаленный товар',
    //             'price'        => $info->product->price,
    //             'loaded'       => $info->loaded,
    //             'left'         => $info->left,
    //             'sold'         => $soldCount,
    //             'profit'       => $info->profit,
    //             'minus'        => $minus, 
    //         ];
    //     });

    //     return response()->json([
    //         'market_name'    => $visit->market->name ?? 'Неизвестно',
    //         'visit_date'     => $visit->created_at->format('d.m.Y H:i'),
    //         'comment'        => $visit->comment,
    //         'photos'         => $visit->images->pluck('image')->map(fn($img) => asset('storage/' . $img)),
            
    //         'products_data'  => $report,
            
    //         // Итоговые показатели по всему визиту
    //         'total_profit'   => $report->sum('profit'),
    //         'total_minus'    => $report->sum('minus'), // Общий минус за весь визит
    //     ]);
    // }

    // public function show(string $id)
    // {
    //     $visit = Visit::with([
    //         'market.stocks', 
    //         'info.product',
    //         'images'
    //     ])->findOrFail($id);

    //     $report = $visit->info->map(function ($info) use ($visit) {
    //         $stock = $visit->market->stocks
    //             ->where('product_id', $info->product_id)
    //             ->first();
            
    //         $currentStockQty = $stock->qty ?? 0;

    //         // 1. Расчет реальных продаж: (Что было + Что привезли) - Что осталось
    //         $totalAvailable = $info->left; //$currentStockQty + $info->loaded;
    //         $soldCount = max(0, $currentStockQty - $info->left); //max(0, $totalAvailable - $info->left);
            
    //         $price = $info->product->price ?? 0;
    //         $expectedAmount = $soldCount * $price;
            
    //         // 2. Расчет "минуса"
    //         $minus = max(0, $expectedAmount - $info->profit);

    //         return [
    //             'product_id'   => $info->product_id,
    //             'product_name' => $info->product->name ?? 'Noma’lum',
    //             'price'        => (int)$price,
    //             'was_in_stock' => (int)$currentStockQty, // Было в магазине
    //             'loaded'       => (int)$info->loaded,       // Привезли сегодня
    //             'left'         => (int)$info->left,         // Осталось после визита
    //             'sold'         => (int)$soldCount,
    //             'profit'       => (int)$info->profit,
    //             'minus'        => (int)$minus, 
    //         ];
    //     });

    //     return response()->json([
    //         'id'             => $visit->id,
    //         'market_name'    => $visit->market->name ?? 'N/A',
    //         'visit_date'     => $visit->created_at->format('d.m.Y H:i'),
    //         'comment'        => $visit->comment,
    //         'photos'         => $visit->images->map(fn($img) => asset('storage/' . $img->image)),
    //         'products_data'  => $report,
    //         'total_sold'     => (int)$report->sum('sold'),
    //         'total_profit'   => (int)$report->sum('profit'),
    //         'total_minus'    => (int)$report->sum('minus'),
    //     ]);
    // }

    public function show(string $id)
    {
        $visit = Visit::with([
            'market', 
            'info.product',
            'images'
        ])->findOrFail($id);

        $report = $visit->info->map(function ($info) {
            $price = $info->product->price ?? 0;
            
            // 1. Используем уже сохраненное поле sold из базы
            $soldCount = $info->sold ?? 0;
            
            // 2. Ожидаемая сумма за этот конкретный товар
            $expectedAmount = $soldCount * $price;
            
            // 3. Расчет "минуса" (недостачи) по этому товару
            $minus = max(0, $expectedAmount - $info->profit);

            // 4. Чтобы показать "сколько было до визита" (для отчета), 
            // используем логику: Остаток + Продажи - Загрузка
            $wasInStock = $info->left + $soldCount - $info->loaded;

            return [
                'product_id'   => $info->product_id,
                'product_name' => $info->product->name ?? 'Noma’lum',
                'price'        => (int)$price,
                'was_in_stock' => (int)$wasInStock,    // Сколько было на полке до действий агента
                'loaded'       => (int)$info->loaded,      // Сколько догрузили
                'left'         => (int)$info->left,        // Сколько осталось в итоге
                'sold'         => (int)$soldCount,         // Сколько продано штук
                'profit'       => (int)$info->profit,      // Сколько денег сдано в кассу
                'minus'        => (int)$minus,             // Недостача по товару
            ];
        });

        return response()->json([
            'id'             => $visit->id,
            'market_name'    => $visit->market->name ?? 'N/A',
            'visit_date'     => $visit->created_at->format('d.m.Y H:i'),
            'comment'        => $visit->comment,
            'photos'         => $visit->images->map(fn($img) => asset('storage/' . $img->image)),
            'products_data'  => $report,
            'total_sold'     => (int)$report->sum('sold'),
            'total_profit'   => (int)$report->sum('profit'),
            'total_minus'    => (int)$report->sum('minus'),
        ]);
    }

    public function edit($id)
    {
        try {
            // Загружаем визит вместе со связанными данными
            $visit = Visit::with([
                'market', 
                'info.product'
            ])->findOrFail($id);

            return response()->json($visit, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Visit topilmadi',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {
            // Загружаем визит с его инфо и связью со складом через маркет
            $visit = Visit::with('info')->findOrFail($id);

            // 1. ОТКАТ СКЛАДА: Возвращаем состояние склада к моменту ДО этого визита
            foreach ($visit->info as $oldInfo) {
                $stock = ProductStock::where('market_id', $visit->market_id)
                    ->where('product_id', $oldInfo->product_id)
                    ->first();
                
                if ($stock) {
                    // Чтобы получить "базу", которая была до визита:
                    // Мы прибавляем то, что было продано (sold) 
                    // И вычитаем то, что было загружено (loaded)
                    $originalQty = $stock->qty - $oldInfo->loaded + $oldInfo->sold;
                    
                    $stock->update(['qty' => $originalQty]);
                }
            }

            // 2. Обновляем основные данные визита
            $visit->update([
                'comment' => $request->comment,
            ]);

            // 3. ПЕРЕСЧЕТ С НОВЫМИ ДАННЫМИ
            if ($request->has('products')) {
                // Удаляем старые записи VisitInfo
                $visit->info()->delete();

                foreach ($request->products as $item) {
                    // Получаем "чистый" остаток на складе (уже откатанный выше)
                    $stock = ProductStock::where('market_id', $visit->market_id)
                        ->where('product_id', $item['id'])
                        ->first();

                    $baseQty = $stock ? $stock->qty : 0;

                    // Считаем новые продажи на основе откатанной базы
                    $newSoldCount = max(0, $baseQty - $item['left']);

                    // Создаем новую запись инфо с полем sold
                    VisitInfo::create([
                        'visit_id'   => $visit->id,
                        'product_id' => $item['id'],
                        'loaded'     => $item['loaded'],
                        'left'       => $item['left'],
                        'profit'     => $item['profit'],
                        'sold'       => $newSoldCount,
                    ]);

                    // Обновляем склад финальным значением
                    ProductStock::updateOrCreate(
                        ['market_id' => $visit->market_id, 'product_id' => $item['id']],
                        ['qty' => $item['left'] + $item['loaded']]
                    );
                }
            }

            // 4. Картинки (добавляем новые, если пришли)
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('visits', 'public');
                    VisitImage::create([
                        'visit_id' => $visit->id,
                        'image'    => $path,
                    ]);
                }
            }

            return response()->json([
                'message' => 'O\'zgartirildi!', 
                'visit' => $visit->load('info')
            ]);
        });
    }


    // public function update(Request $request, $id)
    // {
    //     return DB::transaction(function () use ($request, $id) {
    //         $visit = Visit::with('info')->findOrFail($id);

    //         // 1. "Откатываем" старое влияние визита на склад
    //         foreach ($visit->info as $oldInfo) {
    //             $stock = ProductStock::where('market_id', $visit->market_id)
    //                 ->where('product_id', $oldInfo->product_id)
    //                 ->first();
                
    //             if ($stock) {
    //                 // Вычитаем то, что было добавлено в прошлый раз
    //                 $stock->decrement('qty', ($oldInfo->left + $oldInfo->loaded));
    //             }
    //         }

    //         // 2. Обновляем основные данные визита
    //         $visit->update([
    //             'comment' => $request->comment,
    //         ]);

    //         // 3. Удаляем старые записи о товарах и создаем новые (или обновляем)
    //         if ($request->has('products')) {
    //             // Удаляем старые привязки
    //             $visit->info()->delete();

    //             foreach ($request->products as $item) {
    //                 VisitInfo::create([
    //                     'visit_id'   => $visit->id,
    //                     'product_id' => $item['id'],
    //                     'loaded'     => $item['loaded'],
    //                     'left'       => $item['left'],
    //                     'profit'     => $item['profit'],
    //                 ]);

    //                 // Применяем НОВЫЕ данные к складу
    //                 ProductStock::updateOrCreate(
    //                     ['market_id' => $visit->market_id, 'product_id' => $item['id']],
    //                     ['qty' => DB::raw("qty + " . ($item['left'] + $item['loaded']))]
    //                 );
    //             }
    //         }

    //         // 4. Добавляем новые изображения, если они есть
    //         if ($request->hasFile('images')) {
    //             foreach ($request->file('images') as $image) {
    //                 $path = $image->store('visits', 'public');
    //                 VisitImage::create([
    //                     'visit_id' => $visit->id,
    //                     'image'    => $path,
    //                 ]);
    //             }
    //         }

    //         return response()->json(['message' => 'O\'zgartirildi!', 'visit' => $visit->load('info')]);
    //     });
    // }

    public function destroy($id)
    {
        $visit = Visit::findOrFail($id);
        foreach($visit->images as $img) {
            Storage::disk('public')->delete($img->image);
        }
        $visit->delete();
        return response()->json(['message' => 'Deleted']);
    }
}

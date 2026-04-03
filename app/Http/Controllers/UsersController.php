<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Group;
use App\Traits\ApiResponses;
use Exception;
use Illuminate\Http\Request;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UsersController extends Controller
{
    use ApiResponses;

    public function index()
    {
        return response()->json(User::where('role', 'agent')->get());
    }


    public function store(StoreUserRequest $request)
    {
        try{
            $validated = $request->validated();
            
            $user = User::create([
                'name' => $validated['name'],
                'login' => $validated['login'],
                'role'      => 'agent',
                'permission' => $validated['permission'],
                'password' => Hash::make($validated['password']),
            ]);

        return response()->json(['message' => 'yaratildi'], 201);
        }
        catch(Exception $ex){
            return response()->json([
                'message' => $ex->getMessage(),
            ],400);
        }
    }

    // public function update(UpdateUserRequest $request, User $user)
    // {
    //     $validated = $request->validated();

    //     if ($request->filled('password')) {
    //         $validated['password'] = Hash::make($validated['password']);
    //     }

    //     $user->update($validated);

    //     return response()->json([
    //         'message' => 'Yangilandi'
    //     ]);
    // }

    public function show(string $id)
    {
        try{
            
            $user = User::findOrFail($id);
            return response()->json($user);
        }
        catch(ModelNotFoundException $ex){
            return $this->error('Malumot topilmadi', 404);
        }
    }


    public function update(UpdateUserRequest $request, string $id)
    {
        try{
            $user = User::findOrFail($id);
            $validated = $request->validated();
            if (!empty($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            } else {
                unset($validated['password']);
            }
            $user->update($validated);

            return response()->json(['message' => 'yangilandi'], 200);
        }
        catch(Exception $ex){
            return response()->json([
                'message' => $ex->getMessage(),
            ],400);
        }
    }
    

    public function destroy(string $id)
    {
        try{
            $user = User::findOrFail($id);
            $user->delete();
            return $this->ok('Foydalanuvchi uchirildi');
        }
        catch(ModelNotFoundException $ex){
            return $this->ok('Topilmadi');
        }
    }

    public function markets(string $id){
        try{
            $user = User::findOrFail($id);
            $markets = $user->markets()->get();
            if ($markets->isEmpty()) return response()->json('topilmadi', 404);
            return response()->json($markets, 200);
        }catch(ModelNotFoundException $ex){
            return response()->json(['message' => 'user ni marketlari topilmadi']);
        }
        
    }

    // public function group_markets(){

    //     $markets = Group::with('markets')->get();
    //     if($markets->isEmpty())
    //         return response()->json([
    //             'message' => 'dokonlar topilmadi'
    //         ], 404);
    //     return response()->json($markets);
    // }

    public function group_markets()
    {
        $user = Auth::user();

        $groups = Group::with(['markets' => function ($query) use ($user) {
            // Если это агент, фильтруем магазины через промежуточную таблицу market_users
            $query->when($user->role === 'agent', function ($q) use ($user) {
                return $q->whereHas('users', function ($sq) use ($user) {
                    $sq->where('users.id', $user->id);
                });
            });
        }])
        ->get()
        ->filter(function ($group) {
            return $group->markets->isNotEmpty();
        })
        ->values();

        if ($groups->isEmpty()) {
            return response()->json(['message' => 'Dokonlar topilmadi'], 404);
        }

        return response()->json($groups);
    }

//     public function group_markets2()
// {
//     $user = auth()->user();

//     $groups = Group::query()
//         // Загружаем только те группы, где есть маркеты, доступные пользователю
//         ->whereHas('markets', function ($q) use ($user) {
//             $q->when($user->role === 'agent', function ($sq) use ($user) {
//                 $sq->whereHas('users', fn($u) => $u->where('users.id', $user->id));
//             });
//         })
//         ->with(['markets' => function ($query) use ($user) {
//             // Фильтрация самих маркетов внутри групп
//             $query->when($user->role === 'agent', function ($q) use ($user) {
//                 $q->whereHas('users', fn($sq) => $sq->where('users.id', $user->id));
//             });

//             // Считаем общие остатки на складе маркета
//             $query->withSum('stocks as total_qty', 'qty');

//             // Загружаем последний визит и его детали (теперь через связь в модели)
//             $query->with(['latestVisit.info']);
//         }])
//         ->get();

//     // Проходимся по коллекции для формирования данных под Flutter
//     $groups->each(function ($group) {
//         foreach ($group->markets as $market) {
//             $lastVisit = $market->latestVisit;

//             if ($lastVisit) {
//                 // Используем метод info(), как в вашей модели Visit
//                 $market->last_profit = (int) $lastVisit->info->sum('profit');
//                 $market->last_sold = $lastVisit->info->sum('loaded') - $lastVisit->info->sum('left');
//             } else {
//                 $market->last_profit = 0;
//                 $market->last_sold = 0;
//             }

//             // Убираем объект визита, чтобы JSON был компактным
//             $market->makeHidden('latestVisit');
//         }
//     });

//     return response()->json($groups);
// }
// 2-3 xil tovar bulsa uni xissoblab bulmaydi minusini;

// public function group_markets2()
// {
//     $user = Auth::user(); //auth()->user();

//     $groups = Group::query()
//         ->whereHas('markets', function ($q) use ($user) {
//             $q->when($user->role === 'agent', function ($sq) use ($user) {
//                 $sq->whereHas('users', fn($u) => $u->where('users.id', $user->id));
//             });
//         })
//         ->with(['markets' => function ($query) use ($user) {
//             // Фильтруем маркеты для агента
//             $query->when($user->role === 'agent', function ($q) use ($user) {
//                 $q->whereHas('users', fn($sq) => $sq->where('users.id', $user->id));
//             });

//             // ЖАДНАЯ ЗАГРУЗКА (Eager Loading) — это уберет таймаут
//             // Загружаем сумму остатков и последний визит со всеми вложенными данными
//             $query->withSum('stocks as total_qty', 'qty')
//                   ->with(['latestVisit.info.product']); 
//         }])
//         ->get();

//     $groups->each(function ($group) {
//         foreach ($group->markets as $market) {
//             // Достаем связь, которую мы загрузили через latestOfMany()
//             $lastVisit = $market->latestVisit;

//             if ($lastVisit && $lastVisit->info->isNotEmpty()) {
//                 // 1. Прибыль берем из коллекции visit_infos (связь info)
//                 $market->last_profit = (int) $lastVisit->info->sum('profit');

//                 // 2. Считаем дебет (ожидаемые деньги - реальные деньги)
//                 $expectedCash = $lastVisit->info->sum(function ($detail) {
//                     $soldQty = $detail->loaded - $detail->left;
//                     return $soldQty * ($detail->product->price ?? 0);
//                 });

                
                

//                 $market->last_debt = (int) ($expectedCash - $market->last_profit);
                
//                 // 3. Общее количество проданного товара
//                 $market->last_sold_qty = (int) ($lastVisit->info->sum('loaded') - $lastVisit->info->sum('left'));
//                 $firstProduct = $lastVisit->info->first()->product ?? null;

//                 if ($firstProduct && $firstProduct->price > 0) {
//                     $market->last_minus_qty = (int) ($market->last_debt / $firstProduct->price);
//                 } else {
//                     $market->last_minus_qty = 0;
//                 }
//             } else {
//                 // Если визитов еще не было
//                 $market->last_profit = 0;
//                 $market->last_debt = 0;
//                 $market->last_sold_qty = 0;
//                 $market->last_minus_qty = 0;
//             }

//             // Очищаем JSON от лишних данных, чтобы Flutter было легче парсить
//             $market->total_qty = (int) ($market->total_qty ?? 0);
//             $market->makeHidden(['latestVisit', 'stocks']);
//         }
//     });

//     return response()->json($groups);
// }


    public function group_markets2()
    {
        $user = Auth::user();

        $groups = Group::query()
            ->whereHas('markets', function ($q) use ($user) {
                $q->when($user->role === 'agent', function ($sq) use ($user) {
                    $sq->whereHas('users', fn($u) => $u->where('users.id', $user->id));
                });
            })
            ->with(['markets' => function ($query) use ($user) {
                // Фильтруем маркеты для агента
                $query->when($user->role === 'agent', function ($q) use ($user) {
                    $q->whereHas('users', fn($sq) => $sq->where('users.id', $user->id));
                });

                // Жадная загрузка сумм и последнего визита
                $query->withSum('stocks as total_qty', 'qty')
                    ->with(['latestVisit.info.product']); 
            }])
            ->get();

        $groups->each(function ($group) {
            foreach ($group->markets as $market) {
                $lastVisit = $market->latestVisit;

                if ($lastVisit && $lastVisit->info->isNotEmpty()) {
                    // 1. Прибыль (касса) за последний визит
                    $market->last_profit = (int) $lastVisit->info->sum('profit');

                    // 2. Используем новое поле SOLD для расчета дебета (долга в суммах)
                    $market->last_debt = (int) $lastVisit->info->sum(function ($info) {
                        $expected = ($info->sold ?? 0) * ($info->product->price ?? 0);
                        // Долг — это разница между тем, что продано, и тем, что сдано
                        return max(0, $expected - $info->profit);
                    });

                    // 3. Общее количество проданного товара (штуки)
                    $market->last_sold_qty = (int) $lastVisit->info->sum('sold');

                    // 4. "Минус" в штуках (сколько товара не оплачено)
                    // Теперь считаем точно по каждому товару, а не по "первому попавшемуся"
                    $market->last_minus_qty = (int) $lastVisit->info->sum(function ($info) {
                        $price = $info->product->price ?? 0;
                        if ($price > 0) {
                            $expected = ($info->sold ?? 0) * $price;
                            $debt = max(0, $expected - $info->profit);
                            // Переводим долг по конкретному товару обратно в штуки
                            return $debt / $price;
                        }
                        return 0;
                    });

                } else {
                    // Если визитов не было — всё по нулям
                    $market->last_profit = 0;
                    $market->last_debt = 0;
                    $market->last_sold_qty = 0;
                    $market->last_minus_qty = 0;
                }

                // Приведение типов для Flutter
                $market->total_qty = (int) ($market->total_qty ?? 0);
                
                // Прячем вложенные связи, чтобы не раздувать JSON
                $market->makeHidden(['latestVisit', 'stocks']);
            }
        });

        return response()->json($groups);
    }

    public function syncMarkets(Request $request, $id) {
        $user = User::findOrFail($id);
        
        
        $user->markets()->sync($request->input('market_ids', []));
        
        return response()->json(['message' => 'Synced successfully']);
    }
}

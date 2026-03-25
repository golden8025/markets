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

    public function group_markets2()
    {
        $user = Auth::user();

        $groups = Group::with(['markets' => function ($query) use ($user) {
            // Фильтрация для агента
            $query->when($user->role === 'agent', function ($q) use ($user) {
                return $q->whereHas('users', function ($sq) use ($user) {
                    $sq->where('users.id', $user->id);
                });
            });

            // Добавляем сумму всех продуктов в магазине (qty)
            $query->withSum('stocks as total_qty', 'qty');

            // Подгружаем последний визит с его инфой
            $query->with(['visits' => function($v) {
                $v->latest()->with('visitInfos')->limit(1);
            }]);
        }])
        ->get()
        ->filter(fn($group) => $group->markets->isNotEmpty())
        ->values();

        // Преобразуем данные перед отправкой, чтобы Flutter было легче их читать
        $groups->each(function($group) {
            $group->markets->each(function($market) {
                $lastVisit = $market->visits->first();
                $market->last_profit = $lastVisit ? $lastVisit->visitInfos->sum('profit') : 0;
                // "Минус" (сколько товара ушло/продано)
                $market->last_sold = $lastVisit ? $lastVisit->visitInfos->sum('loaded') - $lastVisit->visitInfos->sum('left') : 0;
                
                unset($market->visits); // Убираем лишнее из JSON
            });
        });

        return response()->json($groups);
    }

    public function syncMarkets(Request $request, $id) {
        $user = User::findOrFail($id);
        
        
        $user->markets()->sync($request->input('market_ids', []));
        
        return response()->json(['message' => 'Synced successfully']);
    }
}

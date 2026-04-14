<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Market;
use Illuminate\Http\Request;
use App\Http\Requests\StoreGroupRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;

class GroupController extends Controller
{
    public function index(){
        $groups = Group::all();
        return response()->json($groups);
    }

    public function destroy(string $id)
    {
        try {
            $group = Group::findOrFail($id);

            if ($group->markets()->exists()) { 
                return response()->json('Dokonlar mavjud', 400);
            }

            $group->delete();
            return response()->json('Uchirildi', 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
            return response()->json('Topilmadi', 404);
        } catch (\Exception $e) {
            return response()->json('Xatolik yuz berdi', 500);
        }
    }

    public function update(Request $request, $id)
    {
        // 1. Валидация
        $request->validate([
            // Имя должно быть уникальным, кроме текущей записи
            'name' => ['required', 'string', 'max:255', 'unique:groups,name,' . $id],
        ]);

        // 2. Поиск и обновление
        $group = Group::findOrFail($id);
        $group->update([
            'name' => $request->name
        ]);

        return response()->json([
            'message' => 'Название успешно обновлено',
            'market' => $group
        ]);
    }

    public function store(StoreGroupRequest $request){
        $group = Group::create($request->validated());

        return response()->json([
            'message' => 'guruh yaratildi',
            'group' => $group
        ], 200);

    }

    public function getGroups()
    {
      
        $groups = Group::with('markets:id,name,group_id')->get();
        return response()->json($groups);
    }

    public function updateGroup(Request $request, $id)
    {
        // Проверяем существование маркета 
        $market = Market::find($id);

        if (!$market) {
            return response()->json(['message' => 'Market topilmadi'], 404);
        }

        // Валидация: group_id может быть null (согласно миграции nullOnDelete) 
        $validator = Validator::make($request->all(), [
            'group_id' => 'nullable|exists:groups,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Обновляем только поле group_id
        $market->update([
            'group_id' => $request->group_id
        ]);

        return response()->json([
            'message' => 'Gurux yangilandi',
            'market' => $market
        ]);
    }
}

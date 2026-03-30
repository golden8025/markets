<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Market;
use Illuminate\Http\Request;
use App\Http\Requests\StoreGroupRequest;
use Illuminate\Support\Facades\Validator;

class GroupController extends Controller
{
    public function index(){
        $groups = Group::all();
        return response()->json($groups);
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

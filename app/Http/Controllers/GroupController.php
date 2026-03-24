<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\Request;
use App\Http\Requests\StoreGroupRequest;

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
}

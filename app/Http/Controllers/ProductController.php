<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Traits\ApiResponses;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use ApiResponses;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Product::all());
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|int',
        ]);

        $product = Product::create($validated);

        return response()->json([
            'message' => 'Yaratildi',
            'data'    => $product
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try{
            $product = Product::findOrFail($id);
            return response()->json($product);
        }
        catch(ModelNotFoundException $ex){
            return $this->error('topilmadi');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try{
            $product = Product::findOrFail($id);
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'price' => 'sometimes|required|int',
            ]);

            $product->update($validated);

            return response()->json([
                'message' => 'Yangilandi',
                'data'    => $product
            ], 201);
        }
        catch(ModelNotFoundException $ex){
            return $this->error("Topilmadi");
        }
        

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
         try{
            $product = Product::findOrFail($id);
            $product->delete();
            return $this->ok('Uchirildi');
        }
        catch(ModelNotFoundException $ex){
            return $this->ok('Topilmadi');
        }
    }

    public function storeInitial(Request $request) {
        $data = $request->validate([
            'market_id' => 'required|exists:markets,id',
            'stocks' => 'required|array',
            'stocks.*.product_id' => 'required|exists:products,id',
            'stocks.*.qty' => 'required|integer|min:1',
        ]);

        foreach ($data['stocks'] as $stock) {
            \App\Models\ProductStock::updateOrCreate(
                ['market_id' => $data['market_id'], 'product_id' => $stock['product_id']],
                ['qty' => $stock['qty']]
            );
        }

        return response()->json(['message' => 'Success']);
    }

    public function getMissingProducts($marketId)
    {
        // Получаем все продукты, которых НЕТ в таблице product_stocks для данного магазина
        $products = Product::whereDoesntHave('stocks', function ($query) use ($marketId) {
            $query->where('market_id', $marketId);
        })->get();

        return response()->json($products);
    }
}

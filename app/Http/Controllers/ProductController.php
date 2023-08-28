<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Product::all();
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }


    //@param \Illuminate\Http\Request $request
   // @return \Illuminate\Http\Response
    public function store(StoreProductRequest $request)
    {
        $request->validate([
            'name'=> 'required',
            'slug'=> 'required',
            'price'=> 'required'
        ]);
        return Product::create($request->all());
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        return Product::find($id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, $id)
    {
        $Product = Product::find($id);
        $Product->update($request->all());
        return $Product;

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        return Product::destroy($id);
    }


     /**
     * Search for product by name
     * @param str $name
     * @return \Illuminate\Http\Response
     */
    public function search($name)
    {
        return Product::where('name','like','%'.$name.'%')->get();
    }
}

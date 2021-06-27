<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Product;
use App\Brand;
use DB;
use Validator;

class ProductController extends Controller
{
    public function index()
    {   
        $products = Product::with('brand')->get();

        $brands = Brand::all();

        return response()->json(['products' => $products, 'brands' => $brands], 200);
    }

    public function create()
    {
        $brands = Brand::all();

        return response()->json(['brands' => $brands], 200);
    }

    public function store(Request $request)
    {
        
        $rules = [
            'brand_id.required' => 'Brand is required',
            'brand_id.integer' => 'Brand must be an integer',
            'model.required' => 'Model is required',
            'serial.required' => 'Serial Number is required',
        ];

        $valid_fields = [
            'brand_id' => 'required|integer',
            'model' => 'required',
            'serial' => 'required',
        ];

        $validator = Validator::make($request->all(), $valid_fields, $rules);

        if($validator->fails())
        {   
            return response()->json($validator->errors(), 200);
        }

        $product = new Product();
        $product->brand_id = $request->get('brand_id');
        $product->model = $request->get('model');
        $product->serial = $request->get('serial');
        $product->quantity = 1;
        $product->save();

        $product = Product::with('brand')
                          ->where('id', '=', $product->id)
                          ->first();

        return response()->json(['success' => 'Record has successfully added', 'product' => $product], 200);
    }

    public function edit($product_id)
    {
        $product_id = $request->get('product_id');

        $product = Product::with('brand')
                          ->where('id', '=', $product_id)
                          ->first();

        //if record is empty then display error page
        if(empty($product->id))
        {
            return abort(404, 'Not Found');
        }
        
        // return view('pages.service.edit', compact('service'));
        return response()->json(['product' => $product], 200);
    }

    public function update(Request $request, $product_id)
    {
        $rules = [
            'brand_id.required' => 'Brand is required',
            'brand_id.integer' => 'Brand must be an integer',
            'model.required' => 'Model is required',
            'serial.required' => 'Serial Number is required',
        ];

        $valid_fields = [
            'brand_id' => 'required|integer',
            'model' => 'required',
            'serial' => 'required',
        ];

        $validator = Validator::make($request->all(), $valid_fields, $rules);

        if($validator->fails())
        {   
            return response()->json($validator->errors(), 200);
        }

        $product = Product::find($product_id);
        $product->brand_id = $request->get('brand_id');
        $product->model = $request->get('model');
        $product->serial = $request->get('serial');
        $product->quantity = 1;
        $product->save();

        $product = Product::with('brand')
                          ->where('id', '=', $product_id)
                          ->first();

        return response()->json(['success' => 'Record has successfully updated', 'product' => $product], 200);
    }

    public function delete(Request $request)
    {
        $product = Product::find($request->get('product_id'));

        //if record is empty then display error page
        if(empty($product->id))
        {
            return abort(404, 'Not Found');
        }

        $product->delete();

        return response()->json(['success' => 'Record has been deleted'], 200);
    }
}

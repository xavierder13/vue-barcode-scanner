<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Product;
use App\Brand;
use App\Branch;
use DB;
use Validator;
use Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProductsExport;

class ProductController extends Controller
{
    public function index()
    {   
        $user = Auth::user();

        $products = Product::with('brand')
                           ->with('branch')
                           ->with('user')
                           ->get();

        // if auth is not admin then filter per branch
        if($user->id !== 1)
        {
            $products = Product::with('brand')
                               ->with('branch')
                               ->with('user')
                               ->where('branch_id' ,'=' ,$user->branch_id )
                               ->get();
        }

        $brands = Brand::all();
        $branches = Branch::all();
        
        return response()->json([
            'products' => $products, 
            'brands' => $brands,
            'branches' => $branches,
            'user' => $user,
        ], 200);
    }

    public function create()
    {   
        $user = Auth::user();
        $brands = Brand::all();
        $branches = Branch::all();

        return response()->json([
            'brands' => $brands,
            'branches' => $branches,
            'user' => $user,
        ], 200);
    }

    public function store(Request $request)
    {
        
        $rules = [
            'branch_id.integer' => 'Branch must be an integer',
            'brand_id.required' => 'Brand is required',
            'brand_id.integer' => 'Brand must be an integer',
            'model.required' => 'Model is required',
            'serial.required' => 'Serial Number is required',
        ];

        $valid_fields = [
            'branch_id' => 'nullable|integer',
            'brand_id' => 'required|integer',
            'model' => 'required',
            'serial' => 'required',
        ];

        $validator = Validator::make($request->all(), $valid_fields, $rules);

        if($validator->fails())
        {   
            return response()->json($validator->errors(), 200);
        }

        $user = Auth::user();
        $branch_id = $request->get('branch_id');

        // if auth is not admin then filter per branch
        if($user->id !== 1)
        {
            $branch_id = $user->branch_id;
        }

        $product = new Product();
        $product->user_id = $user->id;
        $product->branch_id = $branch_id;
        $product->brand_id = $request->get('brand_id');
        $product->model = $request->get('model');
        $product->serial = $request->get('serial');
        $product->quantity = 1;
        $product->save();

        $product = Product::with('brand')
                          ->with('branch')
                          ->with('user')
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

        $user = Auth::user();
        $branch_id = $request->get('branch_id');

        // if auth is not admin then filter per branch
        if($user->id !== 1)
        {
            $branch_id = $user->branch_id;
        }

        $product = Product::find($product_id);
        $product->branch_id = $branch_id;
        $product->brand_id = $request->get('brand_id');
        $product->model = $request->get('model');
        $product->serial = $request->get('serial');
        $product->quantity = 1;
        $product->save();

        $product = Product::with('brand')
                          ->with('branch')
                          ->with('user')
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


    public function export()
    {
        return Excel::download(new ProductsExport, 'products.xls');
    }
}

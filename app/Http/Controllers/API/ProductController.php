<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Product;
use App\Brand;
use DB;

class ProductController extends Controller
{
    public function index()
    {   
        $products = DB::table('products')
                      ->join('brands', 'products.brand_id', '=', 'products')
                      ->select('products.*', DB::raw('brands.name as brand'))
                      ->get();
        return response()->json(['products' => $products], 200);
    }

    public function create()
    {
        $brands = Brand::all();

        return response()->json(['brands' => $brands], 200);
    }
}

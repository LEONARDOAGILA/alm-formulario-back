<?php

namespace App\Http\Controllers\paginaWeb;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\DB;

class PreciosController extends Controller
{
    public function alm_precios()
    {
        try {
            // vista anterior: public.av_producto_tipoproducto_ecommerce            
            $data = DB::table('public.av_producto_tipoproducto_ecommerce_api')->get();

            return response()->json($data);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }
}

<?php

namespace App\Http\Controllers\proylecma;

use App\Http\Controllers\Controller;
use App\Http\Controllers\email\EmailController;
use App\Http\Controllers\formulario\SolicitudesCreditosWebController;
use App\Http\Resources\RespuestaApi;
use App\Http\Resources\ValidacionCedulaRucService;
use App\Models\crm\Cliente;
use App\Models\crm\TelefonosCliente;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class CreditoProylecmaController extends Controller
{
    protected $validacionCedulaRucService;

    public function __construct(ValidacionCedulaRucService $validacionCedulaRucService)
    {
        $this->validacionCedulaRucService = $validacionCedulaRucService;
    }

    public function buscarClienteProylecma(Request $request)
    {
        try {

            $identificacion = $request->input('identificacion');
            $tipoIdentificacion = $request->input('tipoidentificacion');

            if ($tipoIdentificacion == 1) {
                // Verificar la longitud de la cédula es 10
                if (strlen($identificacion) === 10) {
                    $valido = $this->validacionCedulaRucService->esIdentificacionValida($identificacion);
                } else {
                    // return response()->json(['La cédula no contiene los 10 digitos permitidos.'], 400);
                    return response()->json(RespuestaApi::returnResultado('error', 'La cédula no contiene los 10 digitos permitidos.', ''));
                }
            }

            if ($tipoIdentificacion == 2) {
                if (strlen($identificacion) === 13) {
                    $valido = $this->validacionCedulaRucService->esRucValido($identificacion);
                } else {
                    // return response()->json(['El RUC no contiene los 13 digitos permitidos.'], 400);
                    return response()->json(RespuestaApi::returnResultado('error', 'El RUC no contiene los 13 digitos permitidos.', ''));
                }
            }

            if ($tipoIdentificacion == 3) {
                $valido = 1;
            }

            if ($valido) {

                // Cliente OPENCEO PROYLECMA
                $cliente = DB::connection('bdproylecma')->selectOne("SELECT 
                                                e.ent_id as id, --c.id,
                                                e.ent_identificacion as identificacion,
                                                e.ent_tipo_identificacion as tipoidentificacion,
                                                e.ent_nombres as nombres,
                                                e.ent_apellidos as apellidos,
                                                null as direccion,
                                                null as prv_nombre,
                                                null as ctn_nombre,
                                                null as prq_nombre,
                                                e.ent_email as email,
                                                null as telefono
                                            FROM public.entidad e 
                                            --left join crm.cliente c on c.ent_id = e.ent_id 
                                            WHERE e.ent_identificacion like '%$identificacion%'");

                if ($cliente) {
                    return response()->json(RespuestaApi::returnResultado('success', 'Se listo con exito', $cliente));
                } else {
                    // return response()->json($cliente);
                    return response()->json(RespuestaApi::returnResultado('error', 'El cliente no existe.', ''));
                }

            } else {

                // return response()->json(['La cédula o RUC no es válida.'], 400);
                return response()->json(RespuestaApi::returnResultado('error', 'La cédula o RUC no es válida.', ''));
            }

        } catch (Exception $e) {

            // return response()->json(['error' => $e->getMessage()]);
            return response()->json(RespuestaApi::returnResultado('error', 'Error', $e->getMessage()));
        }
    }

}

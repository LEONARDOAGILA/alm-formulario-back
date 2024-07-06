<?php

namespace App\Http\Controllers\paginaWeb;

use App\Http\Controllers\Controller;
use App\Http\Controllers\email\EmailController;
use App\Http\Controllers\formulario\SolicitudesCreditosWebController;
use App\Http\Resources\ValidacionCedulaRucService;
use App\Models\crm\Cliente;
use App\Models\crm\TelefonosCliente;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class CreditoController extends Controller
{
    protected $validacionCedulaRucService;

    public function __construct(ValidacionCedulaRucService $validacionCedulaRucService)
    {
        $this->validacionCedulaRucService = $validacionCedulaRucService;
    }

    public function buscarCliente(Request $request)
    {
        try {

            $identificacion = $request->input('identificacion');
            $tipoIdentificacion = $request->input('tipoidentificacion');

            if ($tipoIdentificacion == 1) {
                // Verificar la longitud de la cédula es 10
                if (strlen($identificacion) === 10) {
                    $valido = $this->validacionCedulaRucService->esIdentificacionValida($identificacion);
                } else {
                    return response()->json(['La cédula no contiene los 10 digitos permitidos.'], 400);
                }
            }

            if ($tipoIdentificacion == 2) {
                if (strlen($identificacion) === 13) {
                    $valido = $this->validacionCedulaRucService->esRucValido($identificacion);
                } else {
                    return response()->json(['El RUC no contiene los 13 digitos permitidos.'], 400);
                }
            }

            if ($tipoIdentificacion == 3) {
                $valido = 1;
            }

            if ($valido) {
                // Cliente CRM
                $cliente = DB::selectOne("SELECT 
                                            c.id,
                                            c.identificacion,
                                            c.tipo_identificacion as tipoidentificacion,
                                            c.nombres,
                                            c.apellidos,
                                            c.direccion,
                                            c.prv_nombre,
                                            c.ctn_nombre,
                                            c.prq_nombre,
                                            c.email,
                                            null as telefono
                                        FROM crm.cliente c 
                                        WHERE c.identificacion like '%$identificacion%'");


                if (!$cliente) {
                    // Cliente OPENCEO
                    $cliente = DB::selectOne("SELECT 
                                                c.id,
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
                                            left join crm.cliente c on c.ent_id = e.ent_id 
                                            WHERE e.ent_identificacion like '%$identificacion%'");
                }


                if ($cliente) {
                    return response()->json($cliente);
                } else {
                    // return response()->json($cliente);
                    return response()->json(['El cliente no existe.'], 200);
                }

                // return response()->json(['mensaje' => 'La cédula o RUC es válida.'], 200);
            } else {

                return response()->json(['La cédula o RUC no es válida.'], 400);
            }

        } catch (Exception $e) {

            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function crearCaso(Request $request)
    {
        try {
            $error = null;
            $exitoso = null;

            $validation = Validator::make(
                $request->all(),
                [
                    'identificacion' => 'required',
                    'tipoidentificacion' => 'required',
                    'nombres' => 'required',
                    'apellidos' => 'required',
                    // 'direccion' => 'required',
                    'prv_nombre' => 'required',
                    'ctn_nombre' => 'required',
                    // 'prq_nombre' => 'required',
                    'email' => 'required',
                    'telefono' => 'required',
                ],
                [
                    'identificacion' => 'La identificacion es requerido',
                    'tipoidentificacion' => 'El tipo identificacion es requerido',
                    'nombres' => 'Los nombres es requerido',
                    'apellidos' => 'Los apellidos es requerido',
                    // 'direccion' => 'La direccion es requerido',
                    'prv_nombre' => 'La provincia es requerido',
                    'ctn_nombre' => 'La ciudad es requerido',
                    // 'prq_nombre' => 'es requerido',
                    'email' => 'El email es requerido',
                    'telefono' => 'El telefono es requerido',
                ]
            );

            if (!$validation->fails()) {

                DB::transaction(function () use ($request, &$error, &$exitoso) {
                    $cliente = $request->all();

                    $ent_id = null;

                    if ($cliente['id'] != null) {

                        // Identificacion 
                        // Nombres
                        // Apellidos *
                        // Dirección Domiciliaria *
                        // Provincia *
                        // Ciudad *
                        // Teléfono Celular *
                        // Email *

                        // parroquia
                        // tipoIndentificacion

                        $dataCliente = Cliente::find($cliente['id']);

                        if ($dataCliente) {

                            $ent_id = $dataCliente->ent_id;

                            $dataCliente->update([
                                "nombres" => $cliente['nombres'],
                                "apellidos" => $cliente['apellidos'],
                                "direccion" => $cliente['direccion'],
                                "prv_nombre" => $cliente['prv_nombre'],
                                "ctn_nombre" => $cliente['ctn_nombre'],
                                "prq_nombre" => $cliente['prq_nombre'],
                                "email" => $cliente['email'],
                                "nombre_comercial" => $cliente['apellidos'] . ' ' . $cliente['nombres'],
                            ]);

                        }

                    } else {

                        $objetoJsonCliente = (object) [
                            'tipoidentificacion' => $cliente['tipoidentificacion'],
                            'identificacion' => $cliente['identificacion'],
                            'nombres' => $cliente['nombres'],
                            'apellidos' => $cliente['apellidos'],
                            'direccion' => $cliente['direccion'],
                            'telefono' => $cliente['telefono'],
                            'email' => $cliente['email'],
                            'empId' => 1,
                        ];

                        $requestDataCliente = json_decode(json_encode($objetoJsonCliente), true);

                        $responseCliente = Http::post('api.almacenesespana.ec/api/crm/addClienteOpenceo', $requestDataCliente)->throw();
                        // echo 'data _____ ' . json_encode($responseCliente->json());

                        //Respuesta de crear el cliente del dynamo
                        if ($responseCliente->json()['status'] == 'success') {
                            $ent_id = $responseCliente->json()['data']['ent_id'];
                            // echo ' -*-*-*-*-*-*- ' . json_encode($ent_id) . ' -*-*-*-*-*-*- ';
                        } else {
                            $error = 'Error al crear el cliente.';
                            return null;
                        }

                    }

                    //--- Configuracion del destino del caso
                    $configuracion = DB::selectOne("SELECT tcf.* from crm.tipo_caso tc
                                                    inner join crm.tipo_caso_formulas tcf on tcf.tc_id = tc.id
                                                    where tc.nombre = 'SOLICITUD DE CREDITO PAGINA WEB' and tc.estado = true 
                                                limit 1;");
                    //// echo json_encode($configuracion);
                    if ($configuracion) {

                        //--- Add miembros administradores del tablero
                        $miembrosAdminTablero = DB::select('SELECT u.id from crm.tablero_user tu
                            inner join crm.users u on u.id = tu.user_id
                            where tu.tab_id = ? and u.usu_tipo in (2,3);', [$configuracion->tab_id]);

                        $miembros = [];

                        foreach ($miembrosAdminTablero as $miembro) {
                            array_push($miembros, $miembro->id);
                        }

                        // Fecha actual
                        $fechaActual = Carbon::now();

                        // Separar las horas, minutos y segundos
                        list ($horas, $minutos, $segundos) = explode(':', $configuracion->tiempo_vencimiento);

                        // Sumar las horas, minutos y segundos
                        $fechaVencimiento = ($fechaActual->addHours($horas)->addMinutes($minutos)->addSeconds($segundos))->format('Y-m-d H:i:s');

                        $objetoJson = (object) [
                            // "id" => null,
                            "fas_id" => $configuracion->fase_id,
                            "nombre" => 'Solicitud de credito aplicación movil',
                            "descripcion" => 'Pedido generado desde la pagina web',
                            "estado" => $configuracion->estado,
                            "orden" => 1,
                            "ent_id" => $ent_id,
                            "user_id" => $configuracion->user_id,
                            "prioridad" => $configuracion->prioridad,
                            "fecha_vencimiento" => $fechaVencimiento,
                            "fase_anterior_id" => $configuracion->fase_id,
                            "user" => null,
                            "entidad" => null,
                            "miembros" => $miembros,
                            "comentarios" => null,
                            "resumen" => null,
                            "bloqueado" => false,
                            "bloqueado_user" => "",
                            "tar_id" => null,
                            "ctt_id" => null,
                            "tareas" => null,
                            "tc_id" => $configuracion->tc_id,
                            "tableroId" => $configuracion->tab_id,
                            "estado_2" => $configuracion->estado_2,
                            "fase_creacion_id" => $configuracion->fase_id,
                            "tablero_creacion_id" => $configuracion->tab_id,
                            "dep_creacion_id" => $configuracion->dep_id,
                            "fase_anterior_id_reasigna" => $configuracion->fase_id,
                            "user_anterior_id" => $configuracion->user_id,
                            "user_creador_id" => $configuracion->user_id,
                            "cpp_id" => null,
                        ];
                        // echo '1';
                        $requestData = json_decode(json_encode($objetoJson), true);
                        // Realizar la solicitud HTTP POST al endpoint en la aplicación de destino
                        // $response = Http::get('https://almacenesespana.ec/prueba2/crm_back/public/index.php/api/crm/listClientes/0107');

                        // $response = Http::get('api.almacenesespana.ec/api/crm/listClientes/0107');

                        $casoCreado = Http::post('api.almacenesespana.ec/api/crm/addCaso', $requestData)->throw();

                        // echo 'Data caso creado _____ ' . json_encode($casoCreado->json());

                        if ($casoCreado->json()['status'] == 'success') {
                            // $ent_id = $casoCreado->json()['data']['ent_id'];
                            // echo ' -*-*-*-*-*-*- CASO CREADO CON EXITO -*-*-*-*-*-*- ';



                            // echo ' --->2';
                            // echo ' ---- ent_id ---> ' . $ent_id;
                            $dataCliente2 = Cliente::where('ent_id', $ent_id)->first();
                            // echo ' ----- >3';
                            // echo 'dataCliente2 ----> ' . json_encode($dataCliente2);
                            if ($dataCliente2 != null) {
                                // echo '4';
                                $dataCliente2->update([
                                    "nombres" => $cliente['nombres'],
                                    "apellidos" => $cliente['apellidos'],
                                    "direccion" => $cliente['direccion'],
                                    "prv_nombre" => $cliente['prv_nombre'],
                                    "ctn_nombre" => $cliente['ctn_nombre'],
                                    "prq_nombre" => $cliente['prq_nombre'],
                                    "email" => $cliente['email'],
                                    "nombre_comercial" => $cliente['apellidos'] . ' ' . $cliente['nombres'],
                                ]);
                                // Http::post('api.almacenesespana.ec/api/crm/addCaso', $requestData)->throw();

                            }
                            // else{
                            //   // echo '4(1)';
                            //   Http::post('api.almacenesespana.ec/api/crm/addCaso', $requestData)->throw();
                            // }




                            if ($cliente['telefono'] != null) {
                                // echo ' ---> 5';


                                $dataClienteTelf = TelefonosCliente::where('cli_id', $dataCliente2->id)
                                    ->where('numero_telefono', $cliente['telefono'])
                                    ->first();
                                // echo '5(1)';
                                if (!$dataClienteTelf) {
                                    $dataClienteTelf = TelefonosCliente::create([
                                        "cli_id" => $dataCliente2->id,
                                        "numero_telefono" => $cliente['telefono'],
                                        "tipo_telefono" => 'No Definido',
                                    ]);
                                }
                            }
                            // echo '6';
                            //           Http::post('api.almacenesespana.ec/api/crm/addCaso', $requestData)->throw();
                            // echo '7';


                            $addSolicitudCreditoWeb = new SolicitudesCreditosWebController();
                            $respSolicitud = $addSolicitudCreditoWeb->addSolicitudCreditoWeb($request);

                            $empresa = $request->has('empresa') ? $request->empresa : 'NINGUNA'; // veo si existe el campo empresa sino coloco NINGUNA

                            // Todos los datos que vamos a enviar en el correo
                            $object = (object) [
                                'asunto' => 'AUTORIZACIÓN PARA EL TRATAMIENTO DE DATOS PERSONALES Y DE RIESGO CREDITICIO ENTRE EL CLIENTE Y ALMESPANA CIA. LTDA',
                                'linkAutorizar' => 'https://almacenesespana.ec/prueba2/ecommerce-back/public/api/ecommerce/autorizarTratamientoDatos/' . $empresa . '/' . $respSolicitud->getData()->data->id,
                                // 'linkAutorizar' => 'http://192.168.1.105:8008/api/ecommerce/autorizarTratamientoDatos/' . $empresa . '/' . $respSolicitud->getData()->data->id,
                            ];

                            $enviarCorreo = new EmailController();
                            $enviarCorreo->sendEmailTerminosCondiciones($request->email, $object);

                            // $exitoso = $cliente;
                            $exitoso = 'Gracias por aplicar en breve un asesor se comunicara con Ud.';
                            return null;

                        } else {
                            $error = 'Error al crear el caso.';
                            return null;
                        }

                    }

                });

                if ($exitoso) {
                    return response()->json($exitoso);
                } else {
                    return response()->json($error);
                }

            } else {
                return response()->json(['error' => $validation->messages()]);
            }

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

}

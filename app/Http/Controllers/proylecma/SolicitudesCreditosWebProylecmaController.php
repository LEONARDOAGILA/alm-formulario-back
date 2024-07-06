<?php

namespace App\Http\Controllers\proylecma;

use App\Http\Controllers\Controller;
use App\Http\Controllers\email\EmailController;
use App\Http\Resources\RespuestaApi;
use App\Models\proylecma\ClienteProylecma;
use App\Models\proylecma\DireccionProylecma;
use App\Models\proylecma\EmpresaProylecma;
use App\Models\proylecma\EntidadProylecma;
use App\Models\proylecma\SolicitudesCreditosWebProylecma;
use App\Models\proylecma\TelefonoProylecma;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SolicitudesCreditosWebProylecmaController extends Controller
{

    public function addSolicitudCreditoWebProylecma(Request $request)
    {
        try {
            $data = DB::transaction(function () use ($request) {

                $addClienteProylecma = $this->addClienteProylecma($request);
                $responseaddClienteProylecma = json_decode($addClienteProylecma->getContent(), true); // convertimos la respuesta a json
                // echo $responseCliente;

                if ($responseaddClienteProylecma['status'] == 'success') {
                    // Obtener la dirección MAC del servidor (no es posible obtener la MAC del cliente desde el lado del servidor)
                    // $mac = exec('getmac');
                    $mac = "0.0.0.0";

                    // Guardar la solicitud de crédito web maquetado o a mano
                    $respuesta = SolicitudesCreditosWebProylecma::create([
                        'identificacion' => $request->identificacion,
                        'tipoidentificacion' => $request->tipoidentificacion,
                        'nombres' => $request->nombres,
                        'apellidos' => $request->apellidos,
                        'direccion' => $request->direccion,
                        'prv_nombre' => $request->prv_nombre,
                        'ctn_nombre' => $request->ctn_nombre,
                        'prq_nombre' => null, // null porque no se manda desde el front
                        'email' => $request->email,
                        'telefono' => $request->telefono,
                        'ip' => $request->ip,
                        'mac' => $mac,
                        'aceptar_terminos_condiciones' => $request->aceptar_terminos_condiciones ?? true, // true porque desde la pagina web no me envia este valor y se estaba guardando en null (True porque en la pagina web el cliente si esta aceptando los terminos y condiciones)
                        'confirmacion_terminos_condiciones' => false, // por defecto false, true se hace cuando el usuario o cliente acepte o confirme desde el email
                        'latitud' => $request->latitud,
                        'longitud' => $request->longitud,
                        'formulario_tipo' => $request->formulario_tipo ?? 0,
                        'empresa' => $request->empresa,
                    ]);

                    // Todos los datos que vamos a enviar en el correo
                    $object = (object) [
                        'asunto' => 'AUTORIZACIÓN PARA EL TRATAMIENTO DE DATOS PERSONALES Y DE RIESGO CREDITICIO ENTRE EL CLIENTE Y PROYLECMA',
                        'linkAutorizar' => 'https://almacenesespana.ec/prueba2/ecommerce-back/public/api/proylecma/autorizarTratamientoDatosProylecma/' . $respuesta->id,
                        // 'linkAutorizar' => 'http://192.168.1.105:8008/api/proylecma/autorizarTratamientoDatosProylecma/' . $respuesta->id,
                    ];

                    $enviarCorreo = new EmailController();
                    $enviarCorreo->sendEmailTerminosCondicionesProylecma($request->email, $object);

                    return response()->json(RespuestaApi::returnResultado('success', 'Se envio correctamente el Formulario.', $respuesta));
                } else {

                    return response()->json(RespuestaApi::returnResultado('error', $responseaddClienteProylecma['message'], ''));
                }

            });

            return $data;
        } catch (Exception $e) {
            return response()->json(RespuestaApi::returnResultado('error', 'Error', $e->getMessage()));
        }
    }

    public function autorizarTratamientoDatosProylecma($id)
    {
        try {
            $data = DB::transaction(function () use ($id) {
                $solicitud = SolicitudesCreditosWebProylecma::find($id);

                if ($solicitud) {

                    $solicitud->update([
                        'confirmacion_terminos_condiciones' => true,
                    ]);

                    // return response()->json(RespuestaApi::returnResultado('success', 'Se actualizo con exito', $solicitud));
                    return view('mail.confirmacionTerminosCondicionesProylecma');

                } else {
                    // return 'No existe la solicitud con el ID: ' . $id;
                    return response()->json(RespuestaApi::returnResultado('error', 'No existe la solicitud con el ID: ' . $id, ''));
                }
            });

            return $data;
        } catch (Exception $e) {
            return response()->json(RespuestaApi::returnResultado('error', 'Error', $e->getMessage()));
        }
    }

    // Para la lista del combo box del formulario
    public function listAllEmpresasProylecma()
    {
        try {
            $data = EmpresaProylecma::where('locked', false)->get();

            return response()->json(RespuestaApi::returnResultado('success', 'Se listo con éxito', $data));
        } catch (Exception $e) {
            return response()->json(RespuestaApi::returnResultado('error', 'Error', $e->getMessage()));
        }
    }

    public function addClienteProylecma(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'direccion' => 'required|string',
            'telefono' => 'required|string',
            'identificacion' => 'required|string',
            'tipoidentificacion' => 'required|string',
            'nombres' => 'required|string',
            'apellidos' => 'required|string',
            'email' => 'required|string',
            'empId' => 'required|numeric',
            // 'identificacionConyugue' => 'required|string',
            // 'nombreConyugue' => 'required|string',
            // 'apellidoConyugue' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(RespuestaApi::returnResultado('error', 'Validación datos', $validator->errors()));
        }
        try {

            $base_datos = 'bdproylecma';

            $entidad = DB::connection($base_datos)->selectOne("SELECT 
            *
            FROM public.entidad e 
            -- LEFT JOIN public.cliente c ON c.ent_id = e.ent_id 
            WHERE e.ent_identificacion LIKE ?", ['%' . $request->input('identificacion') . '%']);


            if (!$entidad) {

                $cliente = DB::transaction(function () use ($request, $base_datos) {
                    $direccion = $request->input('direccion');
                    $telefono = $request->input('telefono');
                    $identificacion = $request->input('identificacion');
                    $tipoIdentificacion = $request->input('tipoidentificacion');
                    $nombres = $request->input('nombres');
                    $apellidos = $request->input('apellidos');
                    $email = $request->input('email');
                    $empId = $request->input('empId');
                    $identificacionConyugue = $request->input('identificacionConyugue');
                    $nombreConyugue = $request->input('nombreConyugue');
                    $apellidoConyugue = $request->input('apellidoConyugue');
                    //--- NUEVA DIRECCION
                    $newDireccion = new DireccionProylecma();
                    $newDireccion->dir_calle_principal = $direccion;
                    $newDireccion->save();
                    //--- NUEVO TELEFONO
                    $newTelefono = new TelefonoProylecma();
                    $newTelefono->tte_id = 1;
                    $newTelefono->tel_numero = $telefono;
                    $newTelefono->save();
                    //--- NUEVA ENTIDAD
                    $valor1 = DB::connection($base_datos)->selectOne("SELECT to_number(par_texto,'999999') as tit_id from parametro where par_abreviacion='TIT' and mod_abreviatura='CLI' limit 1");
                    $newEntidad = new EntidadProylecma();
                    $newEntidad->ent_identificacion = $identificacion;
                    $newEntidad->ent_nombres = $nombres;
                    $newEntidad->ent_apellidos = $apellidos;
                    $newEntidad->tit_id = $valor1->tit_id;
                    $newEntidad->ent_direccion_principal = $newDireccion->dir_id;
                    $newEntidad->ent_tipo_identificacion = $tipoIdentificacion;
                    $newEntidad->ent_email = $email;
                    $newEntidad->ent_telefono_principal = $newTelefono->tel_id;
                    $newEntidad->save();
                    //--- NUEVO CLIENTE
                    $valor2 = DB::connection($base_datos)->selectOne("SELECT to_number(par_texto,'999999') as tit_id from parametro where par_abreviacion='UBI' and mod_abreviatura='CLI' LIMIT 1");
                    $valor3 = DB::connection($base_datos)->selectOne("SELECT zon_id from zona where zon_codigo in (select par_texto from parametro
                            where par_abreviacion='ZON' and mod_abreviatura='CLI' limit 1) limit 1");
                    $valor4 = DB::connection($base_datos)->selectOne("SELECT cat_id from catcliente where cat_abreviacion = 'clien'");
                    $valor5 = DB::connection($base_datos)->selectOne("SELECT pol_id from politica where pol_nombre = 'CONTADO' and pol_tipocli = 1");
                    // $valor6 = DB::connection($base_datos)->selectOne("SELECT lpr_id from listapre where lpr_nombre in (select par_texto
                    //         from parametro where par_abreviacion='LPR' and mod_abreviatura='CLI' limit 1) limit 1");
                    $valor7 = DB::connection($base_datos)->selectOne("SELECT to_number(par_texto,'999999') as can_id from parametro where par_abreviacion='CAN' and mod_abreviatura='CLI' limit 1");
                    $newCliente = new ClienteProylecma();
                    $newCliente->cli_codigo = $identificacion;
                    $newCliente->ent_id = $newEntidad->ent_id;
                    $newCliente->ubi_id = $valor2->tit_id;
                    $newCliente->zon_id = $valor3->zon_id;
                    $newCliente->cat_id = $valor4->cat_id;
                    $newCliente->pol_id = $valor5->pol_id;
                    // $newCliente->lpr_id =  $valor6->lpr_id;
                    $newCliente->lpr_id = 1; // 1 => CONTADO
                    $newCliente->cli_tipocli = 1;
                    $newCliente->emp_id = $empId;
                    $newCliente->can_id = $valor7->can_id;
                    $newCliente->ent_nombre_comercial = $nombres . ' ' . $apellidos;
                    $newCliente->cli_tiposujeto = 'N';
                    $newCliente->cli_sexo = 'M';
                    $newCliente->cli_estadocivil = 'S';
                    $newCliente->cli_ingresos = 'I';
                    $newCliente->save();
                    $cliTipoPago = DB::connection($base_datos)->insert("insert into cliente_tipo_pago(cli_id, sfp_id) values (?, 1)", [$newCliente->cli_id]);
                    if ($identificacionConyugue && $nombreConyugue && $apellidoConyugue) {
                        $cliAnexo = DB::connection($base_datos)->insert(
                            "INSERT into cliente_anexo(cliane_identificacion_conyuge, cliane_nombre_conyuge,cli_id) values (?,?,?)",
                            [$identificacionConyugue, $nombreConyugue, $newCliente->cli_id]
                        );
                    }
                    return $newCliente;
                });

                return response()->json(RespuestaApi::returnResultado('success', 'Listado con exito', $cliente));
            } else {
                return response()->json(RespuestaApi::returnResultado('success', 'Listado con exito', $entidad));
            }

        } catch (\Throwable $th) {
            return response()->json(RespuestaApi::returnResultado('exception', 'Al listar', $th->getMessage()));
        }
    }

}

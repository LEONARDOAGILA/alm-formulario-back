<?php

namespace App\Http\Controllers\rrhh;

use App\Http\Controllers\Controller;
use App\Http\Controllers\email\EmailController;
use App\Http\Resources\RespuestaApi;
use App\Http\Resources\ValidacionCedulaRucService;
use App\Models\crm\SolicitudesCreditosWeb;
use App\Models\rrhh\ExperienciaLaboral;
use App\Models\rrhh\Pais;
use App\Models\rrhh\Parentesco;
use App\Models\rrhh\Persona;
use App\Models\rrhh\Postulacion;
use App\Models\rrhh\Provincia;
use App\Models\rrhh\Referencia;
use App\Models\rrhh\Telefono;
use App\Models\rrhh\TipoTelefono;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Exception;

class TrabajaConNostrosController extends Controller
{
    protected $validacionCedulaRucService;
    public function __construct(ValidacionCedulaRucService $validacionCedulaRucService)
    {
        $this->validacionCedulaRucService = $validacionCedulaRucService;
        $this->middleware('auth:api', [
            'except' => [
                'personaByIdentificacion',
                'addDatosPersonales',
                'addReferencia',
                'editReferencia',
                'deleteReferencia',
                'addExperienciaLaboral',
                'editExperienciaLaboral',
                'deleteExperienciaLaboral',
                'addCurriculum',
                'addPostulacion',
                'listPaises',
                'listProvincias',
                'listTiposTelefonos',
                'listCiudades',
                'listCargos',
                'listParentescos',
            ]
        ]);
    }

    public function personaByIdentificacion($identificacion)
    {
        try {
            $persona = Persona::where('identificacion', $identificacion)->first();

            if ($persona) {
                $persona = Persona::where('identificacion', $persona->identificacion)->with('telefonos.tipo_telefono', 'referencias.parentesco', 'experiencias_laborales')->first();
            }

            return response()->json(RespuestaApi::returnResultado('success', 'Se listo con exito', $persona));
        } catch (Exception $e) {
            return response()->json(RespuestaApi::returnResultado('error', 'Error', $e->getMessage()));
        }
    }

    public function addPersona(Request $request)
    {
        try {
            $data = DB::transaction(function () use ($request) {

                // para ver si existe ya esa persona con esa cedula
                $identificacionDuplicada = Persona::where('identificacion', $request->identificacion)
                    ->where('id', '!=', $request->id) // Excluye el registro actual
                    ->first();

                if ($identificacionDuplicada) {
                    return response()->json(RespuestaApi::returnResultado('error', 'Ya existe una persona con está identificación ' . $request->identificacion, ''));
                } else {

                    $persona = Persona::find($request->id);

                    // si existe la persona actualizo todos los datos
                    if ($persona) {

                        $persona->update($request->all());

                        // Obtén los IDs de los teléfonos existentes en la base de datos
                        $telefonosDBIds = $persona->telefonos->pluck('id')->toArray();

                        // Obtén los IDs de los teléfonos enviados desde el frontend
                        $telefonosFrontendIds = collect($request->telefonos)->pluck('id')->toArray();

                        // Encuentra los teléfonos a eliminar
                        $telefonosAEliminar = array_diff($telefonosDBIds, $telefonosFrontendIds);
                        Telefono::whereIn('id', $telefonosAEliminar)->delete();

                        // Iterar sobre cada teléfono para actualizar o crear
                        foreach ($request->telefonos as $telefonoData) {
                            // Verificar si el teléfono ya existe (por id) o si es nuevo
                            if (isset($telefonoData['id'])) {
                                // Si tiene ID, actualizar el teléfono existente
                                $telefono = Telefono::find($telefonoData['id']);
                                if ($telefono) {
                                    $telefono->update([
                                        'numero' => $telefonoData['numero'],
                                        'id_tipo_telefono' => $telefonoData['id_tipo_telefono'],
                                        // otros campos que puedan ser actualizados
                                    ]);
                                }
                            } else {
                                // Si no tiene ID, crear un nuevo teléfono
                                $telefonoData['id_persona'] = $persona->id; // Asignar el ID de la persona
                                Telefono::create($telefonoData);
                            }
                        }

                        $persona = Persona::where('id', $persona->id)->with('telefonos.tipo_telefono')->first();

                        // return $persona;
                        return response()->json(RespuestaApi::returnResultado('success', 'Se actualizo con exito', $persona));

                    } else {

                        $persona = Persona::create($request->all());

                        foreach ($request->telefonos as $telf) {
                            $telf['id_persona'] = $persona->id;
                            Telefono::create($telf);
                        }

                        $persona = Persona::where('id', $persona->id)->with('telefonos.tipo_telefono')->first();

                        // return $persona;
                        return response()->json(RespuestaApi::returnResultado('success', 'Se guardo con exito', $persona));

                    }

                } // end identificacionDuplicada

            });

            return $data;
            // return response()->json(RespuestaApi::returnResultado('success', 'Se guardo con exito', $data));
        } catch (Exception $e) {
            return response()->json(RespuestaApi::returnResultado('error', 'Error', $e->getMessage()));
        }
    }

    public function addDatosPersonales(Request $request)
    {
        try {
            $cedulaValida = null;
            $data = DB::transaction(function () use ($request, &$cedulaValida) {

                // si es de tipo 1 valida la cedula
                if ($request->tipo_identificacion === 1) {
                    $cedulaValida = $this->validacionCedulaRucService->esIdentificacionValida($request->identificacion); // devuelve false en caso de error en la validacion de la cedula

                    if ($cedulaValida === true) {
                        return $this->addPersona($request);
                    } else {
                        return $cedulaValida;
                    }

                } else {
                    return $this->addPersona($request);
                }
            });

            if ($cedulaValida === false) {
                return response()->json(RespuestaApi::returnResultado('error', 'Cédula inválida.', ''));
            } else {

                if ($data->original['status'] == 'success') {
                    return response()->json(RespuestaApi::returnResultado('success', $data->original['message'], $data->original['data']));
                } else {
                    return response()->json(RespuestaApi::returnResultado('error', $data->original['message'], ''));
                }

            }

        } catch (Exception $e) {
            return response()->json(RespuestaApi::returnResultado('error', 'Error', $e->getMessage()));
        }
    }

    public function addReferencia(Request $request)
    {
        try {
            $ref = DB::transaction(function () use ($request) {
                $referencia = Referencia::create($request->all());

                return $referencia;
            });

            $data = Referencia::where('id_persona', $ref->id_persona)->with('parentesco')->get();

            return response()->json(RespuestaApi::returnResultado('success', 'Se guardo con exito', $data));
        } catch (Exception $e) {
            return response()->json(RespuestaApi::returnResultado('error', 'Error', $e->getMessage()));
        }
    }

    public function editReferencia(Request $request, $id)
    {
        try {
            $ref = DB::transaction(function () use ($request, $id) {
                $referencia = Referencia::find($id);

                $referencia->update($request->all());

                return $referencia;
            });

            $data = Referencia::where('id', $id)->with('parentesco')->first();

            return response()->json(RespuestaApi::returnResultado('success', 'Se actualizo con éxito', $data));
        } catch (Exception $e) {
            return response()->json(RespuestaApi::returnResultado('error', 'Error', $e->getMessage()));
        }
    }

    public function deleteReferencia($id)
    {
        try {
            $data = DB::transaction(function () use ($id) {
                $referencia = Referencia::find($id);

                $referencia->delete();

                return $referencia;
            });

            return response()->json(RespuestaApi::returnResultado('success', 'Se elimino con exito', $data));
        } catch (Exception $e) {
            return response()->json(RespuestaApi::returnResultado('error', 'Error', $e->getMessage()));
        }
    }

    public function addExperienciaLaboral(Request $request)
    {
        try {
            $exp = DB::transaction(function () use ($request) {
                $experiencia_laboral = ExperienciaLaboral::create($request->all());

                return $experiencia_laboral;
            });

            $data = ExperienciaLaboral::where('id_persona', $exp->id_persona)->get();

            return response()->json(RespuestaApi::returnResultado('success', 'Se guardo con exito', $data));
        } catch (Exception $e) {
            return response()->json(RespuestaApi::returnResultado('error', 'Error', $e->getMessage()));
        }
    }

    public function editExperienciaLaboral(Request $request, $id)
    {
        try {
            $exp = DB::transaction(function () use ($request, $id) {
                $experiencia_laboral = ExperienciaLaboral::find($id);

                $experiencia_laboral->update($request->all());

                return $experiencia_laboral;
            });

            $data = ExperienciaLaboral::where('id_persona', $exp->id_persona)->get();

            return response()->json(RespuestaApi::returnResultado('success', 'Se actualizo con exito', $data));
        } catch (Exception $e) {
            return response()->json(RespuestaApi::returnResultado('error', 'Error', $e->getMessage()));
        }
    }

    public function deleteExperienciaLaboral($id)
    {
        try {
            $data = DB::transaction(function () use ($id) {
                $experiencia_laboral = ExperienciaLaboral::find($id);

                $experiencia_laboral->delete();

                return $experiencia_laboral;
            });

            return response()->json(RespuestaApi::returnResultado('success', 'Se elimino con exito', $data));
        } catch (Exception $e) {
            return response()->json(RespuestaApi::returnResultado('error', 'Error', $e->getMessage()));
        }
    }

    public function addCurriculum(Request $request, $id)
    {
        try {
            $data = DB::transaction(function () use ($request, $id) {

                $parametro = DB::table('crm.parametro')
                    ->where('abreviacion', 'NAS')
                    ->first();

                $miDisco = $parametro->nas == true ? 'nas' : 'local';

                $persona = Persona::find($id);

                // Guardar el nombre y la ruta del archivo antes de actualizar
                $nombreAnterior = $persona->identificacion;
                $rutaAnterior = $persona->curriculum;

                // Verificar si se ha enviado un archivo
                if ($request->hasFile("archivo")) {
                    $file = $request->file("archivo");
                    $originalTitulo = $file->getClientOriginalName();
                    $nombreBase = $originalTitulo;

                    // $path = "catalogos/" . $request->nombre . "/archivos";
                    $path = "formularios/formulario_trabaja_con_nosotros/" . $persona->identificacion . "/archivos";

                    $titulo = $nombreBase;

                    $i = 1;

                    // Ajustar el nombre si ya existe un archivo con el mismo nombre
                    while (Storage::disk($miDisco)->exists("$path/$titulo")) {
                        $info = pathinfo($nombreBase);
                        $titulo = $info['filename'] . " ($i)." . $info['extension'];
                        $i++;
                    }

                    // Guardar el archivo en la nueva ruta
                    $path = Storage::disk($miDisco)->putFileAs($path, $file, $titulo);

                    // Eliminar el archivo anterior si existe
                    if ($persona->curriculum) {
                        Storage::disk($miDisco)->delete($persona->curriculum);
                    }


                } else {
                    // Si no se proporciona un nuevo archivo, mantener el archivo existente
                    // $path = "catalogos/" . $request->nombre . "/archivos" . "/" . $persona->titulo_archivo;
                    $path = "formularios/formulario_trabaja_con_nosotros/" . $persona->identificacion . "/archivos" . "/" . $persona->curriculum;
                    $titulo = $persona->curriculum;
                }

                // Actualizar el nombre y la ruta del archivo
                $persona->update([
                    "curriculum" => $path,
                ]);

                // Si el nombre de la persona ha cambiado, mover el archivo a la nueva ruta
                if ($nombreAnterior !== $persona->identificacion && $rutaAnterior) {
                    // $newPath = str_replace("catalogos/$nombreAnterior", "catalogos/{$request->nombre}", $rutaAnterior);
                    $newPath = str_replace("formularios/formulario_trabaja_con_nosotros/$nombreAnterior", "formularios/formulario_trabaja_con_nosotros/{$persona->identificacion}", $rutaAnterior);

                    Storage::disk($miDisco)->move($rutaAnterior, $newPath);

                    // Eliminar el directorio anterior junto con su contenido, elimina despues del nombre de la persona ejemplo catalogos/$persona..... de persona para adelante lo borra
                    $directorioAnterior = dirname($rutaAnterior);
                    Storage::disk($miDisco)->deleteDirectory($directorioAnterior);
                }

                $respuesta = Persona::orderBy('id', 'asc')->get();

                return $respuesta;
            });

            return response()->json(RespuestaApi::returnResultado('success', 'Se actualizo con éxito', $data));
        } catch (Exception $e) {
            return response()->json(RespuestaApi::returnResultado('error', 'Error', $e->getMessage()));
        }
    }

    public function addPostulacion(Request $request)
    {
        try {
            $data = DB::transaction(function () use ($request) {

                $postulacion = Postulacion::create($request->all());

                // Todos los datos que vamos a enviar en el correo
                $object = (object) [
                    'asunto' => 'GRACIAS POR POSTULARTE EN ALMESPANA CIA. LTDA',
                    'id_persona' => $postulacion->id_persona,
                ];

                $enviarCorreo = new EmailController();
                $enviarCorreo->sendEmailGraciasPostulacion($object);

                return $postulacion;
            });

            return response()->json(RespuestaApi::returnResultado('success', 'Se guardo con exito', $data));
        } catch (Exception $e) {
            return response()->json(RespuestaApi::returnResultado('error', 'Error', $e->getMessage()));
        }
    }

    public function listPaises()
    {
        try {
            $data = Pais::where('estado', 'true')->get();

            return response()->json(RespuestaApi::returnResultado('success', 'Se listo con exito', $data));
        } catch (Exception $e) {
            return response()->json(RespuestaApi::returnResultado('error', 'Error', $e->getMessage()));
        }
    }

    public function listProvincias($id_pais)
    {
        try {
            $data = Provincia::where('id_pais', $id_pais)->with('canton')->where('estado', 'true')->get();

            return response()->json(RespuestaApi::returnResultado('success', 'Se listo con exito', $data));
        } catch (Exception $e) {
            return response()->json(RespuestaApi::returnResultado('error', 'Error', $e->getMessage()));
        }
    }

    public function listTiposTelefonos()
    {
        try {
            $data = TipoTelefono::where('estado', 'true')->get();

            return response()->json(RespuestaApi::returnResultado('success', 'Se listo con exito', $data));
        } catch (Exception $e) {
            return response()->json(RespuestaApi::returnResultado('error', 'Error', $e->getMessage()));
        }
    }

    public function listCiudades()
    {
        try {
            // para que aparezca las nuevas ciudades de los almacenes que esten funcionando actualmente
            // hay que modificar en la tabla public.almacen la columna alm_nombre_tmp2 con la ciudad del almacen
            // los NULL para los almacenes que estan fuera de servicio o ya no estan funcionando
            $data = DB::select("select distinct alm.alm_nombre_tmp2 as nombre
                                from public.almacen alm
                                where alm.alm_nombre_tmp2 is not null;");

            return response()->json(RespuestaApi::returnResultado('success', 'Se listo con exito', $data));
        } catch (Exception $e) {
            return response()->json(RespuestaApi::returnResultado('error', 'Error', $e->getMessage()));
        }
    }

    public function listCargos()
    {
        try {
            $data = DB::select("select *
                                from rrhh.cargos c
                                where c.estado = true;");

            return response()->json(RespuestaApi::returnResultado('success', 'Se listo con exito', $data));
        } catch (Exception $e) {
            return response()->json(RespuestaApi::returnResultado('error', 'Error', $e->getMessage()));
        }
    }

    public function listParentescos()
    {
        try {
            $data = Parentesco::where('estado', 'true')->get();

            return response()->json(RespuestaApi::returnResultado('success', 'Se listo con exito', $data));
        } catch (Exception $e) {
            return response()->json(RespuestaApi::returnResultado('error', 'Error', $e->getMessage()));
        }
    }

    // este endPoint ya no lo ocupo xq se cambio la estructura del formulario
    public function addFormularioTrabajaConNosotros(Request $request)
    {
        try {
            $data = DB::transaction(function () use ($request) {

                $file = $request->file("archivo");
                $titulo = $file->getClientOriginalName();

                // Fecha actual
                $fechaActual = Carbon::now();

                // Formatear la fecha en formato deseado
                // $fechaFormateada = $fechaActual->format('Y-m-d H-i-s');

                // Reemplazar los dos puntos por un guion medio (NO permite windows guardar con los : , por eso se le pone el - )
                $fecha_actual = str_replace(':', '-', $fechaActual);

                // $path = Storage::putFile("archivos", $request->file("archivo")); //se va a guardar dentro de la CARPETA archivos
                $parametro = DB::table('crm.parametro')
                    ->where('abreviacion', 'NAS')
                    ->first();

                if ($parametro->nas == true) {
                    $path = Storage::disk('nas')->putFileAs("formularios/formulario_trabaja_con_nosotros/" . $request->identificacion . "/archivos", $file, $request->identificacion . '-' . $fecha_actual . '-' . $titulo); // guarda en el nas con el nombre original del archivo
                } else {
                    $path = Storage::disk('local')->putFileAs("formularios/formulario_trabaja_con_nosotros/" . $request->identificacion . "/archivos", $file, $request->identificacion . '-' . $fecha_actual . '-' . $titulo); // guarda en el nas con el nombre original del archivo
                }

                $request->request->add(["archivo" => $path]); //Aqui obtenemos la ruta del archivo en la que se encuentra

                $mac = "0.0.0.0";

                // Guardar la solicitud de crédito web maquetado o a mano
                $respuesta = SolicitudesCreditosWeb::create([
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
                    'aceptar_terminos_condiciones' => false, // false, porque el formulario no necesita los terminos y condiciones
                    'confirmacion_terminos_condiciones' => false, // por defecto false, true se hace cuando el usuario o cliente acepte o confirme desde el email
                    'latitud' => $request->latitud,
                    'longitud' => $request->longitud,
                    'formulario_tipo' => $request->formulario_tipo,
                    'archivo' => $path,
                ]);

                return $respuesta;
            });

            return response()->json(RespuestaApi::returnResultado('success', 'Se guardo con éxito', $data));

        } catch (Exception $e) {
            return response()->json(RespuestaApi::returnResultado('error', 'Error', $e->getMessage()));
        }
    }

}

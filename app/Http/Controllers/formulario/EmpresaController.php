<?php

namespace App\Http\Controllers\formulario;

use App\Http\Controllers\Controller;
use App\Http\Resources\Funciones;
use App\Http\Resources\RespuestaApi;
use App\Models\crm\Empresa;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EmpresaController extends Controller
{
    // Para la lista del combo box del formulario
    public function listAllEmpresas()
    {
        try {
            $data = Empresa::where('estado', true)->get();

            return response()->json(RespuestaApi::returnResultado('success', 'Se listo con éxito', $data));
        } catch (Exception $e) {
            return response()->json(RespuestaApi::returnResultado('error', 'Error', $e));
        }
    }

    // Para el crud de la empresa
    public function listEmpresas()
    {
        try {
            $data = Empresa::get();

            // Especificar las propiedades que representan fechas en tu objeto
            $dateFields = ['created_at', 'updated_at'];
            // Utilizar la función map para transformar y obtener una nueva colección
            $data->map(function ($item) use ($dateFields) {
                $funciones = new Funciones();
                $funciones->formatoFechaItem($item, $dateFields);
                return $item;
            });

            return response()->json(RespuestaApi::returnResultado('success', 'Se listo con éxito', $data));
        } catch (Exception $e) {
            return response()->json(RespuestaApi::returnResultado('error', 'Error', $e));
        }
    }

    public function addEmpresa(Request $request)
    {
        try {
            $respuesta = DB::transaction(function () use ($request) {

                $file = $request->file("archivo");
                $titulo = $file->getClientOriginalName();

                $parametro = DB::table('crm.parametro')
                    ->where('abreviacion', 'NAS')
                    ->first();

                $miDisco = $parametro->nas == true ? 'nas' : 'local';

                $path = Storage::disk($miDisco)->putFileAs("catalogos/" . $request->nombre . "/archivos", $file, $titulo); // guarda en el nas con el nombre original del archivo

                $request->request->add(["archivo" => $path]); //Aqui obtenemos la ruta del archivo en la que se encuentra

                Empresa::create([
                    'nombre' => $request->nombre,
                    'estado' => true,
                    // 'abreviacion_parametro' => $request->abreviacion_parametro,
                    "archivo" => $path,
                    "titulo_archivo" => $titulo,
                ]);

                $data = Empresa::get();

                // Especificar las propiedades que representan fechas en tu objeto
                $dateFields = ['created_at', 'updated_at'];
                // Utilizar la función map para transformar y obtener una nueva colección
                $data->map(function ($item) use ($dateFields) {
                    $funciones = new Funciones();
                    $funciones->formatoFechaItem($item, $dateFields);
                    return $item;
                });

                return $data;
            });

            return response()->json(RespuestaApi::returnResultado('success', 'Se guardo con éxito', $respuesta));

        } catch (Exception $e) {
            return response()->json(RespuestaApi::returnResultado('error', 'Error', $e->getMessage()));
        }
    }

    public function deleteEmpresa($id)
    {
        try {
            $data = DB::transaction(function () use ($id) {

                $empresa = Empresa::find($id);

                $parametro = DB::table('crm.parametro')
                    ->where('abreviacion', 'NAS')
                    ->first();

                $miDisco = $parametro->nas == true ? 'nas' : 'local';

                Storage::disk($miDisco)->delete($empresa->archivo); //Aqui pasa la rta para eliminarlo

                $empresa->delete();

                $empresa = Empresa::get();

                return $empresa;
            });

            return response()->json(RespuestaApi::returnResultado('success', 'Se elimino con éxito', $data));
        } catch (Exception $e) {
            return response()->json(RespuestaApi::returnResultado('error', 'Error', $e));
        }
    }

    public function editEmpresa(Request $request, $id)
    {
        try {
            $data = DB::transaction(function () use ($request, $id) {

                $parametro = DB::table('crm.parametro')
                    ->where('abreviacion', 'NAS')
                    ->first();

                $miDisco = $parametro->nas == true ? 'nas' : 'local';

                $empresa = Empresa::find($id);

                // Guardar el nombre y la ruta del archivo antes de actualizar
                $nombreAnterior = $empresa->nombre;
                $rutaAnterior = $empresa->archivo;

                // Verificar si se ha enviado un archivo
                if ($request->hasFile("archivo")) {
                    $file = $request->file("archivo");
                    $originalTitulo = $file->getClientOriginalName();
                    $nombreBase = $originalTitulo;

                    $path = "catalogos/" . $request->nombre . "/archivos";

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
                    if ($empresa->archivo) {
                        Storage::disk($miDisco)->delete($empresa->archivo);
                    }


                } else {
                    // Si no se proporciona un nuevo archivo, mantener el archivo existente
                    $path = "catalogos/" . $request->nombre . "/archivos" . "/" . $empresa->titulo_archivo;
                    $titulo = $empresa->titulo_archivo;
                }

                // Actualizar el nombre y la ruta del archivo
                $empresa->update([
                    "nombre" => $request->nombre,
                    "estado" => $request->estado,
                    "archivo" => $path,
                    "titulo_archivo" => $titulo,
                ]);

                // Si el nombre de la empresa ha cambiado, mover el archivo a la nueva ruta
                if ($nombreAnterior !== $request->nombre && $rutaAnterior) {
                    $newPath = str_replace("catalogos/$nombreAnterior", "catalogos/{$request->nombre}", $rutaAnterior);
                    Storage::disk($miDisco)->move($rutaAnterior, $newPath);

                    // Eliminar el directorio anterior junto con su contenido, elimina despues del nombre de la empresa ejemplo catalogos/$empresa..... de empresa para adelante lo borra
                    $directorioAnterior = dirname($rutaAnterior);
                    Storage::disk($miDisco)->deleteDirectory($directorioAnterior);
                }

                $respuesta = Empresa::orderBy('id', 'asc')->get();

                /// Especificar las propiedades que representan fechas en tu objeto
                $dateFields = ['created_at', 'updated_at'];
                // Utilizar la función map para transformar y obtener una nueva colección
                $respuesta->map(function ($item) use ($dateFields) {
                    $funciones = new Funciones();
                    $funciones->formatoFechaItem($item, $dateFields);
                    return $item;
                });

                return $respuesta;
            });

            return response()->json(RespuestaApi::returnResultado('success', 'Se actualizo con éxito', $data));
        } catch (Exception $e) {
            return response()->json(RespuestaApi::returnResultado('error', 'Error', $e->getMessage()));
        }
    }

}

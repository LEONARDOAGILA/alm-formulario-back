<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\RespuestaApi;
use App\Models\crm\Departamento;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartamentoController extends Controller
{

    public function listAllUser()
    {
        try {
            $departamentos = Departamento::with('users')->where('estado', true)->get();

            return response()->json(RespuestaApi::returnResultado('success', 'Se listo con éxito', $departamentos));
        } catch (\Throwable $e) {
            return response()->json(RespuestaApi::returnResultado('error', 'Error', $e));
        }
    }

    public function allDepartamento()
    {
        try {
            $departamentos = Departamento::orderBy("id", "desc")->where('estado', true)->get();

            return response()->json(RespuestaApi::returnResultado('success', 'Se listo con éxito', $departamentos));
        } catch (Exception $e) {
            return response()->json(RespuestaApi::returnResultado('error', 'Error', $e));
        }
    }

    public function listDepartamento()
    {
        try {
            $departamentos = Departamento::orderBy("id", "desc")->get();

            return response()->json(RespuestaApi::returnResultado('success', 'Se listo con éxito', $departamentos));
        } catch (Exception $e) {
            return response()->json(RespuestaApi::returnResultado('error', 'Error', $e));
        }
    }

    public function addDepartamento(Request $request)
    {
        try {
            $data = DB::transaction(function () use ($request) {
                Departamento::create($request->all());

                return Departamento::orderBy('estado', 'DESC')->orderBy('id', 'DESC')->get();
            });

            return response()->json(RespuestaApi::returnResultado('success', 'Se guardo con éxito', $data));
        } catch (Exception $e) {
            return response()->json(RespuestaApi::returnResultado('error', 'Error', $e));
        }
    }

    public function editDepartamento(Request $request, $id)
    {
        try {
            $data = DB::transaction(function () use ($request, $id) {
                $departamento = Departamento::findOrFail($id);

                $departamento->update($request->all());

                return Departamento::where('id', $id)->first();
            });

            return response()->json(RespuestaApi::returnResultado('success', 'Se actualizo con éxito', $data));
        } catch (Exception $e) {
            return response()->json(RespuestaApi::returnResultado('error', 'Error', $e));
        }
    }

    public function deleteDepartamento($id)
    {
        try {
            $data = DB::transaction(function () use ($id) {
                $departamento = Departamento::findOrFail($id);

                $departamento->delete();

                return $departamento;
            });

            return response()->json(RespuestaApi::returnResultado('success', 'Se elimino con éxito', $data));
        } catch (Exception $e) {
            return response()->json(RespuestaApi::returnResultado('error', 'Error', $e));
        }
    }
}
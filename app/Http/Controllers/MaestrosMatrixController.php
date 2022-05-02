<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Stmt\TryCatch;

class MaestrosMatrixController extends Controller
{
    public function agregar(Request $request)
    {
        $request->validate([
            "data" => "required",
            "permisos" => "required"
        ]);

        if ($request->permisos["Tabpgr"] == 'on') {

                $data = $request->data;
                $data["Fecha_data"] = date('Y-m-d');
                $data["Hora_data"] = date('H:i:s');
                $data["Medico"] = explode('_', $request->permisos["Tabtab"])[0];
                $data["Seguridad"] = 'C-' . Auth::user()->Codigo;
            try {
                $inserccion = DB::connection('mysql')
                    ->table($request->permisos["Tabtab"])->insert($data);
                return response()->json([
                    'data' => $inserccion,
                    'message' => 'Guardado Correctamente',
                    'res' => 'true'
                ]);
            }catch (Exception $e){
                return response()->json([
                    'data' => false,
                    'message' => 'Error no se pudo guardar el dato',
                    'res' => 'false'
                ]);
            }
        } else {
            return response()->json([
                'data' => false,
                'message' => 'No tiene permiso',
                'res' => 'false'
            ]);
        }
    }

    public function update(Request $request)
    {

        $request->validate([
            "data" => "required",
            "permisos" => "required",
            "row" => "required"
        ]);

        try {
            $actualizacion = DB::connection('mysql')
                ->table($request->permisos["Tabtab"])->where("id", $request->row)->update($request->data);
            return response()->json([
                'data' => $actualizacion,
                'message' => 'Guardado Correctamente',
                'res' => 'true'
            ]);
        }catch (Exception $e){
            return response()->json([
                'data' => false,
                'message' => 'Error no se pudo guardar el dato',
                'res' => 'false'
            ]);
        }
    }

    public function delete(Request $request)
    {
        $request->validate([
            "permisos" => "required",
            "row" => "required"
        ]);

        if ($request->permisos["Tabcam"] == '*') {
            try {
                $delete = DB::connection('mysql')
                    ->table($request->permisos["Tabtab"])->where("id", $request->row)->delete();
                return response()->json([
                    'data' => $delete,
                    'message' => 'Eliminado correctamente',
                    'res' => 'true'
                ]);
            }catch (Exception $e){
                return response()->json([
                    'data' => false,
                    'message' => 'Error no se pudo guardar el dato',
                    'res' => 'false'
                ]);
            }
        } else {
            return response()->json([
                'data' => false,
                'message' => 'No tiene permiso',
                'res' => 'false'
            ]);
        }
    }

    public function permisos()
    {
        $data = DB::table('root_000105')->where('Tabusu', Auth::user()->Codigo)->where('Tabest', 'on')->get(['Tabtab', 'Tabcvi', 'Tabcam', 'Tabpgr', 'Tabopc']);

        return response()->json([
            'data' => $data,
            'message' => 'Retorno de datos correctos',
            'res' => 'true'
        ]);
    }

    public function datos(Request $request)
    {
        $request->validate([
            "tabla" => "required|string"
        ]);

        // permisos
        $permisos = DB::table('root_000105')->where('Tabusu', Auth::user()->Codigo)->where('Tabtab', $request->tabla)->first(['Tabtab', 'Tabcvi', 'Tabcam', 'Tabpgr', 'Tabopc']);

        // detalle
        $tabla_consecutivo = explode('_', $request->tabla);
        $detalle = DB::table('det_formulario')->where('medico', $tabla_consecutivo[0])->where('codigo', $tabla_consecutivo[1])->where('activo', 'A')->get(['medico', 'codigo', 'campo', 'descripcion', 'tipo', 'posicion', 'comentarios']);

        // descripcion
        $descripciones = DB::table('root_000030')->where('Dic_Usuario', $tabla_consecutivo[0])->where('Dic_Formulario', $tabla_consecutivo[1])->get(['Dic_Campo', 'Dic_Descripcion']);

        // data
        $data = DB::table($request->tabla)->get();

        return response()->json([
            'data' => [
                'permisos' => $permisos,
                'detalles' => $detalle,
                'descripciones' => $descripciones,
                'data' => $data
            ],
            'message' => 'Retorno de datos correctos',
            'res' => 'true'
        ]);
    }

    public function relaciones(Request $request)
    {
        $request->validate([
            "medico" => "required|string",
            "codigo" => "required|string",
            "comentarios" => "required|string"
        ]);

        $destructur = explode("-", $request->comentarios);
        if (intval($destructur[0]) + 2 == sizeof($destructur)) {
            //selecciono las columnas
            $columns = DB::connection('mysql')->table('det_formulario')
                ->where('medico', $request->medico)
                ->where('codigo', $destructur[1])
                ->whereIn('campo', array_slice($destructur, 2))
                ->get('descripcion');

            $keys = [];
            foreach ($columns as $value) {
                $keys[] = $value->descripcion;
            }

            $consult = DB::connection('mysql')->table($request->medico . '_' . $destructur[1])->groupBy($keys[0])->get($keys);
            $key2 = [];
            foreach ($consult as $select) {
                $key2[] = [
                    'key' => current($select),
                    'text' => implode('-', array_values((array)$select)),
                    'value' => current($select)
                ];
            }
        } else {
            $columns = DB::connection('mysql')->table('det_formulario')
                ->where('medico', $destructur[1])
                ->where('codigo', $destructur[2])
                ->whereIn('campo', array_slice($destructur, 3))
                ->get('descripcion');

            $keys = [];

            foreach ($columns as $value) {
                $keys[] = $value->descripcion;
            }

            $consult = DB::connection('mysql')->table($destructur[1] . '_' . $destructur[2])->groupBy($keys[0])->get($keys);

            $key2 = [];
            foreach ($consult as $select) {
                $key2[] = [
                    'key' => current($select),
                    'text' => implode('-', array_values((array)$select)),
                    'value' => current($select)
                ];
            }

        }
        return response()->json(['data' => $key2,
            'message' => 'Se encontro unos opciones',
            'res' => true]);
    }

    public
    function selects(Request $request)
    {

        $request->validate([
            "medico" => "required|string",
            "codigo" => "required|string",
            "comentarios" => "required|string"
        ]);

        $codigo = explode("-", $request->comentarios);

        $selects = DB::connection('mysql')->table('det_selecciones')
            ->where('medico', $request->medico)
            ->where('codigo', $codigo)
            ->where('activo', 'A')
            ->get(['subcodigo', 'descripcion']);

        $key = [];
        foreach ($selects as $select) {
            $key[] = [
                'key' => $select->subcodigo . "-" . $select->descripcion,
                'text' => $select->subcodigo . "-" . $select->descripcion,
                'value' => $select->subcodigo . "-" . $select->descripcion
            ];
        }

        return response()->json([
            'data' => $key,
            'message' => 'Se encontro unos opciones',
            'res' => true
        ]);
    }
}

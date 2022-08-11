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
        $data = DB::table('root_000105')->where('Tabusu', Auth::user()->Codigo)->where('Tabest', 'on')
        ->orderBy('Tabtab', 'ASC')->get(['Tabtab', 'Tabcvi', 'Tabcam', 'Tabpgr', 'Tabopc']);

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
        $detalle = DB::table('det_formulario')->where('medico', $tabla_consecutivo[0])
        ->where('codigo', $tabla_consecutivo[1])
        ->where('activo', 'A')
        ->get(['medico', 'codigo', 'campo', 'descripcion', 'tipo', 'posicion', 'comentarios']);
        $descripciones = DB::table('root_000030')->where('Dic_Usuario', $tabla_consecutivo[0])->where('Dic_Formulario', $tabla_consecutivo[1])->get(['Dic_Campo', 'Dic_Descripcion']);
        
        $data = DB::table($request->tabla);
        if($request->condicionFilter && $request->filterText && $request->valueFilter){
            if($request->condicionFilter == 'like'){
                $data->where($request->valueFilter, 'LIKE', '%'.$request->filterText.'%');
            }else{
                $data->where($request->valueFilter, $request->condicionFilter, $request->filterText);
            }
        }

        $data = $data->paginate(10);

        foreach($data as $elem){
            $position = 0;
            $position_data_start = 0;
            foreach($elem as $key => $value){
                if($position_data_start >= 3){
                    $det = $this->searchDescripcion($detalle, $key);
                    if($det){
                        if($det->tipo == "18" || $det->tipo == "9"){
                            $destructur = explode("-", $det->comentarios);
                            $relation = $this->getRelation($destructur, $tabla_consecutivo[0], $value);    
                            $elem->{$key} = $relation;       
                        }
                        $position ++;
                    }
                }
                $position_data_start++;
            }
        }

        $keyExists = DB::select(
            DB::raw(
                'SHOW KEYS
                FROM '.$request->tabla           
            )
        );
        foreach($detalle as $value){
            if($this->isIndex($keyExists, $value->descripcion)){
                $value->isIndex = 1;
            }else{
                $value->isIndex = 0;
            }
        }
        
        return response()->json([
            'data' => [
                'permisos' => $permisos,
                'detalles' => $detalle,
                'descripciones' => $descripciones,
                'datas' => $data
            ],
            'message' => 'Retorno de datos correctos',
            'res' => 'true'
        ]);
    }

    public function isIndex($keyExists, $column){
        foreach($keyExists as $key){
            if($key->Column_name == $column){
                return true;
            }
        }
        return false;
    }

    public function searchDescripcion($detalle, $key){
        $search = $detalle->where('descripcion', $key)->first();
        return $search;
    }

    public function getRelation($destructur, $tabla, $value){
        $num_datos = count($destructur);
        $campos = array_slice($destructur, 1);
        $campos_dos = array_slice($destructur, 2);

        if(intval($destructur[0]) + 2 == sizeof($destructur)){
            $select_campos_tabla = DB::table('det_formulario')
                                    ->where('medico', $tabla)
                                    ->where('codigo', $destructur[1])
                                    ->whereIn('campo', $campos)
                                    ->get();
        
            if(count($select_campos_tabla)){
                $r=0;
                $ppal = $select_campos_tabla[0]->descripcion;
                $campos_select = "";
    
                foreach($select_campos_tabla as $elem){
                    $campos_select .= $elem->descripcion.",'-',";
                }
                $campos_select = substr ($campos_select, 0, strlen($campos_select) - 5);
                $relation = DB::table($tabla."_".$destructur[1])
                                ->select("$ppal as ppal", DB::raw("CONCAT($campos_select) as seleccionado"))
                                ->where($ppal, $value)
                                ->first();


                return $relation ? $relation->seleccionado : "";
            } 
        }else{
            $select_campos_tabla = DB::table('det_formulario')
                                    ->where('medico', $destructur[1])
                                    ->where('codigo', $destructur[2])
                                    ->whereIn('campo', $campos_dos)
                                    ->get();

            if(count($select_campos_tabla)){
                $r=0;
                $ppal = $select_campos_tabla[0]->descripcion;
                $campos_select = "";
    
                foreach($select_campos_tabla as $elem){
                    $campos_select .= $elem->descripcion.",'-',";
                }
                $campos_select = substr ($campos_select, 0, strlen($campos_select) - 5);

                $relation = DB::table($destructur[1]."_".$destructur[2])
                                ->select("$ppal as ppal", DB::raw("CONCAT($campos_select) as seleccionado"))
                                ->where($ppal, $value)
                                ->first();
                
                return $relation ? $relation->seleccionado : "";
            } 
        }
        return "";
    }
    
    public function getKeys($destructur, $medico){
        $keys = [];
        if (intval($destructur[0]) + 2 == sizeof($destructur)) {
            $columns = DB::table('det_formulario')->where('medico', $medico)
                ->where('codigo', $destructur[1])
                ->whereIn('campo', array_slice($destructur, 2))
                ->get('descripcion');
        }else{
            $columns = DB::connection('mysql')->table('det_formulario')
                ->where('medico', $destructur[1])
                ->where('codigo', $destructur[2])
                ->whereIn('campo', array_slice($destructur, 3))
                ->get('descripcion');
        }

        foreach ($columns as $value) {
            $keys[] = $value->descripcion;
        }

        return $keys;
    }

    public function getKeys2($destructur, $medico, $keys){
        $key2 = [];
        if (intval($destructur[0]) + 2 == sizeof($destructur)) {
            $consult = DB::connection('mysql')->table($medico . '_' . $destructur[1])->groupBy($keys[0])->get($keys);
        }else{
            $consult = DB::connection('mysql')->table($destructur[1] . '_' . $destructur[2])->groupBy($keys[0])->get($keys);
        }

        foreach ($consult as $select) {
            $key2[] = [
                'key' => current($select),
                'text' => implode('-', array_values((array)$select)),
                'value' => current($select)
            ];
        }
        return $key2;
    }


    public function relaciones(Request $request)
    {
        $request->validate([
            "medico" => "required|string",
            "codigo" => "required|string",
            "comentarios" => "required|string"
        ]);

        $destructur = explode("-", $request->comentarios);
        
        $keys = $this->getKeys($destructur, $request->medico);
        $key2 = $this->getKeys2($destructur, $request->medico, $keys);
        
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

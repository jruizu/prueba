<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PersonasController extends Controller
{
    public function search(Request $request)
    {


        //Personalizacion de los mensajes de validacion que trae por defecto el framework
        $messages = [
            'regex' => 'El campo :attribute no cumple con el formato requerido',
            'max'=> 'El campo :attribute no debe ser mayor a :max',
            'min'=> 'El campo :attribute no debe ser menor a :min',
        ];
        $customAttributes = [
            'name' => 'nombre',
            'percent' => 'porcentaje',
        ];

        //Validacion de los campos que recibimos del cliente, de no cumplirlo retorna error con codigo 422
        $request->validate([
            'name'=>'required|string|regex:/^[\sa-zA-Z]+$/',
            'percent'=>'required|numeric|min:5|max:100',  
        ], $messages, $customAttributes);






        //convertimos las cadenas a miniscula para trabajar mas comodo sin distinguir capitalcase
        $str_name = strtolower($request->name);

         //Obtenemos todos los registros del diccionario en la DB a traves del modelo
        $personajes = \App\Diccionario::all();

        //Convertimos la cadena de texto en un array con el fin de generar formatos Apellidos nombres
        $array_name =  explode(' ', $str_name);
       
        $array_name_inverter =  $array_name;

        for ($i = 0; $i < count($array_name); $i++) {
            #Si el arreglo tiene mas de 2 elementos eliminaré dos primeros para moverlos de ultimo 
            #generando un nuevo arreglo algo invertido que cumpla con el formato apellidos nombre
            if (count($array_name) > 2) {
                if ($i < 2) {
                    unset($array_name_inverter[$i]);
                    array_push($array_name_inverter, $array_name[$i]);
                }
            }
            //en caso de que solo tenga dos elementos invertimos la posicion de los elementos
            else {
                array_unshift($array_name_inverter, $array_name[$i]);
            }
        }


        $str_name_inverter = strtolower(implode(' ', $array_name_inverter));

        $results = collect([]);
        #TODA CONSONANTE ACOMPAÑADA DE LA VOCAL E/I Suenan igual
        
        foreach ($personajes as $personaje) {
            //convertimos a minuscula el nombre que viene de la BD para trabajar en un mismo ambito
            $name_compare = strtolower($personaje->nombre);
            
            //hacemos las comparaciones usando similar_text para los casos nombre apellido y apellido nombre
           
            ### antes de usar similar text, pasamos nuestros string generados asi como tambien el que traemos
            ### de la base de datos por una funcion declarada en la clase llamada transform
            ### esta funcion mediante expresiones regulares nos trasnformara las similitudes foneticas en los nombres
            ## ej : Jesus = Jezus || Biviana = Bibiana || Kamilo Koronel = Camilo Coronel
            similar_text($this->transform($str_name), $this->transform($name_compare), $percent_name);
            similar_text($this->transform($str_name_inverter), $this->transform($name_compare), $percent_name_inverter);

            //buscamos cual obtuvo mayor coincidencia con respecto al parametro que recibimos del cliente 
            //y lo almacenamos en una coleccion de datos que declaramos con anterioridad llamada results
            if ($percent_name >= $request->percent) {
                $results[] = [
                'personaje' => $personaje, 
                'percent_result' => number_format($percent_name)];
            } else if ($percent_name_inverter >= $request->percent) {
                $results[] = [
                'personaje' => $personaje, 
                'percent_result' => number_format($percent_name_inverter)];
            }
        }

        //sino encontro resultados
        if(empty($results[0])){
            return response ([
                'data'=>$results,
                'nombre_buscado'=>$request->name, 
                'porcentaje_buscado'=>$request->percent,
                'estado_ejecucion'=>"No se encontraron coincidendias mayores al $request->percent% en los registros", 200]);
        }

        //en caso de que tengamos resultados almacenados en nuestea coleccion ordenamos de mayor
        //a menor por el resultado de coincidencia obtenido
        $results_order = $results->sortByDesc('percent_result');
        $results = $results_order->values()->all();


        
        
        if($results){
           return response ([
            'data'=>$results,
            'nombre_buscado'=>$request->name, 
            'porcentaje_buscado'=>$request->percent,
            'estado_ejecucion'=>"Registros encontrados, coincidencias mayores o igual al $request->percent% en los registros", 200]);

        }
       
    }




    public function transform($str){
       
       /* Creamos un arreglo de datos con las expresiones regulares que corresponderian a 
        las similtudes foneticas donde exp es la expresion regular con la cual hará match
        exp_to_rep sera la expresion regular que buscara y reemplazara por nuetro "rep"
        ej: para el indice 0  $array_pattern[0]['exp']
        probamos 'Geremias Gomez' mediante la expresion buscará la g y si esta acompañada por una
        e ó i sonara igual que la letra jota asi que para encontrar una coincidencia exacta si 
        el usuario escribio Jeremias y en la base de datos esta Geremias hara Match al pasar ambos
        por esta función 
       */
        $array_pattern = [  
            ["exp"=>"/g[ei]+/", "exp_to_rep"=>"/g/",  "rep"=>'j'],
            ["exp"=>"/v[aeiou]+/", "exp_to_rep"=>"/v/","rep"=>'b'],
            ["exp"=>"/[z]+/", "exp_to_rep"=>"/z/","rep"=>'s'],
            ["exp"=>"/c[ei]+/", "exp_to_rep"=>"/c/","rep"=>'s'],
            ["exp"=>"/k[aou]+/", "exp_to_rep"=>"/k/","rep"=>'c'],
            ["exp"=>"/qu[ei]+/", "exp_to_rep"=>"/qu/","rep"=>'k'],
            ["exp"=>"/^u[aeio]+/", "exp_to_rep"=>"/^u/","rep"=>'w'],
            ["exp"=>"/y+$/", "exp_to_rep"=>"/y+$/","rep"=>'i'],
        ];
       
        //Recorremos las expresiones regulares posibles y las buscamos con preg_match_all
        for($i=0; $i<count($array_pattern); $i++){
            if(preg_match_all($array_pattern[$i]['exp'], $str)){
                //En caso de que encontremos coincidentias las reemplazamos para obtener coincidencias al pasarlo por similar_text
                $str =  preg_replace($array_pattern[$i]['exp_to_rep'],$array_pattern[$i]['rep'], $str); 
            }
        }
    
        return $str;
    }
}

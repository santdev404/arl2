<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Models\Category;

class CategoryController extends Controller
{

    public function __construct(){
        $this->middleware('api.auth', ['except' => ['index','show']]);
    }

    public function index(){
        $categories = Category::all();
        return response()->json([
            'code' => 200,
            'status' => 'success',
            'categories' => $categories,
        ]);
    }

    public function show($id){
        $category = Category::find($id);

        if(is_object($category)){
            $data= [
                'code' => 200,
                'status' => 'success',
                'category' => $category,
            ];
        }else{
            $data= [
                'code' => 404,
                'status' => 'error',
                'message' => 'La categoria no existe',
            ];
        }
        return response()->json($data, $data['code']);
    }

    public function store(Request $request){
        //Recoger los datos por post
        $json = $request->input('json', null);
        $params_array = json_decode($json, true); //Convierte a array php

        if(!empty($params_array)){

            //Validar los datos
            $validate = \Validator::make($params_array, [
                'name' => 'required'
            ]);

            if($validate->fails()){
                $data = [
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'no se ha guardado la categoria'
                ];
            }else{
                //Guardar la categoria y devolver el resultado
                $category = new Category();
                $category->name = $params_array['name'];
                $category->save();

                $data = [
                    'code' => 200,
                    'status' => 'success',
                    'category' => $category
                ];

            }
        }else{
            $data = [
                'code' => 400,
                'status' => 'error',
                'message' => 'No has enviado ningura categoria'
            ];
        }

        return response()->json($data, $data['code']);
    }


    public function update(Request $request, $id){
        //Recoger los datos que llegan por post
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);

        if(!empty($params_array)){
            //Validar datos
            $validate = \Validator::make($params_array, [
                'name' => 'required'
            ]);

            //Quitar lo que no se actualiza
            unset($params_array['id']);
            unset($params_array['created_at']);

            //Actualizar el registro (Categoria)
            $category = Category::where('id', $id)->update($params_array);

            $data = [
                'code' => 200,
                'status' => 'success',
                'category' => $params_array
            ];

            //Regresar los datos
        }else{
            $data = [
                'code' => 400,
                'status' => 'error',
                'message' => 'No has enviado ningura categoria para actualizar'
            ];
        }

        return response()->json($data, $data['code']);

    }
}

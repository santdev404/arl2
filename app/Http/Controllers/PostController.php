<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Models\Post;

use App\Helpers\JwtAuth;


class PostController extends Controller
{
    public function __construct(){
        //Pedir header y token del usuario en cada autenticacion
        $this->middleware('api.auth', ['except' => ['index','show']]);
    }


    public function index(){
        $posts = Post::all()->load('category');
        return response()->json([
            'code' => 200,
            'status' => 'success',
            'posts' => $posts
        ], 200);
    }

    public function show($id){
        $post = Post::find($id)->load('category');
        if(is_object($post)){
            $data = [
                'code' => 200,
                'status' => 'success',
                'post' => $post
            ];
        }else{
            $data = [
                'code' => 404,
                'status' => 'error',
                'message' => 'la entrada no existe'
            ];

        }

        return response()->json($data, $data['code']);

    }

    public function store(Request $request){
        //Recoger datos por post
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);

        if(!empty($params_array)){
            //Conseguir usuario indentificado
            $jwtAuth = new \App\Helpers\JwtAuth();
            $token = $request->header('Authorization', null);
            $user = $jwtAuth->checkToken($token, true);

            //Validar los datos
            $validate = \Validator::make($params_array, [
                'title' => 'required',
                'content' => 'required',
                'category_id' => 'required',
                'image' => 'required'

            ]);

            if($validate->fails()){
                $data = [
                    'code'=> 400,
                    'status' => 'error',
                    'message' => 'Faltan datos'
                ];
            }else{
                $post = new Post();
                $post->user_id = $user->sub;
                $post->category_id = $params->category_id;
                $post->title = $params->title;
                $post->content = $params->content;
                $post->image = $params->image;
                $post->save();

                //Guardar el post
                $data = [
                    'code'=> 400,
                    'status' => 'success',
                    'post' => $post
                ];
            }

            
        }else{
            $data = [
                'code'=> 400,
                'status' => 'error',
                'message' => 'Envia datos correctamente'
            ];
        }



        //Devolver la respuesta
        return response()->json($data, $data['code']);
    }


    public function update(Request $request, $id){
        //Recoger datos por post
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);

        //Datos para devolver
        $data = [
            'code' => 400,
            'status' => 'error',
            'message' => 'Datos enviados incorrectamente'
        ];

        if(!empty($params_array)){
            //Validar los datos
            $validate = \Validator::make($params_array, [
                'title' => 'required',
                'content' => 'required',
                'category_id' => 'required'
            ]);

            if($validate->fails()){
                $data['message'] = $validate->errors();
                return reponse()->json($data, $data['code']);
            }

            //Eliminar lo que no se actualiza
            unset($params_array['id']);
            unset($params_array['user_id']);
            unset($params_array['created_at']);
            unset($params_array['user']);

            //Actualizar el resgistro
            //Get, first obtiene los objetos actualizados solo funciona con QueryBuilder
            $post = Post::where('id', $id)->updateOrCreate($params_array);

            //Regresar data
            $data = [
                'code' => 200,
                'status' => 'success',
                'post' => $post,
                'change' => $params_array
            ];
            
        }
        return response()->json($data, $data['code']);

    }

    public function destroy($id, Request $request){
        //Conseguir el post
        $post = Post::find($id);

        if(!empty($post)){
            //Borrarlo
            $post->delete();

            //Devolver data
            $data = [
                'code' => 200,
                'status' => 'success',
                'post' => $post
            ];
        }else{
            $data = [
                'code' => 400,
                'status' => 'error',
                'message' => 'Error not post found'
            ];
        }

        

        return response()->json($data, $data['code']);
    }

}

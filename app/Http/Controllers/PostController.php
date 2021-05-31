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
        $this->middleware('api.auth', ['except' => [
                'index',
                'show',
                'getImage',
                'getPostsByCategory',
                'getPostByUser'
            ]]);
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
            $user = $this->getIdentity($request);

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

            //Conseguir usuario indentificado
            $user = $this->getIdentity($request);

            
            //Get, first obtiene los objetos actualizados solo funciona con QueryBuilder

            //Buscar el registro
            $post = Post::where('id', $id)
                        ->where('user_id', $user->sub)->first();

            if(!empty($post) && is_object($post)){

                //Actualizar el resgistro
                $post->update($params_array);

                $data = [
                    'code' => 200,
                    'status' => 'success',
                    'post' => $post,
                    'change' => $params_array
                ];
            }


            // $where = [
            //     'id' => $id,
            //     'user_id' => $user->sub
            // ];
            // $post = Post::updateOrCreate($where, $params_array);

            //Regresar data

            
        }
        return response()->json($data, $data['code']);

    }

    public function destroy($id, Request $request){

        $user = $this->getIdentity($request);

        //Conseguir el post
        $post = Post::where('id', $id)
                ->where('user_id', $user->sub)->first();

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


    public function upload(Request $request){
        //Recoger la imagen
        $image = $request->file('file0');

        //Validar la imagen
        $validate = \Validator::make($request->all(),[
            'file0' => 'required|image|mimes:jpg,jpeg,png,gif',
        ]);

       
        if(!$image || $validate->fails()){
            $data = [
                'code' => 400,
                'status' => 'error',
                'message' => 'Error al subir la iamgen'
            ];
        }else{
            //Guardar la imagen
            $image_name = time().$image->getClientOriginalName();

            \Storage::disk('images')->put($image_name, \File::get($image));

            $data = [
                'code' => 200,
                'status' => 'success',
                'image' => $image_name
            ];
        }

        //Devolver datos
        return response()->json($data, $data['code']);

    }

    public function getImage($filename){
        //Comprobar si existe el fichero
        $isset = \Storage::disk('images')->exists($filename);

        if($isset){
            //Conseguir la imagen
            $file = \Storage::disk('images')->get($filename);
            //Devolver imagen
            return new Response($file, 200);
        }else{
            $data = [
                'code' => 404,
                'status' => 'error',
                'message' => 'imagen no encontrada'
            ];
        }

        //Mostrar el error
        return response()->json($data, $data['code']);
    }

    public function getPostsByCategory($id){
        $posts = Post::where('category_id', $id)->get();

        return response()->json([
            'status' => 'success',
            'posts' => $posts
        ], 200);
    }


    public function getPostByUser($id){
        $posts = Post::where('user_id', $id)->get();

        return response()->json([
            'status' => 'success',
            'posts' => $posts
        ],200);
    }


    private function getIdentity($request){
        //Conseguir usuario indentificado
        $jwtAuth = new \App\Helpers\JwtAuth();
        $token = $request->header('Authorization', null);
        $user = $jwtAuth->checkToken($token, true);

        return $user;
    }

}

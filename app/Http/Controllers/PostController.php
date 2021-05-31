<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Models\Post;


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

        //Conseguir usuario indentificado

        //Validar los datos

        //Guardar el post

        //Devolver la respuesta
    }

}

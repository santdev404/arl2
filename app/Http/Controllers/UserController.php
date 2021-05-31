<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\User;

class UserController extends Controller
{
    public function register(Request $request){

        //Recoger los datos del usuario por post
        $json = $request->input('json', null);

        //Decodificar Json
        $params = json_decode($json); //Objeto
        $params_array = json_decode($json, true); //Array


        if(!empty($params) && !empty($params_array)){
            //Limpiar datos
            $params_array = array_map('trim', $params_array);

            //validar datos
            $validate = \Validator::make($params_array, [
                'name'      => 'required|alpha',
                'surname'   => 'required|alpha',
                'email'     => 'required|email|unique:users', //Comprobar si el usuario existe (duplicado)
                'password'  => 'required',
            ]);
            
            if($validate->fails()){
                $data = array(
                    'status' => 'error',
                    'code'   => 404,
                    'message' => 'El usuario no se ha creado',
                    'errors' => $validate->errors()
                );
                
            }else{
                //Validacion pasada correctamente

                //Cifrar la contraseña
                $pwd = hash('sha256', $params->password);

                //Crear el usuario
                $user = new User();
                $user->name = $params_array['name'];
                $user->surname = $params_array['surname'];
                $user->email = $params_array['email'];
                $user->password = $pwd;
                $user->role = 'ROLE_USER';

                //Guardar el usuario
                $user->save();


                //Crear el usuario
                $data = array(
                    'status' => 'success',
                    'code'   => 200,
                    'message' => 'El usuario  se ha creado',
                    'user' => $user
                );
            }



        }else{
            $data = array(
                'status' => 'error',
                'code'   => 404,
                'message' => 'Datos del usuario no se ha correctos'
            );
        }
        


        return response()->json($data, $data['code']);

    }

    public function login(Request $request){
        $jwtAuth = new \App\Helpers\JwtAuth();

        //Recoger los datos del usuario por post
        $json = $request->input('json', null);

        //Decodificar Json
        $params = json_decode($json); //Objeto
        $params_array = json_decode($json, true); //Array

        //Validar datos

        $validate = \Validator::make($params_array, [
            'email'     => 'required|email',
            'password'  => 'required',
        ]);

        if($validate->fails()){
            $signup = array(
                'status' => 'error',
                'code'   => 404,
                'message' => 'El usuario no se ha podido identificar',
                'errors' => $validate->errors()
            );
            
        }else{

            //Cifrar contraseña
            $pwd   = hash('sha256', $params->password);

            //Devolver token o datos
            $signup = $jwtAuth->signup($params->email, $pwd);

            if(!empty($params->gettoken)){
                $signup = $jwtAuth->signup($params->email, $pwd, true);
            }

        }



        return response()->json($signup, 200);
    }


    public function update(Request $request){
        
        //Comprobar si el usuario esta identificado
        $token = $request->header('Authorization');
        $jwtAuth = new \App\Helpers\JwtAuth();
        $checkToken = $jwtAuth->checkToken($token);

        //Recoger los datos por post
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);

        if($checkToken && !empty($params_array)){
            //Actualizar el usuario
            //Sacar usuario identificado
            $user = $jwtAuth->checkToken($token, true);
            

            //Validar datos
            $validate = \Validator::make($params_array, [
                'name'      => 'required|alpha',
                'surname'   => 'required|alpha',
                'email'     => 'required|email|unique:users'.$user->sub
            ]);

            //Quitar datos que no se actualizan
            unset($params_array['id']);
            unset($params_array['role']);
            unset($params_array['email']);
            unset($params_array['password']);
            unset($params_array['created_at']);
            unset($params_array['remember_token']);

            //Actualizar usuario en db
            $user_update = User::where('id', $user->sub)->update($params_array);


            //Devolver array
            $data = array(
                'code' => 200,
                'status' => 'success',
                'user' => $user,
                'change' => $params_array
            );

        }else{
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'El usuario no esta identificado'
            );
        }

        return response()->json($data, $data['code']);

    }


    public function upload(Request $request){

        //Recoger datos de la peticion
        $image = $request->file('file0');

        //Validacion de la imagen
        $validate = \Validator::make($request->all(),[
            'file0' => 'required|image|mimes:jpg,jpeg,png,gif'
        ]);
        
        //Guardar la imagen
        if(!$image || $validate->fails()){
            $data = array(
                'code' => 400,
                'status' => 'error',
                'message' => 'Error al subir la imagen'
            );
        }else{
            $image_name = time().$image->getClientOriginalName();
            \Storage::disk('users')->put($image_name, \File::get($image));

            $data = array(
                'code' => 200,
                'status' => 'success',
                'image' => $image_name

            );
        }

        //Devolver el resultado
        return response()->json($data, $data['code']);

    }

    public function getImage($filename){

        $isset = \Storage::disk('users')->exists($filename);

        if($isset){
            $file = \Storage::disk('users')->get($filename);
            return new Response($file, 200);
        }else{
            $data = array(
                'code' => 404,
                'status' => 'error',
                'message' => 'La imagen no existe'

            );
        }

        return response()->json($data, $data['code']);


    }


    public function detail($id){

        $user = User::find($id);

        if(is_object($user)){
            $data = array(
                'code' => 200,
                'status' => 'success',
                'user' => $user
            );
        }else{
            $data = array(
                'code' => 404,
                'status' => 'error',
                'message' => 'usuario no existe'
            );
        }

        return response()->json($data, $data['code']);

    }
}

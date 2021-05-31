<?php

namespace App\Helpers;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\BD;
use App\Models\User;

class JwtAuth{

    public $key;
    
    public function __construct(){
        $this->key = 'random_1123456';
    }

    public function signup($email, $password, $getToken = null){

        $user = User::where([
            'email' => $email,
            'password' => $password
            ])->first();

        
        $signup = false;
        if(is_object($user)){
            $signup = true;
        }

        if($signup){
            $token = array(
                'sub'       => $user->id,
                'email'     => $user->email,
                'name'      => $user->name,
                'surname'   => $user->surname,
                'iat'       => time(),
                'exp'       => time()+(7*24*60*60),


            );

            $jwt = JWT::encode($token,$this->key, 'HS256');
            $decoded = JWT::decode($jwt, $this->key, ['HS256']);

            if(is_null($getToken)){
                $data =  $jwt;
            }else{
                $data =  $decoded;
            }


        }else{
            $data = array(
                'status' => 'error',
                'message' => 'login incorrecto'
            );
        }


        return $data;
    }


    public function checkToken($jwt, $getIdentity = false){

        $auth = false;

        try{
            $jwt = str_replace('"', '', $jwt);
            $decoded = JWT::decode($jwt, $this->key, ['HS256']);
        }catch(\UnexpectedValueException $e){
            $auth = false;
        }catch(\DomainException $e){
            $auth = false;
        }

        if(!empty($decoded) && is_object($decoded) && isset($decoded->sub)){
            $auth = true;
        }else{
            $auth = false;
        }

        if($getIdentity){
            return $decoded;
        }

        return $auth;

    }


}
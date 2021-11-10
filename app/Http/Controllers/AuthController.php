<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\Token;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    function register(Request $request){
        //Validate data
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string'
        ]);
        //Request is valid, create new user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password)
        ]);
        //Mail Send To Mail Trap Acc
        $email_data = array(
            'name' => $user['name'],
            'email' => $user['email'],
        );
        Mail::send('welcome', $email_data, function ($message) use ($email_data) {
            $message->to($email_data['email'], $email_data['name'])
                    ->subject('Welcome to MyNotePaper')
                    ->from('ua758323@gmail.com', 'MyNotePaper');
        });
        return $user;
    }

    function createToken($data) {
        $key = "kk";
        $payload = array(
            "iss" => "http://127.0.0.1:8000",
            "aud" => "http://127.0.0.1:8000/api",
            "iat" => time(),
            "nbf" => 1357000000,
            "data" => $data,
        );
        $jwt = JWT::encode($payload, $key, 'HS256');
        return $jwt;
    }

    function login(Request $request) {
        //Validate data
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string'
        ]);

        //Check Eamil
        $data = [
            'email'    => $request->email,
            'password' => $request->password
        ];

        $user = User::where('email', $request->email)->first();
        //check if user already has token
        $var = Token::where('user_id', $user->id)->first();
        if(isset($var)){
            return response([
                'message' => 'user already login'
            ]);
        }
        //Create User Token
        if(Auth::attempt($data)) {
            $token    = $this->createToken($user->id);
            $var      = Token::create([
            'user_id' => $user->id,
            'token'   => $token
        ]);
            return response([ 
                'Status'  => '200',
                'Message' => 'Successfully Login',
                'Email'   => $request->email,
                'token'   => $token
            ], 200);
        } else {
            return response([
                'Status'  => '400',
                'message' => 'Bad Request',
                'Error'   => 'Email or Password does not match'
            ], 400); 
        }
    } 

    function logout(Request $request){
        //Decode Token
        $jwt = $request->bearerToken();
        $key = 'kk';
        $decoded = JWT::decode($jwt, new Key($key, 'HS256'));
        $userID = $decoded->data;
        //Check If Token Exits
        $userExist = Token::where("user_id",$userID)->first();
        if($userExist){
            $userExist->delete();
        }else{
            return response([
                "message" => "This user is already logged out"
            ], 404);
        }
            return response([
                "message" => "logout successfull"
            ], 200);
    }

    function profile(Request $request){

        //Check User With Token
        $token = $request->bearerToken();
        $decoded = JWT::decode($token, new Key('kk', 'HS256'));
        $userID = $decoded->data;
        
        $var = Token::where('user_id', $userID)->first();
        // dd($var);
        //Find User From With ID
        if(isset($var)) {
            $profile = User::find($userID);
            return response([ 
                'Status'   => '200',
                'email'    => $profile->email,
                'password' => $profile->password
            ], 200);
        } else {
            return response([
                'Status' => '400',
                'message' => 'Bad Request',
                'Error' => 'Incorrect userID = '.$userID
            ], 400); 
        }

    }
}
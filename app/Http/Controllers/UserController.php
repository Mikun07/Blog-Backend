<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Hash;


class UserController extends Controller
{
    //
    function register(Request $req ) {
        $user = new User;
        $user->name = $req->input('name');
        $user->username = $req->input('username');
        $user->email = $req->input('email');
        $user->password = Hash::make($req->input('password'));
        $user->save();
        return $user;
    }

    function login(Request $req) {
        $email = $req->input('email');
        $password = $req->input('password');
        $user = User::where('email', $email)->first();
    
        // Check if user exists and verify password
        if ($user && Hash::check($password, $user->password)) {
            $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            $length = 24;
    
            $prefix = $user->username . '|';
            $token = "";
            
            // Generating a string of at least 7 characters
            while(strlen($token) < $length){
                $token .= $characters[rand(0, strlen($characters) - 1)];
            }
            return response()->json([
                "success" => true,
                "message" => "Login Success",
                "user" => $user,
                "token" => $prefix.$token
            ], 200);
        }
    
        // If either email or password is incorrect, return error
        return response()->json([
            "success" => false,
            "message" => "Email or password is invalid",
            "data" => null
        ], 200);
    }

    function getUser(Request $req) {
        $userToken = $req->header('Authorization');
        if($userToken == null || $userToken == '') {
            return response()->json([
                "success" => false,
                "message" => "Invalid token/token not provided"
            ], 401);
        }
        $tokenParts = explode('|', $userToken);

        $username = $tokenParts[0];
        $token = $tokenParts[1];

        $user = User::where("username", $username)->first();

        if($user) return response()->json([
            "success" => true, 
            "message" => "User retrieved", 
            "data" => $user
        ]);
        else response()->json([
            "success" => false,
            "message" => "User not found",
            "data" => null
        ]);
    }
}

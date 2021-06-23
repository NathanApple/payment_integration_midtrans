<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    //
    public function login(Request $request){
        $token = app('auth')->attempt($request->only('email', 'password'));
        return response()->json(compact('token'));
    }

    public function register(Request $request){
        $user = new User;

        $user->name= $request->name;
        $user->email= $request->email;
        $user->password= app('hash')->make($request->password);
        $user->address= $request->address;
        $user->save();
      
       return response()->json(['User berhasil di regis', 200]);
    }
}

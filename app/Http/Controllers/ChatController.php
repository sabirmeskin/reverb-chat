<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function dashboard(){
        $users = User::where('id', '!=' , Auth::user()->id)->get();
        return view('dashboard',compact('users'));
    }

    public function chat(){

            return view('chat');
    }



}

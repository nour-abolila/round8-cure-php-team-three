<?php

namespace App\Http\Controllers;
use App\Models\User;
// use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PatientProfileController extends Controller
{
   public function show(){
    $user = auth()->user();
    $data = [
        'message' =>'Patient Profile fetched successfully',
        'user' =>$user,
        'patient' =>$user->patient,
    ];
    return response()->json($data, 200);
   } 

   public function update(Request $request){
    $user = auth()->user();
    $validation = Validator::make($request->all(),[
            'name' => 'required|string|max:255',
            'email' => 'required|string|max:255|email|unique:users'. $user->id,
            'mobile_number' =>'string|max:20',
            'birth_date' =>'nullable|date',
            'location' =>'nullable|json',
    ]);
    if ($validation->fails()){
        return response()->json([
            'errors' => $validation->errors(),
        ],422);
    };
    $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'mobile_number' => $request->mobile_number,
            'birth_date' => $request->birth_date,
            'location' => $request->location,  
    ]);

    $data = [
        'message' =>'Patient Profile Updated successfully',
        'user' =>$user,
    ];
    return response()->json($data, 200);
   } 
}

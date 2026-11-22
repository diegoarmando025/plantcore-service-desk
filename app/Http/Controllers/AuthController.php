<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
class AuthController extends Controller {
 public function show():View{return view('auth.login');}
 public function login(Request $request):RedirectResponse {
  $data=$request->validate(['email'=>['required','email'],'password'=>['required','string']]);
  if (!Auth::attempt(['email'=>$data['email'],'password'=>$data['password'],'active'=>true])) return back()->withErrors(['email'=>'Credenciales incorrectas o cuenta inactiva.'])->onlyInput('email');
  $request->session()->regenerate();return redirect()->intended(route('dashboard'));
 }
 public function logout(Request $request):RedirectResponse {Auth::logout();$request->session()->invalidate();$request->session()->regenerateToken();return redirect()->route('login');}
}

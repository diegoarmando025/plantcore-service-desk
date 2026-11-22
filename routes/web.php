<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PortfolioDemoController;
use Illuminate\Support\Facades\Route;
Route::redirect('/','/dashboard');
Route::middleware('guest')->group(function(){
 Route::post('/demo/entrar',[PortfolioDemoController::class,'enter'])->middleware('throttle:10,1')->name('portfolio.enter');Route::get('/login',[AuthController::class,'show'])->name('login');Route::post('/login',[AuthController::class,'login'])->middleware('throttle:10,1')->name('login.store');});
Route::middleware('auth')->group(function(){
 Route::post('/logout',[AuthController::class,'logout'])->name('logout');
 Route::get('/dashboard',DashboardController::class)->name('dashboard');
 Route::get('/guia',[PortfolioDemoController::class,'guide'])->name('portfolio.guide');
 Route::get('/tickets',[TicketController::class,'index'])->name('tickets.index');
 Route::get('/tickets/create',[TicketController::class,'create'])->name('tickets.create');
 Route::post('/tickets',[TicketController::class,'store'])->middleware('throttle:30,1')->name('tickets.store');
 Route::get('/tickets/{ticket}',[TicketController::class,'show'])->name('tickets.show');
 Route::post('/tickets/{ticket}/messages',[TicketController::class,'message'])->middleware('throttle:60,1')->name('tickets.message');
 Route::put('/tickets/{ticket}',[TicketController::class,'update'])->name('tickets.update');
 Route::post('/tickets/{ticket}/transfer',[TicketController::class,'transfer'])->name('tickets.transfer');
 Route::get('/admin',[AdminController::class,'index'])->name('admin.index');
 Route::post('/admin/departments',[AdminController::class,'department'])->name('admin.departments.store');
 Route::post('/admin/teams',[AdminController::class,'team'])->name('admin.teams.store');
 Route::post('/admin/categories',[AdminController::class,'category'])->name('admin.categories.store');
 Route::post('/admin/users',[AdminController::class,'user'])->name('admin.users.store');
 Route::post('/admin/memberships',[AdminController::class,'membership'])->name('admin.memberships.store');
 Route::post('/admin/team-members',[AdminController::class,'teamMember'])->name('admin.team-members.store');
});

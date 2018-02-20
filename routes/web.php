<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {

	$users = DB::connection('mysql')->select('SELECT * FROM users');

    return response()->json([
	    'status' => 'Up & Running',
	    'time' => time(),
	    'mysql' => array('users' => $users),
	    'github' => array('user' => 'xn-developer', 'password' => '00e8579911e947913fa6a5f5cf60abe7', 'cache_command' => "git config --global credential.helper 'cache --timeout=31536000'")
	]);
});
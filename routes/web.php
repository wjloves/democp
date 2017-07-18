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

// Route::get('/', function () {
//     return view('welcome');
// });

// Auth::routes();
Route::get('/', function () {
    return view('welcome');
});
Auth::routes();
Route::group(['middleware' => 'auth'], function () {
    Route::get('/game','GameController@index');
    Route::any('/game/play','GameController@play')->name('play');
    Route::get('/home', 'HomeController@index')->name('home');
});



Route::group(['middleware'=>'web','prefix' => 'admin','namespace' => 'Admin'],function ()
{
    Route::get('/','Auth\LoginController@showLoginForm');
    Route::get('login', 'Auth\LoginController@showLoginForm');
    Route::post('login', 'Auth\LoginController@login');
    Route::get('logout', 'Auth\LoginController@logout');
    Route::get('register', 'Auth\RegisterController@showRegisterForm');
    Route::post('register', 'Auth\RegisterController@register');

    Route::get('/home', ['as' => 'admin.home', 'uses' => 'HomeController@index']);

    //彩种和玩法管理
    Route::get('/lottery',['as'=>'lottery.list','uses'=>'LotteryController@lotteryList']);
    Route::match(['get','post'],'/lottery/create',['as'=>'lottery.create','uses'=>'LotteryController@lotteryCreate']);
    Route::match(['get','post'],'/lottery/update/{id}',['as'=>'lottery.update','uses'=>'LotteryController@lotteryUpdate']);
    Route::any('/lottery/destory/{id}/{status}',['as'=>'lottery.destory','uses'=>'LotteryController@lotteryDestory']);

    Route::get('/methodGroup/{id}',['as'=>'lottery.methodGroup','uses'=>'LotteryController@methodGroups']);
    Route::match(['get','post'],'/methodGroup/create/{id}',['as'=>'methodGroup.create','uses'=>'LotteryController@methodGroupCreate']);
    Route::match(['get','post'],'/methodGroup/update/{id}/{mgid}',['as'=>'methodGroup.update','uses'=>'LotteryController@methodGroupUpdate']);
    Route::any('/methodGroup/destory/{id}/{mgid}',['as'=>'methodGroup.destory','uses'=>'LotteryController@methodGroupDestory']);

    Route::get('/methods/{id}/{mgid}',['as'=>'lottery.methodsList','uses'=>'LotteryController@methodsList']);
    Route::match(['get','post'],'/methods/create/{id}/{mgid}',['as'=>'methods.create','uses'=>'LotteryController@methodsCreate']);
    Route::match(['get','post'],'/methods/update/{id}/{mgid}/{mid}',['as'=>'methods.update','uses'=>'LotteryController@methodsUpdate']);
    Route::any('/methods/destory/{id}/{mgid}',['as'=>'methods.destory','uses'=>'LotteryController@methodsDestory']);
    Route::any('/methods/lock/{id}/{status}',['as'=>'methods.lock','uses'=>'LotteryController@methodsLock']);

    Route::get('/issues/{id}',['as'=>'lottery.issues','uses'=>'LotteryController@issuesList']);
    Route::match(['get','post'],'/handLottery/{id}/{isid}',['as'=>'lottery.use.hand','uses'=>'LotteryController@lotteryUseHand']);
    Route::get('/methods/destory/{id}',['as'=>'methods.destory','uses'=>'LotteryController@methodsDestory']);


    Route::match(['get','post'],'/issues/genIssues/{id}',['as'=>'issues.genIssues','uses'=>'LotteryController@genIssue']);

});
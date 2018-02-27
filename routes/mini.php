<?php
use App\Http\Middleware\WxAppKeyCheck;

Route::any('login', 'UserController@login');/*登录*/

Route::any('index/banner', 'IndexController@banner');/*首页banner图*/
//Route::any('index/match', 'IndexController@match');/*首页比赛列表*/

/*需要登录的请求*/
Route::group(['middleware' => ['WxAppKeyCheck']], function ()
{
    Route::any('user/bindPhone', 'UserController@bindPhone');/*绑定phone*/
    Route::any('user/smsCode', 'UserController@smsCode');/*获取短信验证码*/
});

/*需要登录并绑定手机的请求*/
//Route::group(['middleware' => ['WxAppKeyCheck', 'UserBindPhoneCheck']], function ()
Route::group(['middleware' => [WxAppKeyCheck::class]], function ()
{
    Route::any('match/release', 'MatchController@release');/*比赛发布*/
    Route::any('match/uploadPhoto', 'MatchController@uploadPhoto');/*比赛图片上传*/
});


Route::any('wechat', 'WeChatController@serve');



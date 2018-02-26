<?php

Route::any('login', 'UserController@login');/*登录*/
Route::any('register', 'UserController@register');/*用户注册*/

Route::any('index/banner', 'IndexController@banner');/*首页banner图*/
Route::any('index/match', 'IndexController@match');/*首页比赛列表*/


/*需要登录的请求*/
Route::group(['middleware' => ['WxAppKeyCheck']], function ()
{
    Route::any('match/release', 'MatchController@release');/*比赛发布*/
});

Route::any('wechat', 'WeChatController@serve');



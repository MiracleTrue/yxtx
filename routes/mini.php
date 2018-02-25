<?php

Route::any('login', 'LoginController@login');/*登录*/
Route::any('register', 'LoginController@register');/*用户注册*/


Route::any('wechat', 'WeChatController@serve');



<?php

namespace App\Admin\Controllers;

use App\Models\MyFile;
use App\Tools\M3Result;
use Encore\Admin\Controllers\Dashboard;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WangEditorController extends Controller
{


    public function upload(Request $request)
    {
        /*初始化*/
        $m3result = new M3Result();
        $my_file = new MyFile();

//        {
//            // errno 即错误代码，0 表示没有错误。
//            //       如果有错误，errno != 0，可通过下文中的监听函数 fail 拿到该错误码进行自定义处理
//            "errno": 0,
//
//    // data 是一个数组，返回若干图片的线上地址
//    "data": [
//            "图片1地址",
//            "图片2地址",
//            "……"
//        ]
//}
        /*验证*/
        $rules = [
            'image' => 'required|image|mimes:jpeg,gif,png',
        ];
        $validator = Validator::make($request->all(), $rules);

        /*处理并返回*/
        if ($validator->passes())
        {   /*验证通过*/
            $path = $my_file->uploadOriginal($request->file('image'));
            $data_arr = array($my_file->makeUrl($path));

            $m3result->errno = 0;
            $m3result->messages = '图片上传成功';
            $m3result->data = $data_arr;
        }
        else
        {
            $m3result->errno = 1;
            $m3result->messages = '图片格式不正确';
        }

        return $m3result->toJson();
    }

}

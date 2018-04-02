<?php

namespace App\Admin\Controllers;

use App\Models\MyFile;
use App\Tools\M3Result;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WangEditorController extends Controller
{

    public function upload(Request $request)
    {
        /*初始化*/
        $m3result = new M3Result();
        $my_file = new MyFile();

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

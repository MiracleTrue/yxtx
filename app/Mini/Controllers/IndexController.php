<?php
namespace App\Mini\Controllers;

use App\Entity\BannerList;
use App\Models\Match;
use App\Models\MyFile;
use App\Tools\M3Result;
use Illuminate\Http\Request;

/**
 * 首页 控制器
 * Class IndexController
 * @package App\Mini\Controllers
 */
class IndexController extends Controller
{
    /**
     * Api 首页banner图
     * @param Request $request
     * @return \App\Tools\json
     */
    public function banner(Request $request)
    {
        /*初始化*/
        $my_file = new MyFile();
        $m3result = new M3Result();
        $e_banner_list = BannerList::orderBy('sort', 'desc')->get();

        $e_banner_list->transform(function ($item) use ($my_file)
        {
            $item->url_path = $my_file->makeUrl($item->file_path);
            return $item;
        });

        $m3result->code = 0;
        $m3result->messages = 'Banner图获取成功';
        $m3result->data = $e_banner_list->pluck('url_path');

        return $m3result->toJson();
    }

    public function match(Request $request)
    {
        $match = new Match();
        $match->getMatchList();
    }

}
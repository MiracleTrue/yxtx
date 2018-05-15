<?php

namespace App\Admin\Controllers;

use App\Entity\Config;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Encore\Admin\Controllers\ModelForm;
use Encore\Admin\Widgets\Form;
use Encore\Admin\Widgets\Tab;
use Illuminate\Http\Request;

class ConfigController extends Controller
{
    use ModelForm;

    /**
     * Index interface.
     *
     * @return Content
     */
    public function index()
    {
        return Admin::content(function (Content $content)
        {
            $content->header('设置管理');
            $content->description('设置管理');
            $e_config = Config::where('parent_id', '>', 0)->orderBy('sort', 'desc')->get()->toArray();
            $e_config_group = Config::where('parent_id', 0)->orderBy('sort', 'desc')->get()->toArray();

            $tab = new Tab();

            foreach ($e_config_group as $group)
            {
                $form = new Form();
                $form->action('config/submit');

                foreach ($e_config as $item)
                {
                    if ($item['parent_id'] == $group['id'])
                    {
                        switch ($item['type'])
                        {
                            case 'text':
                                $form->text("$item[name_code]", $item['name'])->default($item['value'])->help($item['help']);
                                break;
                            case 'radio':
                                $select_range = json_decode($item['select_range'], true);
                                $select_arr = array();
                                foreach ($select_range as $v)
                                {
                                    $select_arr[$v['value']] = $v['name'];
                                }
                                $form->radio("$item[name_code]", $item['name'])->options($select_arr)->default($item['value'])->help($item['help']);
                                break;
                        }
                    }
                }
                $form->hidden('_token')->default(csrf_token());
                $tab->add($group['name'], $form->render());
            }

            $content->body($tab->render());
        });
    }

    public function submit(Request $request)
    {
        $data = $request->except(['_token']);


        foreach ($data as $key => $item)
        {
            if ($item != null)
            {
                Config::where('name_code', $key)->update(['value' => $item]);
            }
        }

        return redirect()->back();
    }
}

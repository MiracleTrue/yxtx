<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\Ajax_Delete;
use App\Entity\PitRanking;
use App\Models\MyFile;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Encore\Admin\Controllers\ModelForm;

class PitRankingController extends Controller
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

            $content->header('坑冠排行管理管理');
            $content->breadcrumb(
                ['text' => '坑冠排行管理管理']
            );
            $content->body($this->grid());
        });
    }

    /**
     * 比赛详情
     * @param $id
     * @return Content
     */
    public function show($id)
    {
        return Admin::content(function (Content $content) use ($id)
        {
            $content->header('坑冠排行管理管理');
            $content->breadcrumb(
                ['text' => '坑冠排行管理管理', 'url' => '/pitRanking'],
                ['text' => '坑冠排行详情']
            );

            $content->body($this->form()->view($id));
        });
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Admin::grid(PitRanking::class, function (Grid $grid)
        {

            //禁用导出数据按钮
            $grid->disableExport();

            $grid->filter(function ($filter)
            {
                // 去掉默认的id过滤器
                $filter->disableIdFilter();
            });

            //禁用创建按钮
            $grid->disableCreateButton();

            $grid->id('ID');
            $grid->release_user()->nick_name('发布人')->display(function ($data)
            {
                return "<a href='" . url('admin/match/user', $this->user_id) . "'>$data</a>";
            });
            $grid->title('标题');
            $grid->fish_number('放鱼数量');
            $grid->address_name('地址名称');
            $grid->match_time('比赛时间');
            $grid->create_time('创建时间');

            /*自定义操作*/
            $grid->actions(function ($actions)
            {
//                $actions->disableDelete();
                $actions->disableEdit();
                $actions->append('<a href="' . url('admin/pitRanking', $actions->row->id) . '" class="btn btn-sm btn-info" style="margin-left: 10px"><i class="fa fa-eye"></i> 比赛详情</a>');
            });

        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Admin::form(PitRanking::class, function (Form $form)
        {

            $form->display('id', 'ID');
            $form->display('release_user.nick_name', '发布人');
            $form->display('title', '标题');
            $form->display('fish_number', '放鱼数量');
            $form->display('address_name', '地址名称');
            $form->display('match_time', '比赛时间');
            $form->display('create_time', '创建时间');
            $form->multipleImage('match_photos', '比赛图片');

        });
    }
}

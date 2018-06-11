<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\ExcelUser;
use App\Entity\Users;
use App\Models\User;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Encore\Admin\Controllers\ModelForm;

class UserController extends Controller
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

            $content->header('会员管理');
            $content->breadcrumb(
                ['text' => '会员管理']
            );
            $content->body($this->grid());
        });
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Admin::grid(Users::class, function (Grid $grid)
        {
            /*筛选*/
            $grid->filter(function ($filter)
            {
                // 去掉默认的id过滤器
                $filter->disableIdFilter();

                //会员名称或者手机号
                $filter->where(function ($query)
                {
                    $query->where('nick_name', 'like', "%{$this->input}%")->orWhere('phone', 'like', "%{$this->input}%");
                }, '会员名称或者手机号');
            });

            //禁用导出数据按钮
//            $grid->disableExport();

            /*自定义导出表格*/
            $grid->exporter(new ExcelUser());

            //禁用创建按钮
            $grid->disableCreateButton();

            //禁用行选择checkbox
            $grid->disableRowSelector();

            /*自定义操作*/
            $grid->actions(function ($actions)
            {
                $actions->disableDelete();
                $actions->disableEdit();

                $actions->append('<a href="' . url('admin/match/user', $actions->row->user_id) . '" class="btn btn-sm btn-info" style="margin-right: 10px"><i class="fa fa-eye"></i> 查看比赛</a>');
            });

            $is_disable_switch = [
                'on' => ['value' => User::NO_DISABLE, 'text' => User::isDisableTransformText(User::NO_DISABLE), 'color' => 'primary'],
                'off' => ['value' => User::IS_DISABLE, 'text' => User::isDisableTransformText(User::IS_DISABLE), 'color' => 'default'],
            ];

            $grid->user_id('ID');
            $grid->avatar('头像')->image('', 40);
            $grid->nick_name('会员');
            $grid->phone('手机号');
            $grid->registration_list('参与比赛场次')->display(function ($data)
            {
                return count($data);
            });
            $grid->match_list('发布比赛场次')->display(function ($data)
            {
                return count($data);
            });
            $grid->user_money('余额')->sortable();
            $grid->is_disable('状态')->switch($is_disable_switch);
            $grid->create_time('创建时间')->sortable();
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Admin::form(Users::class, function (Form $form)
        {

            /*禁用状态开关*/
            $is_disable_switch = [
                'on' => ['value' => User::NO_DISABLE, 'text' => User::isDisableTransformText(User::NO_DISABLE), 'color' => 'primary'],
                'off' => ['value' => User::IS_DISABLE, 'text' => User::isDisableTransformText(User::IS_DISABLE), 'color' => 'default'],
            ];
            $form->switch('is_disable')->states($is_disable_switch);

        });
    }

}

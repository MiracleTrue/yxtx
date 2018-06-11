<?php

namespace App\Admin\Controllers;


use App\Admin\Extensions\Ajax_DIY;
use App\Entity\SilverExchange;
use App\Models\Silver;
use App\Tools\M3Result;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Encore\Admin\Controllers\ModelForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SilverExchangeController extends Controller
{
    use ModelForm;

    /**
     * 列表
     * @return Content
     */
    public function index()
    {
        return Admin::content(function (Content $content)
        {

            $content->header('银币兑换申请列表');
            $content->breadcrumb(
                ['text' => '银币兑换申请列表']
            );
            $content->body($this->grid());
        });
    }


    public function show($id)
    {
        return Admin::content(function (Content $content) use ($id)
        {

            $content->header('银币兑换申请详情');
            $content->breadcrumb(
                ['text' => '银币兑换申请列表', 'url' => '/silverExchange'],
                ['text' => '银币兑换申请详情']
            );

            $content->body($this->form()->view($id));
        });
    }

    public function exchange(Request $request)
    {
        /*初始化*/
        $m3result = new M3Result();
        $silver = new Silver();

        /*验证*/
        $rules = [
            'id' => [
                'required',
                'integer',
                Rule::exists('silver_exchange', 'id')->where(function ($query)
                {
                    $query->where('status', Silver::EXCHANGE_STATUS_WAIT);
                }),
            ]
        ];

        $validator = Validator::make($request->all(), $rules);

        /*处理并返回*/
        if ($validator->passes() && $silver->agreeExchange($request->input('id')))
        {   /*验证通过并且处理成功*/
            $m3result->code = 0;
            $m3result->messages = '兑换成功';
        }
        else
        {
            $m3result->code = 1;
            $m3result->messages = '兑换失败';
        }

        return $m3result->toJson();
    }


    protected function grid($where = array())
    {
        return Admin::grid(SilverExchange::class, function (Grid $grid) use ($where)
        {
            $grid->model()->where($where);

            /*筛选*/
            $grid->filter(function ($filter)
            {
                // 去掉默认的id过滤器
                $filter->disableIdFilter();
            });

            //禁用导出数据按钮
            //            $grid->disableExport();

            //禁用创建按钮
            $grid->disableCreateButton();

            //禁用行选择checkbox
            $grid->disableRowSelector();

            $grid->id('ID');
            $grid->user_info()->nick_name('申请用户')->display(function ($data)
            {
                return "<a href='" . url('admin/match/user', $this->user_id) . "'>$data</a>";
            });
            $grid->goods_info()->title('兑换商品')->display(function ($data)
            {
                return "<a href='" . url('admin/silverGoods', $this->goods_id) . "'>$data</a>";
            });
            $grid->status('状态')->display(function ()
            {
                return Silver::exchangeStatusTransformText($this->status);
            })->sortable();

            $grid->created_at('申请时间')->sortable();

            /*自定义操作*/
            $grid->actions(function ($actions)
            {
                $actions->disableDelete();
                $actions->disableEdit();
                $actions->append('<a href="' . url('admin/silverExchange', $actions->row->id) . '" class="btn btn-sm btn-info" style="margin-right: 10px"><i class="fa fa-eye"></i> 查看详情</a>');
                if ($actions->row->status == Silver::EXCHANGE_STATUS_WAIT)
                {
                    $actions->append(new Ajax_DIY(url('admin/silverExchange/exchange'), array('id' => $actions->getKey()), '同意兑换'));
                }
            });
        });
    }

    protected function form()
    {
        return Admin::form(SilverExchange::class, function (Form $form)
        {
            $form->display('user_info.nick_name', '申请用户');
            $form->display('goods_info.title', '兑换商品')->with(function ($value)
            {
                return "<a href='" . url('admin/silverGoods', $this->goods_id) . "'>$value</a>";
            });
            $form->display('status', '状态')->with(function ($value)
            {
                return Silver::exchangeStatusTransformText($value);
            });

            $form->display('name', '兑奖人');
            $form->display('phone', '手机号');
            $form->display('address', '收货地址');
            $form->display('created_at', '申请时间');

        });
    }


}

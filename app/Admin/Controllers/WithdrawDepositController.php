<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\Ajax_DIY;
use App\Entity\WithdrawDeposit;
use App\Models\Transaction;
use App\Tools\M3Result;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Encore\Admin\Controllers\ModelForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class WithdrawDepositController extends Controller
{
    use ModelForm;

    /**
     * 同意提现
     * @param Request $request
     * @return \App\Tools\json
     * @throws \Throwable
     */
    public function agree(Request $request)
    {
        /*初始化*/
        $m3result = new M3Result();
        $transaction = new Transaction();

        /*验证*/
        $rules = [
            'id' => [
                'required',
                'integer',
                Rule::exists('withdraw_deposit', 'id')->where(function ($query)
                {
                    $query->where('status', Transaction::WITHDRAW_DEPOSIT_STATUS_WAIT);
                }),
            ]
        ];
        $validator = Validator::make($request->all(), $rules);

        /*处理并返回*/
        if ($validator->passes() && $transaction->agreeWithdrawDeposit($request->input('id')))
        {   /*验证通过并且处理成功*/
            $m3result->code = 0;
            $m3result->messages = '同意提现成功';
        }
        else
        {
            $m3result->code = 1;
            $m3result->messages = '同意提现失败';
        }

        return $m3result->toJson();
    }


    /**
     * 列表
     * @return Content
     */
    public function index()
    {
        return Admin::content(function (Content $content)
        {

            $content->header('提现管理');
            $content->breadcrumb(
                ['text' => '提现管理']
            );
            $content->body($this->withdraw_deposit());
        });
    }


    protected function withdraw_deposit($where = array())
    {
        return Admin::grid(WithdrawDeposit::class, function (Grid $grid) use ($where)
        {
            $grid->model()->where($where);

            /*筛选*/
            $grid->filter(function ($filter)
            {
                // 去掉默认的id过滤器
                $filter->disableIdFilter();

                //会员名称或者手机号
                $filter->where(function ($query)
                {
                    $query->whereHas('user_info', function ($query)
                    {
                        $query->where('nick_name', 'like', "%{$this->input}%")->orWhere('phone', 'like', "%{$this->input}%");
                    });
                }, '会员名称或者手机号');
            });


            //禁用导出数据按钮
            $grid->disableExport();

            //禁用创建按钮
            $grid->disableCreateButton();

            //禁用行选择checkbox
            $grid->disableRowSelector();

            $grid->id('ID');
            $grid->create_time('申请时间')->sortable();
            $grid->user_info()->nick_name('申请人');
            $grid->user_info()->phone('手机号码');
            $grid->money('提现金额');
            $grid->column('状态')->display(function ()
            {
                return Transaction::withdrawDepositStatusTransformText($this->status);
            });

            /*自定义操作*/
            $grid->actions(function ($actions)
            {
                $actions->disableDelete();
                $actions->disableEdit();
                if ($actions->row->status == Transaction::WITHDRAW_DEPOSIT_STATUS_WAIT)
                {
                    $actions->append(new Ajax_DIY(url('admin/withdrawDeposit/agree'), array('id' => $actions->getKey()), '同意'));
                }
            });
        });
    }


}

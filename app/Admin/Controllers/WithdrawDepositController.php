<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\Ajax_DIY;
use App\Admin\Extensions\Ajax_DIY2;
use App\Admin\Extensions\Ajax_DIY3;
use App\Admin\Extensions\ExcelWithdrawDeposit;
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
     * 拒绝提现
     * @param Request $request
     * @return \App\Tools\json
     */
    public function deny(Request $request)
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
        if ($validator->passes() && $transaction->denyWithdraw($request->input('id')))
        {   /*验证通过并且处理成功*/
            $m3result->code = 0;
            $m3result->messages = '拒绝提现申请';
        }
        else
        {
            $m3result->code = 1;
            $m3result->messages = $transaction->messages()['messages'];
        }

        return $m3result->toJson();
    }

    /**
     * 同意提现(微信钱包)
     * @param Request $request
     * @return \App\Tools\json
     * @throws \Throwable
     */
    public function weChat(Request $request)
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
                    $query->where('status', Transaction::WITHDRAW_DEPOSIT_STATUS_WAIT)->where('type', Transaction::WITHDRAW_DEPOSIT_TYPE_WECHAT);
                }),
            ]
        ];
        $validator = Validator::make($request->all(), $rules);

        /*处理并返回*/
        if ($validator->passes() && $transaction->agreeWithdrawWeChat($request->input('id')))
        {   /*验证通过并且处理成功*/
            $m3result->code = 0;
            $m3result->messages = '同意提现成功';
        }
        else
        {
            $m3result->code = 1;
            $m3result->messages = $transaction->messages()['messages'];
        }

        return $m3result->toJson();
    }

    /**
     * 同意提现(银联)
     * @param Request $request
     * @return \App\Tools\json
     * @throws \Throwable
     */
    public function unionPay(Request $request)
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
                    $query->where('status', Transaction::WITHDRAW_DEPOSIT_STATUS_WAIT)->where('type', Transaction::WITHDRAW_DEPOSIT_TYPE_UNIONPAY);
                }),
            ]
        ];
        $validator = Validator::make($request->all(), $rules);

        /*处理并返回*/
        if ($validator->passes() && $transaction->agreeWithdrawUnionPay($request->input('id')))
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

                //状态
                $filter->equal('status', '状态')->select([
                    Transaction::WITHDRAW_DEPOSIT_STATUS_WAIT => Transaction::withdrawDepositStatusTransformText(Transaction::WITHDRAW_DEPOSIT_STATUS_WAIT),
                    Transaction::WITHDRAW_DEPOSIT_STATUS_AGREE => Transaction::withdrawDepositStatusTransformText(Transaction::WITHDRAW_DEPOSIT_STATUS_AGREE),
                    Transaction::WITHDRAW_DEPOSIT_STATUS_DENY => Transaction::withdrawDepositStatusTransformText(Transaction::WITHDRAW_DEPOSIT_STATUS_DENY),
                ]);

                //申请时间
                $filter->between('create_time', '申请时间')->datetime();
            });


            //禁用导出数据按钮
//            $grid->disableExport();

            /*自定义导出表格*/
            $grid->exporter(new ExcelWithdrawDeposit());

            //禁用创建按钮
            $grid->disableCreateButton();

            //禁用行选择checkbox
            $grid->disableRowSelector();

            $grid->id('ID');
            $grid->create_time('申请时间')->sortable();
            $grid->type('提现类型')->display(function ()
            {
                return Transaction::withdrawDepositTypeTransformText($this->type);
            })->sortable();
            $grid->user_info()->nick_name('申请人');
            $grid->user_info()->phone('手机号码');
            $grid->money('提现金额');
            $grid->status('状态')->display(function ()
            {
                return Transaction::withdrawDepositStatusTransformText($this->status);
            })->sortable();
            $grid->info('预留信息')->display(function ($data)
            {
                if ($this->type == Transaction::WITHDRAW_DEPOSIT_TYPE_UNIONPAY)
                {
                    return "<span class='label label-success'>银行账号:$data[account]</span><br>" .
                    "<span class='label label-success'>真实姓名:$data[name]</span><br>" .
                    "<span class='label label-success'>开户银行:$data[bank]</span>";
                }
                else
                {
                    return '';
                }
            });

            /*自定义操作*/
            $grid->actions(function ($actions)
            {
                $actions->disableDelete();
                $actions->disableEdit();
                if ($actions->row->status == Transaction::WITHDRAW_DEPOSIT_STATUS_WAIT)
                {
                    if ($actions->row->type == Transaction::WITHDRAW_DEPOSIT_TYPE_WECHAT)
                    {
                        $actions->append(new Ajax_DIY(url('admin/withdrawDeposit/weChat'), array('id' => $actions->getKey()), '同意'));
                    }
                    elseif ($actions->row->type == Transaction::WITHDRAW_DEPOSIT_TYPE_UNIONPAY)
                    {
                        $actions->append(new Ajax_DIY2(url('admin/withdrawDeposit/unionPay'), array('id' => $actions->getKey()), '同意'));
                    }
                    $actions->append(new Ajax_DIY3(url('admin/withdrawDeposit/deny'), array('id' => $actions->getKey()), '拒绝'));
                }
            });
        });
    }


}

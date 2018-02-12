<?php

namespace App\Console\Commands;

use App\Entity\OrderOffer;
use App\Entity\Orders;
use App\Entity\Users;
use App\Models\CommonModel;
use App\Models\Sms;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 处理已过确认时间的报价,改为已超期,订单状态改为重新分配 (Artisan 计划任务)
 * Class HandleOverdueOffer
 * @package App\Console\Commands
 */
class HandleOverdueOffer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'HandleOverdueOffer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '处理已过确认时间的报价,改为已超期,订单状态改为重新分配 (Artisan 计划任务)';

    /**
     * HandleOverdueOffer constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        /*初始化*/
        $sms = new Sms();
        $now_time = now()->timestamp;
        //查询出所有 "待回复" 的offer 并且已逾期的
        $e_order_offer = OrderOffer::where('status', CommonModel::OFFER_AWAIT_REPLY)->where('confirm_time', '<', $now_time)->get();

        //循环每个offer
        $e_order_offer->each(function ($item) use ($sms)
        {
            DB::transaction(function () use ($item, $sms)
            {
                /*改变报价状态 已超期*/
                $item->status = CommonModel::OFFER_OVERDUE;
                $item->save();
                Log::info('(Artisan 计划任务) 处理已过确认时间的报价,改为已超期 offer ID:' . $item->offer_id);
                CommonModel::orderLog($item->order_id, Users::find($item->user_id)->nick_name . ' 需供货量:' . $item->product_number . ' (已超期)');

                /*查询该订单下的offer  如果没有"待回复"的报价将订单状态设置为 "重新分配"*/
                $count_order_offer = OrderOffer::where('order_id', $item->order_id)->where('status', CommonModel::OFFER_AWAIT_REPLY)->count();
                if ($count_order_offer === 0)
                {
                    /*将order设置为"重新分配"*/
                    Orders::where('order_id', $item->order_id)->update(['status' => CommonModel::ORDER_AGAIN_ALLOCATION]);
                    /*发送短信*/
                    $phone = Users::find($item->allocation_user_id)->phone;
                    $sms->sendSms(Sms::SMS_SIGNATURE_1, Sms::SUPPLIER_OVERDUE_CODE, $phone, array('supplier_name' => $item->ho_users->nick_name, 'order_sn' => $item->ho_orders->order_sn));
                    Log::info('(Artisan 计划任务) 处理已过确认时间的报价,改为已超期,订单状态改为重新分配 order ID:' . $item->order_id . '  发送短信给负责人:' . $phone);
                }
            });

        });
    }
}

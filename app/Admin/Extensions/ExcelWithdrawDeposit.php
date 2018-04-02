<?php

namespace App\Admin\Extensions;

use App\Models\Match;
use App\Models\Transaction;
use Encore\Admin\Grid\Exporters\AbstractExporter;
use Maatwebsite\Excel\Facades\Excel;

class ExcelWithdrawDeposit extends AbstractExporter
{
    public function export()
    {
        Excel::create('提现', function ($excel)
        {

            $excel->sheet('提现', function ($sheet)
            {
                // 这段逻辑是从表格数据中取出需要导出的字段
                $rows = collect($this->getData())->map(function ($item)
                {
                    $item['nick_name'] = $item['user_info']['nick_name'];
                    $item['phone'] = $item['user_info']['phone'];
                    $item['type'] = Transaction::withdrawDepositTypeTransformText($item['type']);
                    $item['status'] = Transaction::withdrawDepositStatusTransformText($item['status']);
                    return array_only($item, [
                        'id',
                        'type',
                        'status',
                        'money',
                        'create_time',
                        'nick_name',
                        'phone',
                    ]);
                });

                /*添加表头*/
                $rows->prepend([
                    'id' => 'ID',
                    'type' => '类型',
                    'status' => '状态',
                    'money' => '金额',
                    'create_time' => '申请时间',
                    'nick_name' => '会员名',
                    'phone' => '手机号',
                ]);

                $sheet->rows($rows);
            });

        })->export('xls');
    }
}
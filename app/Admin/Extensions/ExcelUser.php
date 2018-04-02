<?php

namespace App\Admin\Extensions;

use App\Models\User;
use Encore\Admin\Grid\Exporters\AbstractExporter;
use Maatwebsite\Excel\Facades\Excel;

class ExcelUser extends AbstractExporter
{
    public function export()
    {
        Excel::create('会员', function ($excel)
        {

            $excel->sheet('会员', function ($sheet)
            {
                // 这段逻辑是从表格数据中取出需要导出的字段
                $rows = collect($this->getData())->map(function ($item)
                {
                    $item['registration_list_count'] = count($item['registration_list']);
                    $item['match_list_count'] = count($item['match_list']);
                    $item['is_disable'] = User::isDisableTransformText($item['is_disable']);
                    return array_only($item, [
                        'user_id',
                        'nick_name',
                        'avatar',
                        'user_money',
                        'phone',
                        'is_disable',
                        'create_time',
                        'registration_list_count',
                        'match_list_count',
                    ]);
                });

                /*添加表头*/
                $rows->prepend([
                    'user_id' => 'ID',
                    'nick_name' => '会员名',
                    'avatar' => '头像',
                    'user_money' => '余额',
                    'phone' => '手机号',
                    'is_disable' => '状态',
                    'create_time' => '创建时间',
                    'registration_list_count' => '参与比赛场次',
                    'match_list_count' => '发布比赛场次',
                ]);

                $sheet->rows($rows);
            });

        })->export('xls');
    }
}
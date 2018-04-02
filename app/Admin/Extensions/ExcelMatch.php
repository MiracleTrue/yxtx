<?php

namespace App\Admin\Extensions;

use App\Models\Match;
use Encore\Admin\Grid\Exporters\AbstractExporter;
use Maatwebsite\Excel\Facades\Excel;

class ExcelMatch extends AbstractExporter
{
    public function export()
    {
        Excel::create('比赛', function ($excel)
        {

            $excel->sheet('比赛', function ($sheet)
            {
                // 这段逻辑是从表格数据中取出需要导出的字段
                $rows = collect($this->getData())->map(function ($item)
                {
                    $item['reg_list_count'] = count($item['reg_list']);
                    $item['status'] = Match::statusTransformText($item['status']);
                    return array_only($item, [
                        'match_id',
                        'status',
                        'title',
                        'address_name',
                        'create_time',
                        'reg_list_count',
                    ]);
                });

                /*添加表头*/
                $rows->prepend([
                    'match_id' => 'ID',
                    'status' => '状态',
                    'title' => '标题',
                    'address_name' => '地点',
                    'create_time' => '创建时间',
                    'reg_list_count' => '参与人数',
                ]);

                $sheet->rows($rows);
            });

        })->export('xls');
    }
}
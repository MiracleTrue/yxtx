<?php

namespace App\Entity;

/**
 * Class WithdrawDeposit
 * Table 提现表
 * @package App\Entity
 */
class WithdrawDeposit extends Entity
{

    /*修改器:预留信息json转换array*/
    public function setInfoAttribute($arr_info)
    {
        if (is_array($arr_info))
        {
            $this->attributes['info'] = json_encode($arr_info);
        }
    }

    public function getInfoAttribute($json_info)
    {
        $arr = json_decode($json_info, true);

        if (is_array($arr))
        {
            return $arr;
        }
        else
        {
            return array();
        }
    }


    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'withdraw_deposit';

    /**
     * 可以通过 $primaryKey 属性，重新定义主键字段
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * 默认情况下，Eloquent预计数据表中有 "created_at" & "updated_at" 字段。
     * 不希望Eloquent字段维护这2个字段，可设置：$timestamps = false
     * @var bool
     */
    public $timestamps = false;

    /**
     * 需要自定义时间戳格式，可在模型内设置 $dateFormat 属性（决定了日期如何在数据库中存储，以及当模型被序列化成数组或JSON时的格式）
     * 格式为 date() 函数第一个参数，详情看手册
     * @var string
     */
    protected $dateFormat = "Y-m-d H:i:s";

    /**
     * 一对一关联Users实体表
     */
    public function user_info()
    {
        return $this->hasOne(Users::class, 'user_id', 'user_id');
    }

//    /**
//     * 一对多关联ProductsCategoryManage实体表
//     */
//    public function hm_product_category_manage()
//    {
//        return $this->hasMany(ProductsCategoryManage::class, 'user_id');
//    }
//
//    /**
//     * 一对多关联SupplierPrice实体表
//     */
//    public function hm_supplier_price()
//    {
//        return $this->hasMany(SupplierPrice::class, 'user_id');
//    }
//
//    /**
//     * 一对多关联SupplierPrice实体表
//     */
//    public function ho_supplier_price()
//    {
//        return $this->hasOne(SupplierPrice::class, 'user_id', 'user_id');
//    }

}

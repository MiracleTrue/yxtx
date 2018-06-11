<?php

namespace App\Entity;

/**
 * Class GoldGoods
 * Table 金币商城表
 * @package App\Entity
 */
class GoldGoods extends Entity
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'gold_goods';

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
    public $timestamps = true;

    /**
     * 需要自定义时间戳格式，可在模型内设置 $dateFormat 属性（决定了日期如何在数据库中存储，以及当模型被序列化成数组或JSON时的格式）
     * 格式为 date() 函数第一个参数，详情看手册
     * @var string
     */
    protected $dateFormat = "Y-m-d H:i:s";

    public function setPhotosAttribute($field)
    {
        if (is_array($field)) {
            $this->attributes['photos'] = json_encode($field);
        }
    }

    public function getPhotosAttribute($field)
    {
        return json_decode($field, true);
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

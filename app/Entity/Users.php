<?php

namespace App\Entity;

/**
 * Class Users
 * Table 用户表
 * @package App\Entity
 */
class Users extends Entity
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * 可以通过 $primaryKey 属性，重新定义主键字段
     * @var string
     */
    protected $primaryKey = 'user_id';

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
     * 一对多关联MatchList实体表
     */
    public function match_list()
    {
        return $this->hasMany(MatchList::class, 'user_id');
    }

    /**
     * 一对多关联PitRanking实体表
     */
    public function pit_list()
    {
        return $this->hasMany(PitRanking::class, 'user_id');
    }

    /**
     * 一对多关联GoldExchange实体表
     */
    public function gold_exchange()
    {
        return $this->hasMany(GoldExchange::class, 'user_id');
    }

    /**
     * 一对多关联SilverExchange实体表
     */
    public function silver_exchange()
    {
        return $this->hasMany(SilverExchange::class, 'user_id');
    }

    /**
     * 一对多关联MatchRegistration实体表
     */
    public function registration_list()
    {
        return $this->hasMany(MatchRegistration::class, 'user_id');
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

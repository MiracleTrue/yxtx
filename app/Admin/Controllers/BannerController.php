<?php

namespace App\Admin\Controllers;

use App\Entity\BannerList;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Encore\Admin\Controllers\ModelForm;

class BannerController extends Controller
{
    use ModelForm;

    /**
     * Index interface.
     *
     * @return Content
     */
    public function index()
    {
        return Admin::content(function (Content $content) {

            $content->header('Banner图管理');
            $content->breadcrumb(
                ['text' => 'Banner图管理']
            );
            $content->body($this->grid());
        });
    }

    /**
     * Edit interface.
     *
     * @param $id
     * @return Content
     */
    public function edit($id)
    {
        return Admin::content(function (Content $content) use ($id) {

            $content->header('Banner图编辑');
            $content->breadcrumb(
                ['text' => 'Banner图管理','url'=>'/banner'],
                ['text' => 'Banner图编辑']
            );

            $content->body($this->form()->edit($id));
        });
    }

    /**
     * Create interface.
     *
     * @return Content
     */
    public function create()
    {
        return Admin::content(function (Content $content) {

            $content->header('Banner图添加');
            $content->breadcrumb(
                ['text' => 'Banner图管理','url'=>'/banner'],
                ['text' => 'Banner图添加']
            );

            $content->body($this->form());
        });
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Admin::grid(BannerList::class, function (Grid $grid) {

            //禁用导出数据按钮
            $grid->disableExport();

            $grid->filter(function($filter){
                // 去掉默认的id过滤器
                $filter->disableIdFilter();
            });

            $grid->banner_id('ID');
            $grid->file_path('图片')->image('',100);
            $grid->sort('排序')->sortable();
            $grid->created_at('创建时间')->sortable();
            $grid->updated_at('更新时间')->sortable();
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Admin::form(BannerList::class, function (Form $form) {

            $form->display('banner_id', 'ID');
            $form->image('file_path','图片');
            $form->text('sort', '排序')->default(0);

        });
    }
}

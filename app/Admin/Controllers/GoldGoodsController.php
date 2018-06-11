<?php

namespace App\Admin\Controllers;


use App\Entity\GoldGoods;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Encore\Admin\Controllers\ModelForm;

class GoldGoodsController extends Controller
{
    use ModelForm;

    /**
     * Index interface.
     *
     * @return Content
     */
    public function index()
    {
        return Admin::content(function (Content $content)
        {

            $content->header('金币商品管理');
            $content->breadcrumb(
                ['text' => '金币商品管理']
            );
            $content->body($this->grid());
        });
    }

    public function show($id)
    {
        return Admin::content(function (Content $content) use ($id)
        {

            $content->header('金币商品查看');
            $content->breadcrumb(
                ['text' => '金币商品管理', 'url' => '/goldGoods'],
                ['text' => '金币商品查看']
            );

            $content->body($this->form()->view($id));
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
        return Admin::content(function (Content $content) use ($id)
        {

            $content->header('金币商品编辑');
            $content->breadcrumb(
                ['text' => '金币商品管理', 'url' => '/goldGoods'],
                ['text' => '金币商品编辑']
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
        return Admin::content(function (Content $content)
        {

            $content->header('金币商品添加');
            $content->breadcrumb(
                ['text' => '金币商品管理', 'url' => '/goldGoods'],
                ['text' => '金币商品添加']
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
        return Admin::grid(GoldGoods::class, function (Grid $grid)
        {

            //禁用导出数据按钮
            $grid->disableExport();

            $grid->model()->orderBy('sort', 'desc');

            $grid->filter(function ($filter)
            {
                // 去掉默认的id过滤器
                $filter->disableIdFilter();
            });

            $grid->id('ID');
            // 显示多图
            $grid->photos('相册')->display(function ($pictures)
            {
                return $pictures;
            })->image(config('filesystems.disks.local.url') . '/', 50, 50);
            $grid->title('标题');
            $grid->point('积分值')->sortable();
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
        return Admin::form(GoldGoods::class, function (Form $form)
        {

            $form->display('id', 'ID');
            $form->text('title', '标题')->rules('required');
            $form->text('point', '积分值')->rules('required');
            $form->multipleImage('photos', '相册')->removable();
            $form->editor('content', '内容')->rules('required');
            $form->number('sort', '排序')->default(0)->rules('required');
        });
    }
}

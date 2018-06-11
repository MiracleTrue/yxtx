<?php

namespace App\Admin\Controllers;


use App\Entity\RankingBanner;
use App\Models\MyFile;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Encore\Admin\Controllers\ModelForm;

class RankingBannerController extends Controller
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

            $content->header('排行Banner图管理');
            $content->breadcrumb(
                ['text' => '排行Banner图管理']
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
        return Admin::content(function (Content $content) use ($id)
        {

            $content->header('排行Banner图编辑');
            $content->breadcrumb(
                ['text' => '排行Banner图管理', 'url' => '/rankingBanner'],
                ['text' => '排行Banner图编辑']
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

            $content->header('排行Banner图添加');
            $content->breadcrumb(
                ['text' => '排行Banner图管理', 'url' => '/rankingBanner'],
                ['text' => '排行Banner图添加']
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
        return Admin::grid(RankingBanner::class, function (Grid $grid)
        {

            //禁用导出数据按钮
            $grid->disableExport();

            $grid->filter(function ($filter)
            {
                // 去掉默认的id过滤器
                $filter->disableIdFilter();
            });

            $grid->banner_id('ID');
            $grid->file_path('图片')->image('', 100);
            $grid->video_path('视频')->display(function ($data)
            {
                if (!empty($data))
                {
                    return "<a target='_blank' href='" . MyFile::makeUrl($data) . "'>观看视频</a>";
                }
                else
                {
                    return '';
                }
            });
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
        return Admin::form(RankingBanner::class, function (Form $form)
        {

            $form->display('banner_id', 'ID');
            $form->image('file_path', '图片')->uniqueName()->help('上传图片最佳比例:  1 * 0.64  像素');
            $form->file('video_path', '视频')->uniqueName()->rules('mimes:mp4')->removable();// 并设置上传文件类型
            $form->text('sort', '排序')->default(0);
            $form->editor('content', '内容');

        });
    }
}

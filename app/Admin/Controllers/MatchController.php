<?php

namespace App\Admin\Controllers;

use App\Admin\Extensions\Ajax_Delete;
use App\Entity\MatchList;
use App\Entity\MatchRegistration;
use App\Entity\Users;
use App\Models\Match;
use App\Models\Registration;
use App\Tools\M3Result;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Layout\Content;
use Encore\Admin\Controllers\ModelForm;
use Encore\Admin\Widgets\Tab;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MatchController extends Controller
{
    use ModelForm;


    public function map($match_id)
    {
        return Admin::content(function (Content $content) use ($match_id)
        {
            $content->header('比赛地图');
            $content->breadcrumb(
                ['text' => '比赛管理', 'url' => '/match'],
                ['text' => '比赛地图']
            );

            $match = new Match();
            $match_info = $match->getMatchInfo($match_id);
            $content->body(
                Admin::form(MatchList::class, function (Form $form) use ($match_info)
                {
                    $form->tools(function (Form\Tools $tools)
                    {
                        // 去掉跳转列表按钮
                        $tools->disableListButton();
                    });
                    $form->display('address_name', '比赛地址');

                    $lat = strval($match_info->address_coordinate['lat']);

                    $lng = strval($match_info->address_coordinate['lng']);

//                    dd($lat, $lng);

                    $form->tencent_map($lat, $lng, '比赛地图');
                })->view($match_id)
            );
        });
    }

    /**
     * 比赛详情
     * @param $match_id
     * @return Content
     */
    public function show($match_id)
    {
        return Admin::content(function (Content $content) use ($match_id)
        {
            $content->header('比赛详情');
            $content->breadcrumb(
                ['text' => '比赛管理', 'url' => '/match'],
                ['text' => '比赛详情']
            );

            $content->body($this->match_list_view()->view($match_id));
        });
    }

    /**
     * 我的比赛
     * @param $user_id
     * @return Content
     */
    public function user($user_id)
    {
        return Admin::content(function (Content $content) use ($user_id)
        {
            $e_users = Users::findOrFail($user_id);

            $content->header($e_users->nick_name . '的比赛');
            $content->breadcrumb(
                ['text' => '比赛管理', 'url' => '/match'],
                ['text' => $e_users->nick_name . '的比赛']
            );

            $tab = new Tab();
            $tab->add('我发布的比赛', $this->match_list([['user_id', $user_id]]));
            $tab->add('我参与的比赛', $this->match_registration([['user_id', $user_id]]));

            $content->body($tab->render());
        });
    }

    /**
     * 删除
     * @param Request $request
     * @return \App\Tools\json
     * @throws \Throwable
     */
    public function delete(Request $request)
    {
        /*初始化*/
        $m3result = new M3Result();
        $match = new Match();

        /*验证*/
        $rules = [
            'id' => [
                'required',
                'integer',
                Rule::exists('match_list', 'match_id')->where(function ($query)
                {
                    $query->where('is_delete', Match::NO_DELETE);
                }),
            ]
        ];
        $validator = Validator::make($request->all(), $rules);

        //无人报名的比赛
        $e_match_registration = MatchRegistration::where('match_id', $request->input('id'))->get()->isEmpty();

        /*处理并返回*/
        if ($validator->passes() && $e_match_registration && $match->deleteMatch($request->input('id')))
        {   /*验证通过并且处理成功*/
            $m3result->code = 0;
            $m3result->messages = '比赛删除成功';
        }
        else
        {
            $m3result->code = 1;
            $m3result->messages = '比赛删除失败';
        }

        return $m3result->toJson();
    }


    /**
     * 列表
     * @return Content
     */
    public function index()
    {
        return Admin::content(function (Content $content)
        {

            $content->header('比赛管理');
            $content->breadcrumb(
                ['text' => '比赛管理']
            );
            $content->body($this->match_list());
        });
    }


    protected function match_list($where = array())
    {
        return Admin::grid(MatchList::class, function (Grid $grid) use ($where)
        {

            $grid->model()->where($where);

            /*筛选*/
            if (empty($where))
            {
                $grid->filter(function ($filter)
                {
                    // 去掉默认的id过滤器
                    $filter->disableIdFilter();

                    //会员名称或者手机号
                    $filter->where(function ($query)
                    {
                        $query->whereHas('release_user', function ($query)
                        {
                            $query->where('nick_name', 'like', "%{$this->input}%")->orWhere('phone', 'like', "%{$this->input}%");
                        });
                    }, '会员名称或者手机号');

                    //状态
                    $filter->equal('status', '状态')->select([
                        Match::STATUS_SIGN_UP => Match::statusTransformText(Match::STATUS_SIGN_UP),
                        Match::STATUS_GET_NUMBER => Match::statusTransformText(Match::STATUS_GET_NUMBER),
                        Match::STATUS_END => Match::statusTransformText(Match::STATUS_END),
                    ]);
                });
            }
            else
            {
                //禁用查询过滤器
                $grid->disableFilter();
            }

            //禁用导出数据按钮
            $grid->disableExport();

            //禁用创建按钮
            $grid->disableCreateButton();

            //禁用行选择checkbox
            $grid->disableRowSelector();

            $grid->match_id('ID');
            $grid->release_user()->nick_name('发布人')->display(function ($data)
            {
                return "<a href='" . url('admin/match/user', $this->user_id) . "'>$data</a>";
            });
            $grid->create_time('发布时间')->sortable();
            $grid->reg_list('参与人数')->display(function ($data)
            {
                return count($this->reg_list);
            });
            $grid->address_name('地点')->display(function ($data)
            {
                return "<a href='" . url('admin/match/map', $this->match_id) . "'>$data</a>";
            });
            $grid->column('状态')->display(function ()
            {
                return Match::statusTransformText($this->status) . "(" . count($this->reg_list) . "/$this->match_sum_number)";
            });

            /*自定义操作*/
            $grid->actions(function ($actions)
            {
                $actions->disableDelete();
                $actions->disableEdit();
                $actions->append('<a href="' . url('admin/match', $actions->row->match_id) . '" class="btn btn-sm btn-info" style="margin-right: 10px"><i class="fa fa-eye"></i> 比赛详情</a>');

                if (empty($actions->row->reg_list))//无人报名的比赛
                {
                    $actions->append(new Ajax_Delete(url('admin/match/delete'), $actions->getKey()));
                }

            });
        });
    }

    protected function match_registration($where = array())
    {
        return Admin::grid(MatchRegistration::class, function (Grid $grid) use ($where)
        {

            $grid->model()->where($where);

            //禁用导出数据按钮
            $grid->disableExport();

            //禁用创建按钮
            $grid->disableCreateButton();

            //禁用查询过滤器
            $grid->disableFilter();

            //禁用行选择checkbox
            $grid->disableRowSelector();

            $grid->match_info()->match_id('ID');
            $grid->create_time('报名时间')->sortable();
            $grid->match_info()->reg_list('参与人数')->display(function ($data)
            {
                return count($this->reg_list);
            });
            $grid->match_info()->address_name('地点')->display(function ($data)
            {
                return "<a href='" . url('admin/match/map', $this->match_id) . "'>$data</a>";
            });
            $grid->column('状态')->display(function ()
            {
                return Registration::statusTransformText($this->status);
            });

            //禁用行操作列
            $grid->disableActions();
        });
    }


    protected function match_list_view()
    {
        return Admin::form(MatchList::class, function (Form $form)
        {
            $form->display('title', '比赛标题');
            $form->display('need_money', '价格');
            $form->display('release_user.nick_name', '发布人');
            $form->display('release_user.phone', '手机号码');
            $form->display('match_content', '比赛介绍');
            $form->display('match_start_time', '比赛时间')->with(function ($value)
            {
                return $this->match_start_time . ' ~ ' . $this->match_end_time;
            });
            $form->display('address_name', '比赛地址')->with(function ($value)
            {
                return "<a href='" . url('admin/match/map', $this->match_id) . "'>$value</a>";
            });
            $form->display('fish_number', '放鱼数量');
            $form->multipleImage('match_photos', '比赛图片');

            $form->display('reg_list', '报名情况')->help('如果抽号后可以显示括号内数字，没抽号不显示')->with(function ($data)
            {
                $text = '';
                foreach ($data as $value)
                {
                    $text .= "<a style='margin-right:20px;' href='" . url('admin/match/user', $value['user_id']) . "'>" .$value['real_name'] . "($value[match_number])" . "</a>";
                }
                return $text;
            });


        });
    }

}

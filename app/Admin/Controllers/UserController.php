<?php

namespace App\Admin\Controllers;

use App\Models\User;
use DLP\Tool\Assistant;
use DLP\Widget\Plane;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Database\Eloquent\Builder;

class UserController extends AdminController
{
    protected $title = '用户管理';

    protected function grid()
    {
        $grid = new Grid(new User());
        $now = time();
        $date = date('Y-m-d H:i:s', $now);
        $grid->model()->orderBy('create_time', 'DESC');
        if (isset($_GET['vip'])) {
            if ($_GET['vip'] === '1') {
                $grid->model()->whereHas('member', function (Builder $query) {
                    $query->where('maturity_time', '>', date('Y-m-d H:i:s'));
                });
            }
        }

        $grid->column('id', __('ID'))->sortable();
        $grid->column('name', '用户名称');
        $grid->column('avatar', '头像')->image('', 100, 100);
        $grid->column('mail', '邮箱');
        $grid->column('status', '状态')->using([1 => '正常', 2 => '封禁'])->dot([
            1 => 'primary',
            2 => 'danger',
        ], 'info');
        $grid->column('ip', __('注册IP'));
        $grid->column('type', __('注册来源'))->using([
            "1" => "邮箱",
            "2" => "Facebook",
            "3" => "Google",
            "4" => "Twitter"
        ]);
        $grid->column('login_time', __('最后登录'));
        $grid->column('create_time', '创建时间')->display(function ($d) {
            return date('Y-m-d H:i:s', strtotime($d));
        })->sortable();
        $grid->column('update_time', '更新时间')->display(function ($d) {
            return date('Y-m-d H:i:s', strtotime($d));
        })->sortable();
        /*配置*/
        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->disableRowSelector();
        /*查询匹配*/
        $grid->filter(function ($filter) {
            $filter->like('name', '名称');
            $filter->like('mail', '邮箱');
            $filter->where(function ($query) {
            }, '会员状态', 'vip')->radio([
                '' => '全部',
                1 => 'VIP'
            ]);
            $filter->equal('type', '注册来源')->select([
                "1" => "邮箱",
                "2" => "Facebook",
                "3" => "Google",
                "4" => "Twitter"
            ]);
            $filter->equal('status', '状态')->select([1 => '正常', 2 => '封禁']);
            $filter->between('login_time', '登录时间')->datetime();
            $filter->between('create_time', '创建时间')->datetime();
        });
        /*弹窗配置*/
        $url = rtrim(config('app.url'), '/') . '/' . Route::current()->uri;
        $grid->actions(function ($actions) use ($url) {
            $actions->disableView();
            $actions->disableEdit();
            $actions->disableDelete();

            $actions->add(Plane::rowAction('开通会员', $url . '/{id}/edit', ['url' => $url . '/{id}'], ['w' => 0.6]));
        });
        return $grid;
    }

    public function edit($id, Content $content)
    {
        $content = $content
            ->body($this->form($id)->edit($id));
        return Plane::form($content);
    }

    public function update($uid)
    {
        $request = Request::capture();
        $combo = $request->input('combo');
        try {

        } catch (\Exception $e) {
            DB::rollBack();
            return Assistant::result(false, $e->getMessage());
        }
        DB::commit();
        return Assistant::result(true);
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form($id = '')
    {
        $form = new Form(new User());
        $form->display('id', 'ID');
        $form->display('name', '用户名称');

        $user = User::where('id',$id)->first();
        $options = [
            "1" => "邮箱",
            "2" => "Facebook",
            "3" => "Google",
            "4" => "Twitter"
        ];
        $form->html("<p style='height:30px;line-height: 30px'>{$options[$user->type]}</p>",'注册类型');

        $options = [
            "1" => "正常",
            "2" => "封禁"
        ];
        $form->html("<p style='height:30px;line-height: 30px'>{$options[$user->status]}</p>",'状态');

        /*配置*/
        CommonController::disableDetailConf($form);
        return $form;
    }
}

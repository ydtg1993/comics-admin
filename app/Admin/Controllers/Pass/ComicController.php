<?php


namespace App\Admin\Controllers\Pass;


use App\Admin\Controllers\CommonController;
use App\Models\Comic;
use DLP\Tool\Assistant;
use DLP\Widget\Plane;
use Encore\Admin\Admin;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComicController extends AdminController
{
    protected $title = '漫画';

    protected function grid()
    {
        $grid = new Grid(new Comic());
        $grid->model()->orderBy('created_at','DESC');

        $grid->column('id', __('ID'))->sortable();
        $grid->column('title', '标题');
        Admin::script("_component.imgDelay('.cover',200,true);");
        $grid->column('cover', '封面')->display(function ($v){
            if(preg_match("/^http/",$v)){
                $url = $v;
            }else {
                $url = env('IMG_DOMAIN') . '/' . $v;
            }
            return "<div style='width:100px;height:60px'><img data-src='{$url}' class='cover img img-thumbnail' style='max-width:100px;height: 100%;' /></div>";
        });
        $grid->column('category', '分类');
        $grid->column('region', '地区');
        $grid->column('is_finish', '连载状态')->using([0 => '连载中', 1 => '完结']);
        $grid->column('chapter_count', '章节数量');
        $grid->column('created_at', '创建时间')->sortable();
        $grid->column('updated_at', '更新时间')->sortable();
        $grid->column('章节列表')->display(function () {
            return "<a href='/admin/chapter?comic_id={$this->id}'>章节列表</a>";
        });
        /*配置*/
        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->disableRowSelector();
        $grid->actions(function ($actions){
            $actions->disableView();
            $actions->disableEdit();
            $actions->disableDelete();
            $url = CommonController::getCurrentUrl();
            $actions->add(Plane::rowAction('编辑', $url."/{$actions->row->id}/edit", ['url' => $url."/{$actions->row->id}"]));
        });

        /*查询匹配*/
        $grid->filter(function ($filter) {
            $filter->like('title', '标题');
            $filter->equal('region', '地区查询');
            $filter->equal('is_finish', '连载状态')->select([0 => '连载中', 1 => '完结']);
            $filter->between('create_at', '创建时间')->datetime();
        });
        return $grid;
    }

    public function edit($id, Content $content)
    {
        $content = $content
            ->body($this->form($id)->edit($id));
        return Plane::form($content);
    }

    public function update($id)
    {
        $request = Request::capture();
        $data = $request->all();
        try {
            DB::beginTransaction();
            if (!$data['title']) throw new \Exception('标题参数必填');

        } catch (\Exception $e) {
            DB::rollBack();
            return Assistant::result(false, $e->getMessage());
        }
        DB::commit();
        return Assistant::result(true);
    }

    protected function form($id='')
    {
        $form = new Form(new Comic());
        /*配置*/
        CommonController::disableDetailConf($form);
        $form->builder()->setTitle('编辑漫画');
        $form->display('id', 'ID');
        $form->text('title', '标题')->required();
        $form->radio('status', '上下架')->options([0 => '上架中', 1 => '下架'])->default(0);
        $form->display('category', '分类');
        $form->display('region', '地区');
        $form->display('author', '作者');
        $form->text('like', '喜欢');
        $form->text('popularity', '人气热度');
        $form->image('cover', '封面')->options(['maxFileSize' => 1024]);
        $form->textarea('description','内容简介');
        return $form;
    }
}

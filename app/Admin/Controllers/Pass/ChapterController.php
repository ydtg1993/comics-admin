<?php


namespace App\Admin\Controllers\Pass;


use App\Admin\Controllers\CommonController;
use App\Models\Chapter;
use App\Models\Comic;
use DLP\Tool\Assistant;
use DLP\Widget\Plane;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChapterController extends AdminController
{
    protected $title = '章节';

    protected function grid()
    {
        $grid = new Grid(new Chapter());
        $grid->model()->orderBy('sort','DESC');
        $grid->column('id', __('ID'))->sortable();
        $grid->column('comic_id', "漫画")->display(function ($comic_id){
            $comic = Comic::where('id',$comic_id)->first();
            return $comic->title;
        });
        $grid->column('title', '标题');
        $grid->column('is_free', '付费状态')->using([0 => '免费', 1 => '收费']);
        $grid->column('sort', '排序')->sortable();
        $grid->column('created_at', '创建时间')->sortable();
        $grid->column('updated_at', '更新时间')->sortable();
        /*配置*/
        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->disableRowSelector();
        $grid->actions(function ($actions){
            $actions->disableView();
            $actions->disableEdit();
            $actions->disableDelete();
            $url = CommonController::getCurrentUrl();
            $actions->add(Plane::rowAction('资源信息', $url."/{$actions->row->id}/edit", ['url' => $url."/{$actions->row->id}"]));
        });

        /*查询匹配*/
        $grid->filter(function ($filter) {
            $filter->equal('comic_id', '漫画id');
            $filter->like('title', '标题');
            $filter->equal('is_free', '付费状态')->select([0 => '免费', 1 => '收费']);
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
        $form = new Form(new Chapter());
        /*配置*/
        CommonController::disableDetailConf($form);
        $form->builder()->setTitle('审核章节');
        $form->display('id', 'ID');
        $form->text('title', '标题');
        $form->multipleImage('images', '图片资源');
        return $form;
    }
}

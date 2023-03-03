<?php


namespace App\Admin\Controllers\Review;


use App\Admin\Controllers\CommonController;
use App\Models\SourceChapter;
use App\Models\SourceComic;
use DLP\Tool\Assistant;
use DLP\Widget\Plane;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SourceChapterController extends AdminController
{
    protected $title = '章节审核';

    protected function grid()
    {
        $grid = new Grid(new SourceChapter());
        $grid->model()->orderBy('sort','DESC')->orderBy('status', 'ASC');
        $grid->column('id', __('ID'))->sortable();
        $grid->column('comic_id', "漫画")->display(function ($comic_id){
            $comic = SourceComic::where('id',$comic_id)->first();
            return $comic->title;
        });
        $grid->column('title', '标题')->width(220);
        $grid->column('status', '审核')->using([0 => '待审核', 1 => '通过'])->dot([0 => 'info', 1 => 'success']);
        $grid->column('is_free', '付费状态')->using([0 => '免费', 1 => '收费'])->dot([0 => 'success',1=>'danger']);
        $grid->column('source', '采集源')->display(function ($v){
            $source_url = $this->source_url;
            if ($v == 1) {
                return "<a href='$source_url' target='_blank'>快看</a>";
            }else{
                return "<a href='$source_url' target='_blank'>腾讯</a>";
            }
        });
        $grid->column('image.state', '资源状态')->display(function ($v){
            if ($v == 1){
                return "已下载";
            }
            return "未下载";
        });
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
            $actions->add(Plane::rowAction('查看章节', $url."/{$actions->row->id}/edit", ['url' => $url."/{$actions->row->id}"]));
        });

        /*查询匹配*/
        $grid->filter(function ($filter) {
            $filter->equal('comic_id', '漫画id');
            $filter->like('title', '标题');
            $filter->equal('source', '采集源')->select([1 => '快看', 2 => '腾讯']);
            $filter->equal('is_free', '付费状态')->select([0 => '免费', 1 => '收费']);
            $filter->equal('status', '审核状态')->select([0 => '待审核', 1 => '通过']);
            $filter->where(function ($query) {
                if($this->input == 1){
                    $query->whereHas('image', function ($query) {
                        $query->where('state', 1);
                    });
                }else {
                    $query->whereHas('image', function ($query) {
                        $query->where('state', 0);
                    })->orDoesntHave('image');
                }
            }, '资源状态')->select([0 => '未下载', 1 => '已下载']);
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
            $chapter = SourceChapter::where('id',$id)->first();
            if(!$chapter->image || $chapter->image->state == 0) throw new \Exception('未下载完成');

        } catch (\Exception $e) {
            DB::rollBack();
            return Assistant::result(false, $e->getMessage());
        }
        DB::commit();
        return Assistant::result(true);
    }

    protected function form($id='')
    {
        $form = new Form(new SourceChapter());
        /*配置*/
        CommonController::disableDetailConf($form);
        $form->builder()->setTitle('审核章节');
        $form->display('id', 'ID');
        $form->display('title', '标题');
        $form->radio('status', '审核状态')->options([0 => '待审核', 1 => '通过'])->default(0);
        $chapter = SourceChapter::where('id',$id)->with('image')->first()->toArray();
        $form->html(<<<EOF
<div style="display: flex;height: 55px;align-content: center;overflow: auto;white-space: nowrap">
<div style="height: 35px;line-height: 35px;margin-right: 10px"><b>采集源</b>: {$chapter['source']}</div>
<div style="height: 35px;line-height: 35px;margin-right: 10px"><b>源章节id</b>: {$chapter['source_chapter_id']}</div>
<div style="height: 35px;line-height: 35px;margin-right: 10px"><b>源url</b>: {$chapter['source_url']}</div>
<div style="height: 35px;line-height: 35px;margin-right: 10px"><b>漫画ID</b>: {$chapter['comic_id']}</div>
</div>
EOF
            , '数据源信息');
        $source = [];
        if(isset($chapter['image'])) {
            foreach ($chapter['image']['source_data'] as $k=>$url) {
                $source[] = ['sort'=>(string)$k,'url' => $url];
            }
            $form->Linear('source_data','原站资源地址')->columns([
                'sort' => ['name' => '序号', 'type' => 'text','style'=>'width:45px'],
                'url' => ['name' => '地址', 'type' => 'input']
            ])->list($source)
                ->attribute(['height' => '200px','sortable' => false, 'delete' => false, 'insert' => false]);
        }
        $form->multipleImage('image.images', '图片资源');
        $form->disableSubmit(true);
        return $form;
    }
}

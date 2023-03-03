<?php


namespace App\Admin\Controllers\Review;


use App\Models\Fail;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;

class SourceFailController extends AdminController
{
    protected $title = '采集错误日志';

    protected function grid()
    {
        $grid = new Grid(new Fail());
        $grid->model()->orderBy('created_at', 'DESC');

        $grid->column('id', __('ID'))->sortable();

        $grid->column('source', '采集源')->using([1 => '快看',2 => '腾讯']);
        $grid->column('type', '分类')->using([1=>'漫画', 2=>'章节', 3=>'图片']);
        $grid->column('err', '错误关键词');
        $grid->column('url', '地址')->width("200");
        $grid->column('info', '失败信息记录')->width("300");
        $grid->column('created_at', '创建时间')->sortable();
        /*配置*/
        $grid->disableCreateButton();
        $grid->disableExport();
        $grid->disableRowSelector();
        $grid->disableActions();

        /*查询匹配*/
        $grid->filter(function ($filter) {
            $filter->equal('err', '错误关键词');
            $filter->equal('url', '地址');
            $filter->equal('source', '采集源')->select([1 => '快看', 2 => '腾讯']);
            $filter->equal('type', '分类')->select([1=>'漫画', 2=>'章节', 3=>'图片']);
            $filter->between('create_at', '创建时间')->datetime();
        });
        return $grid;
    }
}

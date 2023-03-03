<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use DLP\Tool\Assistant;
use Encore\Admin\Admin;
use Encore\Admin\Layout\Column;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class PawController extends Controller
{
    private $SourceChapterTASK = "source:comic:chapter";
    private $SourceChapterRetryTask = "source:comic:retry:chapter";

    private $SourceComicTASK = "source:comic:task";
    private $SourceComicRetryTask = "source:comic:retry:task";

    private $SourceImageTASK = "source:chapter:image";

    private $SourceImageCapture = "source:image:capture";
    private $SourceImageDownload = "source:image:download";

    private $TaskStepRecord = "task:step:record";

    private $StopRobotSignal = "shutdown";


    public function index(Content $content)
    {
        return $content
            ->title('Dashboard')
            ->row(function (Row $row) {
                $url = CommonController::getCurrentUrl();

                Admin::html(<<<EOF
<script>
class ticker {
    constructor(id,source) {
       this.Dom = document.getElementById(id);
       this.Dom.parentNode.style.height = "300px";
       this.Dom.parentNode.style.overflowY = "scroll";
       this.Dom2 = document.getElementById(id+'_task');
       this.Dom2.parentNode.style.height = "300px";
       this.request(source);
       setInterval(()=>{
           this.request(source)
           },30000);
    }

    request(source){
        let that = this;
        $.ajax({
            method: 'post',
            url: '{$url}.cache',
            data: {
                _method:'post',
                _token:document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                source:source
            },
            success: function (data) {
                if (data.code === 0){
                    that.Dom.firstChild.innerHTML = '';
                    let res = data.data;
                    for (let record of res["{$this->TaskStepRecord}"].list){
                        that.Dom.firstChild.insertAdjacentHTML('beforeend', '<tr><td>'+record+'</td></tr>');
                    }

                    that.Dom2.firstChild.innerHTML = '';

                    function x(dom,title,task) {
                        let panel = '<tr><td>'+task+title+'</td><td>'+ res[task].len+
                    '</td><td>'+res[task].list+'...</td></tr>';
                        dom.insertAdjacentHTML('beforeend', panel);
                    }
                    x(that.Dom2.firstChild,"漫画任务","{$this->SourceComicTASK}")
                    x(that.Dom2.firstChild,"错误漫画任务","{$this->SourceComicRetryTask}")
                    x(that.Dom2.firstChild,"章节任务","{$this->SourceChapterTASK}")
                    x(that.Dom2.firstChild,"错误章节任务","{$this->SourceChapterRetryTask}")
                    x(that.Dom2.firstChild,"图片任务","{$this->SourceImageTASK}")

                        that.Dom2.firstChild.insertAdjacentHTML('beforeend', '<tr><td>图片抓取进程</td><td>'+ "{$this->SourceImageCapture}"+
                    '</td><td>'+res["{$this->SourceImageCapture}"].cache+'</td></tr>');

                        that.Dom2.firstChild.insertAdjacentHTML('beforeend', '<tr><td>图片下载进程</td><td>'+ "{$this->SourceImageDownload}"+
                    '</td><td>'+res["{$this->SourceImageDownload}"].cache+'</td></tr>');
                }
            }
        });
    }
}
new ticker('kk','kk','{$this->TaskStepRecord}');
new ticker('tx','tx','{$this->TaskStepRecord}');
</script>
EOF
                );

                Admin::html(<<<EOF
<script>
function triggerStop(dom,source,cache) {
  dom.addEventListener('click',()=>{
         dom.setAttribute("disabled","disabled");
         $.ajax({
            method: 'post',
            url: '{$url}.set.cache',
            data: {
                _method:'post',
                _token:document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                source:source,
                cache:cache,
            },
            success: function (data) {
                if (data.code === 0){
                    dom.removeAttribute("disabled");
                    let table = document.createElement("table");
                    table.className = "table table-hover grid-table";
                    table.innerHTML = "<tbody></tbody>";
                    dom.after(table);
                    setInterval(()=>{
                         $.ajax({
                            method: 'post',
                            url: '{$url}.cache',
                            data: {
                                _method:'post',
                                _token:document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                source:source,
                                stop:1
                            },
                            success: function (data) {
                                if (data.code === 0){
                                    table.firstChild.innerHTML = '';
                                    let res = data.data;
                                    for (let record of res["{$this->StopRobotSignal}"].list){
                                        table.firstChild.insertAdjacentHTML('beforeend', '<tr><td>'+record+'</td></tr>');
                                    }
                                }
                            }
                        });
                    },2000);
                }
            }
        });
  })
}
triggerStop(document.getElementById('stop-kk'),'kk','{$this->StopRobotSignal}');
triggerStop(document.getElementById('stop-tx'),'tx','{$this->StopRobotSignal}');
</script>
EOF
                );

                Admin::html(<<<EOF
<script>
function triggerReset(dom,source,cache) {
  dom.addEventListener('click',()=>{
         dom.setAttribute("disabled","disabled");
         $.ajax({
            method: 'post',
            url: '{$url}.set.cache',
            data: {
                _method:'post',
                _token:document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                source:source,
                cache:cache,
            },
            success: function () {
                dom.removeAttribute("disabled");
            },
            timeout: 60000
        });
  })
}
triggerReset(document.getElementById('reset-comic-kk'),'kk','{$this->SourceComicRetryTask}');
triggerReset(document.getElementById('reset-comic-tx'),'tx','{$this->SourceComicRetryTask}');
triggerReset(document.getElementById('reset-chapter-kk'),'kk','{$this->SourceChapterRetryTask}');
triggerReset(document.getElementById('reset-chapter-tx'),'tx','{$this->SourceChapterRetryTask}');
</script>
EOF
                );
                $row->column(3, function (Column $column) {
                    $column->append((new Box("快看爬虫进程",
                        "<btn class='btn btn-danger btn-sm' id='stop-kk'>停止</btn>
<table id='kk' class='table table-hover grid-table'><tbody></tbody></table>")));
                    $column->append((new Box("腾讯爬虫进程",
                        "<btn class='btn btn-danger btn-sm' id='stop-tx'>停止</btn>
<table id='tx' class='table table-hover grid-table'><tbody></tbody></table>")));
                });

                $row->column(9, function (Column $column) {
                    $column->append((new Box("快看爬虫任务",
                        "<btn class='btn btn-info btn-sm' id='reset-comic-kk'>错误漫画任务重置</btn>
<btn class='btn btn-info btn-sm' id='reset-chapter-kk'>错误章节任务重置</btn>
<table id='kk_task' class='table table-hover grid-table'><tbody></tbody></table>"))
                        ->style('primary'));
                    $column->append((new Box("腾讯爬虫任务",
                        "<btn class='btn btn-info btn-sm' id='reset-comic-tx'>错误漫画任务重置</btn>
<btn class='btn btn-info btn-sm' id='reset-chapter-tx'>错误章节任务重置</btn>
<table id='tx_task' class='table table-hover grid-table'><tbody></tbody></table>"))
                        ->style('primary'));
                });
            });
    }

    public function getPawCache(Request $request)
    {
        $source = $request->input('source');
        $redis = Redis::connection($source);

        if($request->has('stop')){
            $data = [$this->StopRobotSignal => $this->getCacheList($redis, $this->StopRobotSignal)];
        }else {
            $data = [
                $this->SourceComicTASK => $this->getCacheList($redis, $this->SourceComicTASK),
                $this->SourceComicRetryTask => $this->getCacheList($redis, $this->SourceComicRetryTask),
                $this->SourceChapterTASK => $this->getCacheList($redis, $this->SourceChapterTASK),
                $this->SourceChapterRetryTask => $this->getCacheList($redis, $this->SourceChapterRetryTask),
                $this->SourceImageTASK => $this->getCacheList($redis, $this->SourceImageTASK),
                $this->TaskStepRecord => $this->getCacheList($redis, $this->TaskStepRecord),
                $this->SourceImageCapture => $this->getCacheList($redis, $this->SourceImageCapture),
                $this->SourceImageDownload => $this->getCacheList($redis, $this->SourceImageDownload),
            ];
        }

        return Assistant::result(true, 'ok', $data);
    }

    private function getCacheList($redis, $key)
    {
        $data = [];
        switch ($key) {
            case $this->SourceComicTASK:
            case $this->SourceComicRetryTask:
            case $this->SourceChapterTASK:
            case $this->SourceChapterRetryTask:
            case $this->SourceImageTASK:
                $data['len'] = $redis->llen($key);
                $data['list'] = $redis->lrange($key, 0, 5);
                break;
            case $this->TaskStepRecord:
                $data['len'] = $redis->llen($key);
                $data['list'] = $redis->lrange($key, $data['len'] - 5, -1);
                break;
            case $this->StopRobotSignal:
                $data['list'] = $redis->lrange($key, 0, -1);
                break;
            case $this->SourceImageCapture:
            case $this->SourceImageDownload:
                $data['cache'] = $redis->get($key);
                break;
        }
        return $data;
    }

    public function setPawCache(Request $request)
    {
        $source = $request->input('source');
        $redis = Redis::connection($source);
        $cache = $request->input('cache');
        set_time_limit(0);
        switch ($cache){
            case $this->StopRobotSignal:
                $redis->lpush($this->StopRobotSignal,'停止爬虫');
                break;
            case $this->SourceComicRetryTask:
                while (true){
                    $retry = $redis->rpop($this->SourceComicRetryTask);
                    if ($retry !== false && $retry !== ""){
                        $redis->rpush($this->SourceComicTASK,$retry);
                    }else{
                        break;
                    }
                }
                break;
            case $this->SourceChapterRetryTask:
                while (true){
                    $retry = $redis->rpop($this->SourceChapterRetryTask);
                    if ($retry !== false && $retry !== ""){
                        $redis->rpush($this->SourceChapterTASK,$retry);
                    }else{
                        break;
                    }
                }
                break;
        }
        return Assistant::result(true);
    }
}

<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Language;
use DLP\Tool\FormPanel;
use DLP\Widget\Plane;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;

class CommonController extends Controller
{
    public static function disableDetailConf(&$form)
    {
        $form->tools(function (Form\Tools $tools) {
            // 去掉`列表`按钮
            $tools->disableList();
            // 去掉`删除`按钮
            $tools->disableDelete();
            // 去掉`查看`按钮
            $tools->disableView();
        });
        $form->footer(function ($footer) {
            // 去掉`重置`按钮
            $footer->disableReset();
            // 去掉`查看`checkbox
            $footer->disableViewCheck();
            // 去掉`继续编辑`checkbox
            $footer->disableEditingCheck();
            // 去掉`继续创建`checkbox
            $footer->disableCreatingCheck();
        });
    }

    /**
     * @param $file
     * @param string $dirname
     * @param int $chunk
     * @param null $allowed_extensions ["png", "jpg", "jpeg", "gif", "mpg", "mpeg", "image/gif", "image/jpeg", "image/png", "video/mpeg"]
     * @param bool $name_encode
     * @return string
     * @throws \Exception
     */
    public static function upload($file, $dirname = '', $chunk = 128, $allowed_extensions = null, $name_encode = false)
    {
        $mm = $file->getMimeType();

        //检查文件是否上传完成
        if (is_array($allowed_extensions) && !in_array($mm, $allowed_extensions)) {
            throw new \Exception('文件格式错误');
        }
        $base_dir = rtrim(public_path('resources'), '/') . '/';
        $chunk_dir = mt_rand(1, $chunk);
        $newDir = $base_dir . $dirname . '/' . $chunk_dir . '/';
        if (!is_dir($newDir)) {
            mkdir($newDir, 0777, true);
            chmod($newDir, 0777);
        }
        if ($name_encode) {
            $newFile = $file->getClientOriginalName();
        } else {
            $newFile = substr(md5($file->getPathname()), 26, 32) . mt_rand(100, 999) . "." . $file->getClientOriginalExtension();
        }
        $res = move_uploaded_file($file->getPathname(), $newDir . $newFile);
        if (!$res) {
            throw new \Exception('文件存储失败');
        }

        return $dirname . '/' . $chunk_dir . '/' . $newFile;
    }

    /**
     * @param $files
     * @param string $dirname
     * @param int $chunk
     * @param null $allowed_extensions ["png", "jpg", "jpeg", "gif", "mpg", "mpeg", "image/gif", "image/jpeg", "image/png", "video/mpeg"]
     * @return array
     * @throws \Exception
     */
    public static function multiUpload($files, $dirname = '', $chunk = 128, $allowed_extensions = null)
    {
        $base_dir = rtrim(public_path('resources'), '/') . '/';
        $chunk_dir = mt_rand(1, $chunk);
        $result = [];
        foreach ($files as $file) {
            $mm = $file->getMimeType();
            //检查文件是否上传完成
            if (is_array($allowed_extensions) && !in_array($mm, $allowed_extensions)) {
                throw new \Exception('文件格式错误');
            }
            $newDir = $base_dir . $dirname . '/' . $chunk_dir . '/';
            if (!is_dir($newDir)) {
                mkdir($newDir, 0777, true);
                chmod($newDir, 0777);
            }
            $newFile = substr(md5($file->getPathname()), 26, 32) . mt_rand(100, 999) . "." . $file->getClientOriginalExtension();
            $res = move_uploaded_file($file->getPathname(), $newDir . $newFile);
            if (!$res) {
                throw new \Exception('文件存储失败');
            }
            $result[] = $dirname . '/' . $chunk_dir . '/' . $newFile;
        }

        return $result;
    }

    /**
     * @param $form
     * @param $column
     * @param $label
     * @param array $settings
     * @param array $initialPreview
     * @param string $attribute
     */
    public static function fileInput($form, $column, $label, $settings = [], $initialPreview = ['files' => null, 'url' => null], $attribute = '')
    {
        $file_input_settings = [
            'overwriteInitial' => false,
            'initialPreviewAsData' => true,
            'msgPlaceholder' => "\u9009\u62e9\u6587\u4ef6",
            'browseLabel' => "\u6d4f\u89c8",
            "cancelLabel" => "\u53d6\u6d88",
            "showRemove" => false,
            "showUpload" => false,
            "showCancel" => false,
            "dropZoneEnabled" => false,
            'fileActionSettings' => ["showRemove" => true, "showDrag" => false]
        ];
        $fileTypes = [
            'image' => '/^(gif|png|jpe?g|svg|webp)$/i',
            'html' => '/^(htm|html)$/i',
            'office' => '/^(docx?|xlsx?|pptx?|pps|potx?)$/i',
            'gdocs' => '/^(docx?|xlsx?|pptx?|pps|potx?|rtf|ods|odt|pages|ai|dxf|ttf|tiff?|wmf|e?ps)$/i',
            'text' => '/^(txt|md|csv|nfo|ini|json|php|js|css|ts|sql)$/i',
            'video' => '/^(og?|mp4|webm|mp?g|mov|3gp)$/i',
            'audio' => '/^(og?|mp3|mp?g|wav)$/i',
            'pdf' => '/^(pdf)$/i',
            'flash' => '/^(swf)$/i',
        ];
        $file_input_settings['initialPreviewConfig'] = [];
        $file_input_settings['initialPreview'] = [];
        if (isset($initialPreview['files']) && is_array($initialPreview['files'])) {
            foreach ($initialPreview['files'] as $file) {
                $filetype = 'other';
                $ext = strtok(strtolower(pathinfo($file, PATHINFO_EXTENSION)), '?');
                foreach ($fileTypes as $type => $pattern) {
                    if (preg_match($pattern, $ext) === 1) {
                        $filetype = $type;
                        break;
                    }
                }
                $setting = ['caption' => basename($file), 'key' => $file, 'type' => $filetype];
                if ($filetype == 'video') {
                    $setting['filetype'] = "video/{$ext}";
                }
                if ($filetype == 'audio') {
                    $setting['filetype'] = "audio/{$ext}";
                }
                $setting['downloadUrl'] = $initialPreview['url'] . $file;
                $settings['initialPreviewConfig'][] = $setting;
                $settings['initialPreview'][] = $initialPreview['url'] . $file;
            }
        }
        $settings = json_encode(array_merge($file_input_settings, $settings));
        $form->html("<input class='{$column}' name='{$column}' multiple type='file' {$attribute}>", $label);
        \Encore\Admin\Admin::script(<<<EOF
        $('input.{$column}').fileinput(JSON.parse('{$settings}')).on('filebeforedelete', function () {
            return new Promise(function(resolve, reject) {
                    var remove = resolve;
                    swal({
                        title: "确认删除?",
                        type: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#DD6B55",
                        confirmButtonText: "确认",
                        showLoaderOnConfirm: true,
                        cancelButtonText: "取消",
                        preConfirm: function() {
                            return new Promise(function(resolve) {
                                resolve(remove());
                            });
                        }
                    });
            });
        }).on("filebatchselected", function (event, files) {
            if(files.length == 0)return;
            $(this).fileinput("upload");
        }).on('fileerror', function (event, data, msg) {
            alert(msg);
        });
EOF
        );

    }


    /**
     * dot增减计算
     * @param array $selected 过去已经选择
     * @param array $select 已选择
     * @return array [insert,delete]
     */
    public static function dotCalculate(array $selected, array $select)
    {
        $insert = [];
        $delete = [];
        $intersect = array_intersect($selected, $select);
        if (count($intersect) == count($selected) && count($intersect) == count($select)) {
            return [$insert, $delete];
        }
        if (count($intersect) == count($selected) && count($intersect) < count($select)) {
            $insert = array_diff($select, $intersect);
            return [$insert, $delete];
        }
        if (count($intersect) < count($selected) && count($intersect) == count($select)) {
            $delete = array_diff($selected, $intersect);
            return [$insert, $delete];
        }
        if (count($intersect) < count($selected) && count($intersect) < count($select)) {
            $insert = array_diff($select, $intersect);
            $delete = array_diff($selected, $intersect);
            return [$insert, $delete];
        }
    }

    public static function getCurrentUrl()
    {
        return rtrim(config('app.url'), '/') . '/' . Route::current()->uri;
    }
}

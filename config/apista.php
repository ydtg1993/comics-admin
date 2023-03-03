<?php
/**
 * Created by PhpStorm.
 * User: Night
 * Date: 2022/11/9
 * Time: 10:36
 */

return [
    'code_version'=>'',
    'host'=>env('APISTA_HOST'),
    'port'=>env('APISTA_PORT','55656'),
    'open'=>env('APISTA_OPEN',false),
    'module'=>env('APISTA_MODULE','aoflix-admin-UAT'),
];
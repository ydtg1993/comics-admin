<?php

use Illuminate\Routing\Router;

Admin::routes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
    'as'            => config('admin.route.prefix') . '.',
], function (Router $router) {
    $router->get('/', 'HomeController@index')->name('home');

    /*审核*/
    $router->resource('/fail_info', 'Review\SourceFailController');
    $router->resource('/source_comic', 'Review\SourceComicController');
    $router->get('/source.comic/{id}','Review\SourceComicController@sourceComic');
    $router->resource('/source_chapter', 'Review\SourceChapterController');

    /*已发布*/
    $router->resource('/comic', 'Pass\ComicController');
    $router->resource('/chapter', 'Pass\ChapterController');

    /*用户管理*/
    $router->resource('/user', 'UserController');

    /*爬虫redis任务*/
    $router->resource('/paw', 'PawController');
    $router->post('/paw.cache', 'PawController@getPawCache');
    $router->post('/paw.set.cache', 'PawController@setPawCache');
});

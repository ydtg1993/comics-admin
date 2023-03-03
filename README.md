composer require encore/laravel-admin:1.8.19
php artisan vendor:publish --provider="Encore\Admin\AdminServiceProvider"
php artisan admin:install
composer require dlp/component-js:3.5
php artisan vendor:publish --provider="DLP\DLPServiceProvider"
composer require laravel-admin-ext/summernote
php artisan vendor:publish --tag=laravel-admin-summernote

env配置增加项目
ADMIN_HTTPS=true
VIDEO_DOMAIN=http://v13czhwp.com

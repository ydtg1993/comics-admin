<?php

namespace App\Listeners;

use Illuminate\Database\Events\QueryExecuted;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

class QueryListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param QueryExecuted $event
     * @return void
     */
    public function handle(QueryExecuted $event)
    {
        if (env('APP_DEBUG') == true && env('SQL_LISTEN') == true) {
            $sql = str_replace("?", "'%s'", $event->sql);
            $log = vsprintf($sql, $event->bindings);
            (new Logger('sql'))->pushHandler(new RotatingFileHandler(storage_path('logs/sql.log')))->info($log);
        }
    }
}

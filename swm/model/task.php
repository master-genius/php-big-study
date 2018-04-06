<?php
namespace model;

use mcore\DB;

class task
{
    private $table = 'student_task';

    public function __construct()
    {
    
    }
    
    public function getCurTask()
    {
        $tm = time();
        $task = DB::instance()->select($this->table,
                '*', 
                [
                    'AND'=>[
                        'task_status' => 0,
                        'start_time[<=]' => $tm,
                        'end_time[>=]' => $tm
                    ]
                ]
            );
        return (empty($task)?[]:$task);
    }

    public function getTaskById($task_id)
    {
        $task = DB::instance()->get($this->table, '*', 
                    [
                        'task_id'=>$task_id
                    ]
                );
        return (empty($task)?[]:$task);
    }

    public function getOldTask()
    {
        $task = DB::instance()->select($this->table,'*', 
                    [
                        'end_time[<=]' => time()
                    ]
                );
        return (empty($task)?[]:$task);
    }

    public function pubTask($task)
    {
        $r = DB::instance()->insert($this->table,$task)->rowCount();
        return (empty($r)?false:true);
    }

}


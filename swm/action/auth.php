<?php
namespace action;

class login
{
    public function __construct()
    {
    
    }
    
    public function login($req, $res)
    { 
        $post = $req->getParsedBody();
        $username = $post['username'];
        $passwd = $post['passwd'];

    }

    public function getcomment($request, $response)
    {
    
    }

    public function getTaskStatus($request, $response)
    {
        
    }

    public function checkTask()
    {
        if (empty($this->getCurTask())) {
            return false;
        }
    }

    public function getStTask($request, $response)
    {
        $task = $this->getCurTask();
        if(empty($task)) {
            return response_api($response, -1,
                        'Error: task empty'
                    );
        }

        return $response->withStatus(200)->write(
                        $this->formatTask($task)
                );

    }

    private function formatTask($task_list)
    {
        $task = '';
        foreach ($task_list as $t) {
            $task .= "task id: ".$t['task_id'] . "\n".
                    "start time: ".format_time($t['start_time']) . "\n".
                    "end time: " . format_time($t['end_time']) . "\n".
                    "content: " . $t['task_content'] . "\n".
                    "\n";
        }
        return $task;
    }

    public function getCurTask()
    {
        return (new \model\task)->getCurTask();
    }

    public function pubTask($request, $response)
    {
        $r = (new \model\user)->checkTeachAdmin(
                        $args['admin'],
                        $args['passwd']
                    );

        if (!$r) {
            return response_api($response, -1, 'Error: permission denied');
        }
        
        $post = $request->getParsedBody();
        $start_time = (isset($post['start_time'])
                        ?
                        $post['start_time']
                        :
                        time()
                      );
        $end_time = (isset($post['end_time'])
                     ?
                     $post['end_time']
                     :
                     (time()+72000)
                    );
        $task = [
            'task_content' => $post['content'],
            'start_time' => $start_time,
            'end_time' => $end_time
        ];
        
        $r = (new \model\task)->pubTask($task);

        if (!$r) {
            return response_api($response, -1, 
                        'Error: publish task failed'
                    );
        }
        
        return response_api($response);

    }

}


<?php
namespace action;

class home
{

    public function help($request, $response)
    {
        ob_start();
        include APP_PATH . '/view/index.html';
        $content = ob_get_contents();
        ob_end_clean();
        return $response->withStatus(200)->write($content);
    }
}


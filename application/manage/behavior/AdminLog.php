<?php

namespace app\manage\behavior;
class AdminLog
{
    public function run()
    {
        if (request()->isPost()) {
            \app\manage\model\AdminLog::record();
        }
    }
}

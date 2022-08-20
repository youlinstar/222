<?php

namespace app\manage\controller;
use app\common\model\VideoSort as sortModel;
class Videosort extends Common
{

    protected $searchFields='name,dir';
    protected $modelValidate=true;
    protected $modelSceneValidate=true;

    protected $sceneTag='sort';

    public function initialize(){
        parent::initialize();
        $this->model=new sortModel();
    }
}
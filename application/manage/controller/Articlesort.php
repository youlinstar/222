<?php

namespace app\manage\controller;
use app\common\model\ArticleSort as sortModel;
class Articlesort extends Common{

    protected $searchFields='name';
    protected $modelValidate=true;
    protected $modelSceneValidate=true;

    protected $sceneTag='articlesort';

    public function initialize(){
        parent::initialize();
        $this->model=new sortModel();
    }
}
<?php

namespace app\manage\controller;
use app\common\model\Swiper as swiperModel;
class Swiper extends Common
{

    protected $searchFields='title';
    protected $modelValidate=true;

    protected $modelSceneValidate=true;

    protected $sceneTag='swiper';


    public function initialize(){
        parent::initialize();
        $this->model=new swiperModel();
    }

}
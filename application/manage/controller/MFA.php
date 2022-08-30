<?php
/**
 * MFA
 * @project 222
 * @copyright
 * @author
 * @version
 * @createTime 20:51
 * @filename MFA.php
 * @product_name PhpStorm
 * @link
 * @example
 */

namespace app\manage\controller;

use app\common\model\Admin;
use app\common\model\AuthGroup;
use app\common\model\AuthRule;
use Sonata\GoogleAuthenticator\GoogleAuthenticator;
use Sonata\GoogleAuthenticator\GoogleQrUrl;

class MFA extends Common
{
    public function index()
    {

        $googleAuthenticator = new GoogleAuthenticator();
        $adminInfo=Admin::get($this->auth->getUserInfo()['id']);

        if(request()->isPost()){
            switch ($this->request->param('type')){
                case 'new':
                    if (empty($this->request->param('secret'))){
                        return callback(400,'数据不存在');
                    }
                    if ((new Admin())->save(['mfa_secret'=>$this->request->param('secret')],['id'=>$adminInfo['id']])){
                        return callback(200,'更新成功',url('MFA/index'));
                    }
                    return callback(400,'更新失败');
                    break;

                case 'cancel':
                    if ((new Admin())->save(['mfa_secret'=>null],['id'=>$adminInfo['id']])){
                        return callback(200,'更新成功',url('MFA/index'));
                    }
                    return callback(400,'更新失败');
                    break;

                default:
                    return callback(400,'方法不存在');
                    break;
            }
        }

        if (empty($adminInfo['mfa_secret'])){
            $secret = $googleAuthenticator->generateSecret();
            $qrcode= GoogleQrUrl::generate($adminInfo['username'], $secret, 'manage');
            $this->assign('qrcode',$qrcode);
            $this->assign('secret',$secret);
            $isNew=true;
        }else{
            $this->assign('secret',"");
            $isNew=false;
        }
        //$g = new \Sonata\GoogleAuthenticator\GoogleAuthenticator();
//        $secret = $g->generateSecret();
        //$secret = "XVQ2UIGO75XRUKJO";
        /*$secret = $g->generateSecret();
        /*$secret = $g->generateSecret();
        $qr=\Sonata\GoogleAuthenticator\GoogleQrUrl::generate('deyunshe', $secret, 'manage');
        $this->assign('qrcode',$qr);*/
        //dd($g->checkCode($secret, "975434"));
        //$qr=\Sonata\GoogleAuthenticator\GoogleQrUrl::generate('deyunshe', $secret, 'manage');
        $this->assign('isNew',$isNew);
        return $this->fetch();
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 11/06/2018
 * Time: 15:06
 */

namespace Index\Controller;
use Think\Controller;


class ArticleController extends CommonController {
    public function index($id)
    {
        $id = intval($id);
        $prefix = C('DB_PREFIX');
        $act = M('act');
        $data = $act->field("{$prefix}act.*,{$prefix}category.name,{$prefix}users.nickname,{$prefix}users.head,{$prefix}users.pageurl")
            ->where("{$prefix}act.id= $id")
            ->join("{$prefix}users ON {$prefix}users.id = {$prefix}act.userid")
            ->join("{$prefix}category ON {$prefix}category.id = {$prefix}act.pid")
            ->find();
        if(!$data){
            header('Location: http://liweijia.site/404/index.html');
        }
        $act->where("id=$id")-> setInc('view',1);
        $this->assign('current', $data['title']);
        $this->assign('data', $data);
        $this->display();
    }

}
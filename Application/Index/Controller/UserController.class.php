<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 11/06/2018
 * Time: 16:10
 */

namespace Index\Controller;
use Think\Controller;
use Vendor\ThinkSDK\ThinkOauth;
use Api\Controller\OauthController;



class UserController extends CommonController {

    public function index($user){
        $infor = M('users')->where("pageurl='$user'")->find();

        $this->assign('infor', $infor);
        $this->display();
    }
    public function about($user){
        $infor = M('users')->where("pageurl='$user'")->find();
        $this->assign('infor', $infor);
        $this->display();
    }
    public function login(){

        // 判断提交方式
        if (IS_POST) {
            $username = I('post.username');
            $password = I('post.password');

            // 组合查询条件
            $where = array();
            $where['username'] = $username;
            $result = M('users')->where($where)->field('id,username,nickname,password,lastdate,lastip')->find();

            // 验证用户名 对比 密码

            if ($result && auth_code($result['password']) == $password) {

                // 存储session
                session('Iuid', $result['id']);          // 当前用户id
                session('Inickname', $result['nickname']);   // 当前用户昵称
                session('Iusername', $result['username']);   // 当前用户名
                session('Ilastdate', $result['lastdate']);   // 上一次登录时间
                session('Ilastip', $result['lastip']);       // 上一次登录ip

                $data = array(
                    'lastdate'=> time(),
                    'regip'=> get_client_ip(),
                    'lastip'=> get_client_ip(),
                );
                // 更新用户登录信息
                $where['id'] = session('Iuid');
                M('admin')->where($where)->setInc('loginnum');   // 登录次数加 1
                M('admin')->where($where)->save($data);   // 更新登录时间和登录ip

                exit(json_encode(array(
                    'status'=> 200, // 格式错误
                    'cap'=>'登录成功' // 错误信息
                )));
            } else {
                exit(json_encode(array(
                    'status'=> 404, // 格式错误
                    'cap'=>array(
                        'name'=> 'username', // DOM name
                        'value'=> '账号或密码错误', // 提示信息
                    )  // 错误信息
                )));
            }
        } else {
            $this->assign('current', "登录");
            $this->display();
        }
    }
    public function logout(){

        // 清楚所有session
        session(null);
        $this->success('正在退出登录...',U('Index/index'), 2);
    }

    public function register(){
        // 判断提交方式 做不同处理
        if (IS_POST) {

            $nickname = I('post.nickname');
            $email = I('post.email');
            $verify = I('post.verify');
            $code = I('post.code');
            $password = I('post.password');
            $cap = array();

            if(!verify_check($verify)){
                array_push($cap,array(
                    'name'=> 'verify', // DOM name
                    'value'=> '图形验证码不正确', // 提示信息
                ));
            }
            $mail_erify =M('mail_verify');
            $verifyDate=$mail_erify->where(array('email' => $email,'type'=>1))->field()->find();

            if(!$verifyDate['verify']){
                array_push($cap,array(
                    'name'=> 'code', // DOM name
                    'value'=> '请先发送Email验证码', // 提示信息
                ));
            }else{
                if($code !=auth_code($verifyDate['verify'])){
                    array_push($cap,array(
                        'name'=> 'code', // DOM name
                        'value'=> '邮箱验证码不正确', // 提示信息
                    ));
                }
                if((time() - $verifyDate['verify']) < 60*30){
                    array_push($cap,array(
                        'name'=> 'code', // DOM name
                        'value'=> '验证码已经过期。请重新发送mail', // 提示信息
                    ));
                }
            }
            if( M('users')->where("email='$email'")->find()){
                array_push($cap,array(
                    'name'=> 'email', // DOM name
                    'value'=> '邮箱已经注册', // 提示信息
                ));
            }

            if($cap){
                exit(json_encode(array(
                    'status'=> 404, // 格式错误
                    'cap'=>$cap  // 错误信息
                )));
            }
            $mail_erify->where('id = '.$verifyDate['id'])->delete();
            $pageurl = pinyin($nickname);
            if( M('users')->where("pageurl='$pageurl'")->find()){
                $pageyrl = $pageurl.rand_int(6);
            }

            $data = array(
                'username'=> $email,
                'password'=> auth_code($password,'1'),
                'nickname'=> $nickname,
                'head'=> '/blog/Public/Image/user-64.png',
                'regdate'=> time(),
                'lastdate'=> time(),
                'regip'=> get_client_ip(),
                'lastip'=> get_client_ip(),
                'loginnum'=> 1,
                'email'=> $email,
                'pageurl'=>$pageyrl ,
            );

            //插入数据库
            if ($id = M('users')->add($data)) {
                // 存储session
                session('Iuid', $id);          // 当前用户id
                session('Inickname', $data['nickname']);   // 当前用户昵称
                session('Iusername', $data['username']);   // 当前用户名
                session('Ilastdate', $data['lastdate']);   // 上一次登录时间
                session('Ilastip', $data['lastip']);       // 上一次登录ip


                exit(json_encode(array(
                    'status'=> 200, // 格式错误
                    'cap'=>'注册成功！'  // 错误信息
                )));

            } else {
                exit(json_encode(array(
                    'status'=> 404, // 格式错误
                    'cap'=> $this->error('注册失败')  // 错误信息
                )));

            }

        } else {
            $this->assign('current', "注册");
            $this->display();
        }
    }
    public function forgot(){
        // 判断提交方式 做不同处理
        if (IS_POST) {

            $email = I('post.email');
            $verify = I('post.verify');
            $cap= array();

            if(!verify_check($verify)){
                array_push($cap,array(
                    'name'=> 'verify', // DOM name
                    'value'=> '图形验证码不正确', // 提示信息
                ));
            }
            $user = M('users')->where("email='$email'")->find();

            if($user == ''){
                array_push($cap,array(
                    'name'=> 'email', // DOM name
                    'value'=> '此用户未注册', // 提示信息
                ));
            }

            if($cap){
                exit(json_encode(array(
                    'status'=> 404, // 格式错误
                    'cap'=>$cap  // 错误信息
                )));
            }
            $time = time();

            $token = createToken($user['username'],$user['password'],$time);

            $user_verfy = M('mail_verify')->where(array('email' => $email,'type'=>2))->find();

            $data=array(
                'verify' => $token,
                'email' => $email,
                'date' => $time,
                'type' => 2,
                'ip' => get_client_ip(),
            );

            $str ="<div class='header' style='background-color: rgb(117, 212, 183); padding: 10px;'><span class='logo'><img src='http://blog.liweijia.site/Public/Image/logo.png' style='width: 150px;'></span></div><div style='font-size: 14px;padding: 2em;'><br><br>        您好  ".$user['nickname'].",<br><br><p>您正在通过邮箱重置<a href='http://blog.liweijia.site'>Liweijia_blog</a>的登录密码请点击下面的连接修改密码，如果邮箱不能直接跳转链接，请将该链接复制到浏览器的地址中：</p><h3 style='font-size: 20px; color: #f00; font-weight: bold;'><a href='http://blog.liweijia.site/index.php/Index/User/modify/token/".$token.".html'>http://blog.liweijia.site/index.php/Index/User/modify/token/".$token.".html</a></h3><p>该验证邮件有效期为1天，超时请重新发送邮件。</p></div><div style='font-size: 14px; padding: 2em;'>        Regards<br>        Your  Team<br>        ------------------------------------<br><a href=''>www.blog.liweijia.site</a><br><div style='color: #999;'><p>发件时间：<span id='stickerTimer' style='border-bottom: 1px dashed rgb(204, 204, 204); position: relative;'  times='".date("G").":".date("i")."' isout='0'>".date("Y")."/".date("m")."/".date("d")."</span>  ".date("G").":".date("i").":".date("s")."</p><p>此邮件为系统自动发出的，请勿直接回复。</p></div></div>";


                if(send_mail($email,$email,'Blog-找回密码验证码',$str)) {
                    if($user_verfy){
                        M('mail_verify')->where($user_verfy['id'])->save($data);
                    }else{
                        M('mail_verify')->add($data);
                    }
                    exit(json_encode(array(
                        'status' => 200, // 格式错误
                        'cap' => '发送成功！'  // 错误信息
                    )));
                }else{
                    exit(json_encode(array(
                        'status' => 403, // 格式错误
                        'cap' => '网络不通畅，请稍后再试一下！'  // 错误信息
                    )));
                }


        } else {
            $this->assign('current', "忘记密码");
            $this->display();
        }
    }

    public function modify(){
        $token = I('get.token');
        $verify = M('mail_verify')->where(array('verify' => $token,'type'=>2,'IsExpiried'=>1))->find();
        if($verify || (time()-$verify['date'])< 60*60*24){
            session('modifyUserName', $verify['email']);
            redirect(U('User/reset'));
        }else{
            $this->error('链接错误或者已经失效...',U('Index/index'), 2);
        }
    }
    public function reset(){
        if(IS_POST){
            $username = session('modifyUserName');
            $password = I('post.password');

            if($username == null){
                $this->error('请重新点击Mail中的连接。切记勿修改！若多次无效，请重新发送mail！',U('Index/index'), 2);
                return;
            }
            $data = array(
                'password'=> auth_code($password,"1"),
                'lastip'=> get_client_ip(),
            );
            $modify = M('users')->where(array('username' => $username))->save($data);

            if($modify){

                M('mail_verify')->where("email = '".$username."'")->delete();
                session(null);
                $this->success('修改成功！请重新登录.',U('User/login'), 2);
            }
        }else{
            $this->assign('current', "修改密码");
            $this->display();
        }
    }

    //第三方登录
    public  function oauth($type){

        empty($type) && $this->error('参数错误');
        //加载ThinkOauth类并实例化一个对象
        //import("ORG.ThinkSDK.ThinkOauth");
        $sns = ThinkOauth::getInstance($type);
        //跳转到授权页面
        redirect($sns->getRequestCodeURL());
    }
    //授权回调地址
    public function callback($type = null, $code = null){
     
        (empty($type) || empty($code)) && $this->error('参数错误');

        //加载ThinkOauth类并实例化一个对象
        import("ORG.ThinkSDK.ThinkOauth");
        $sns  = ThinkOauth::getInstance($type);
        //腾讯微博需传递的额外参数
        $extend = null;
        if($type == 'tencent'){
            $extend = array('openid' => $this->_get('openid'), 'openkey' => $this->_get('openkey'));
        }
        //请妥善保管这里获取到的Token信息，方便以后API调用
        //调用方法，实例化SDK对象的时候直接作为构造函数的第二个参数传入
        //如： $qq = ThinkOauth::getInstance('qq', $token);
        $token = $sns->getAccessToken($code , $extend);
        //获取当前登录用户信息
        if(is_array($token)){
            $user_info = OauthController::$type($token);
            echo("<h1>恭喜！使用 {$type} 用户登录成功</h1><br>");
            echo("授权信息为：<br>");
            dump($token);
            echo("当前登录用户信息为：<br>");
            dump($user_info);
        }
    }
}
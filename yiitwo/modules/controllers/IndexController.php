<?php

namespace app\modules\controllers;
use app\models\Follow;
use app\models\Msg;
use Yii;
use yii\web\Controller;
use app\models\YiiUser;
use yii\web\session;
use app\models\UserForm;
use yii\filters\AccessControl;

class IndexController extends Controller{
    public $enableCsrfValidation = false;//yii默认表单csrf验证，如果post不带改参数会报错！
    public $layout  = 'layout';

    /**
     * accesscontrol
     */

    /**
     * @用户授权规则
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'actions' => ['login','captcha'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['logout','edit','add','del','index','users','thumb','upload','cutpic','follow','nofollow'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @验证码独立操作
     */

    public function actions(){
        return [

            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }


    /**
     * @return string 后台默认页面
     */
    public function actionIndex()
    {
        //echo Yii::$app->user->getId().'<br/>';获取用户id
        //echo Yii::$app->user->identity->getUser();//获取用户名

       // echo Yii::$app->basePath;//获取应用根目录
        return $this->render('index');
    }



    /**
     * @return string 读取用户列表
     */
    public function actionUsers(){
        $uid=Yii::$app->user->getId();

        //我的粉丝
        $follow=Follow::find()->where(array("fid"=>$uid))->all();
        //$follow=Follow::findBySql("select uid from {{%follow}} where fid=".Yii::$app->user->getId())->all();
        //echo '<pre/>';print_r($follow);
        $ids=array();
        foreach($follow as $v){
            array_push($ids,$v->uid);
        }

        //获取我的粉丝信息
        $fensi=YiiUser::findAll($ids);
        //echo '<pre/>';print_r($fensi);

        //获取我关注的人【我的好友】
        $careids=Follow::find()->where(["uid"=>$uid])->all();
        $cids=array();
        foreach($careids as $v){
            array_push($cids,$v->fid);
        }
        $cares=YiiUser::findAll($cids);

        //获取我没有关注的用户【加关注的人】
         array_push($cids,$uid);//将我的id也加入到排除列表
        //$users=YiiUser::find()->where(['in','id',$ids])->all();//id在一个数组范围内
        $users=YiiUser::find()->where(['not in','id',$cids])->all();

        return $this->render('users',array('users'=>$users,'fensi'=>$fensi,'cares'=>$cares,'cids'=>$cids));
    }


    /**
     * @添加关注动作
     */
    public function actionFollow($id){
       //获取查询条件
        $fid=intval($id);
        $uid=Yii::$app->user->getId();

        //检查是否已经关注了
        $obj=new Follow();
        $num=$obj->find()->andWhere(['uid'=>$uid,'fid'=>$fid])->count();
        if($num==0){
            $obj->uid=$uid;
            $obj->fid=$fid;
            $obj->save();
            Yii::$app->session->setFlash('success','关注成功！');
        }else{
            Yii::$app->session->setFlash('error','关注失败！');
        }
        return $this->redirect(['index/users']);
    }

    /**
     * @取消关注
     */
    public function actionNofollow($id){
        $fid=intval($id);
        $uid=Yii::$app->user->getId();
        //检查是否已经关注了
        $user=Follow::find()->andWhere(['uid'=>$uid,'fid'=>$fid])->one();

        if(count($user)>0){
            //$user->delete() 失败，提示没有定义主键
            $user->deleteAll('uid=:uid and fid=:fid',[':uid'=>$uid,':fid'=>$fid]);
            Yii::$app->session->setFlash('success','取消关注成功！');
        }else{
            Yii::$app->session->setFlash('error','取消关注失败！');
        }
        return $this->redirect(['index/users']);
    }





    /**
     * @return string|\yii\web\Response 用户登录
     */

    public function actionLogin(){
        $model=new UserForm();

        if($model->load(Yii::$app->request->post())){

            if($model->login()){
                //查询未读消息
                $count=Msg::find()->andwhere(['tid'=>Yii::$app->user->getId(),'status'=>0])->count();
                $session=Yii::$app->session;
                $session->set('msg',$count);

                return $this->redirect(['index/index']);
            }else{
                return $this->render('login',['model'=>$model]);
            }
        }

        return $this->render('login',['model'=>$model]);
    }



    /**
     * @后台退出页面
     */
    public function actionLogout(){
        Yii::$app->user->logout();
        return $this->goHome();

    }


    /**
     * @用户头像上传
     */
    public function  actionThumb(){
       $user=YiiUser::findOne(Yii::$app->user->getId());
        return $this->render('thumb',array('user'=>$user));
    }

    /**
     * @
     */
    public  function  actionUpload(){

        $path = Yii::$app->basePath."/web/avatar/";
        $tmpath="/avatar/";
        if(!empty($_FILES)){

            //得到上传的临时文件流
            $tempFile = $_FILES['myfile']['tmp_name'];

            //允许的文件后缀
            $fileTypes = array('image/gif','image/jpeg','image/pjpeg',
            		           'image/png','image/x-png','image/wbmp');  // 有问题  // xum
             
            // xum 
            // 上传图片时，IE6、7、8会把 jpg、jpeg翻译成image/pjpeg，png翻译成image/x-png 
            // 而火狐、谷歌等则很标准：jpg、jpeg翻译成image/jpeg，png翻译成image/png
/*             if(($_FILES["myfile"]["type"] == "image/gif")
            	||($_FILES["myfile"]["type"] == "image/jpeg")	
            	||($_FILES["myfile"]["type"] == "image/pjpeg")
            	||($_FILES["myfile"]["type"] == "image/png")
            	||($_FILES["myfile"]["type"] == "image/x-png")
            	||($_FILES["myfile"]["type"] == "image/bmp")
            		) */
            if(in_array($_FILES["myfile"]["type"], $fileTypes))
            {
         
            //得到文件原名      
            /*  @author xum
             *  string iconv ( string $in_charset , string $out_charset , string $str ) 
             *  将字符串 str 从 in_charset 转换编码到 out_charset
             *  
             *  [iconv] note:上传中文名的文件会出现乱码,如图片名是中文的，可能会导致显示图片出问题
             *  可以参考网址看看  http://php.net/manual/zh/function.iconv.php
             */
            $fileName = iconv("UTF-8","GB2312",$_FILES["myfile"]["name"]); 
            //$fileName = iconv("UTF-8", "UTF-8", $_FILES["myfile"]["name"]);
            $fileParts = pathinfo($_FILES['myfile']['name']);

            //最后保存服务器地址
            if(!is_dir($path)){
                mkdir($path);
            }

            if (move_uploaded_file($tempFile, $path.$fileName)){
            	//$fileName = iconv("GB2312", "UTF-8", $fileName); // xum
                $info= $tmpath.$fileName;
                $status=1;
                $data=array('path'=>Yii::$app->basePath,'file'=> $path.$fileName);
            }else{
                $info=$fileName."上传失败！";
                $status=0;
                $data='';
            }
            //$info = iconv("GB2312", "UTF-8", $info);
            echo iconv("GB2312", "UTF-8", $info);  // xum
          }else{
          	die("不支持的图像格式!");
          }
        }

    }

    /**
     * @裁剪头像
     */

    public function actionCutpic(){
    	header("Content-type: text/html; charset=utf-8");
        if(Yii::$app->request->isAjax){
            $path="/avatar/";
            $targ_w = $targ_h = 150;
            $jpeg_quality = 100;
            $src =Yii::$app->request->post('f');
            $src=Yii::$app->basePath.'/web'.$src;//真实的图片路径

            /* xum
             * 为什么不能上传中文名字的图片,因为imagecreatefromjpeg、imagecreatefromgif、imagecreatefrompng、imagecreatefromwbmp打不开中文名字的图片
             * 后续怎么改进？怎么才能上传中文名字的图片
            */
            $suffix = stristr($src,".");  // root problem
            //$src = iconv("UTF-8", "UTF-8", $src);
            if($suffix == ".jpg"){
            	$img_r = imagecreatefromjpeg($src); 
            	$ext=$path.time().".jpg";//生成的引用路径
            } else if ($suffix == ".jpeg"){
            	$img_r = imagecreatefromjpeg($src); 
            	$ext=$path.time().".jpeg";//生成的引用路径
            } else if ($suffix == ".gif"){
            	$img_r = imagecreatefromgif($src); 
            	$ext=$path.time().".gif";//生成的引用路径
            }else if ($suffix == ".png"){
            	$img_r = imagecreatefrompng($src); 
            	$ext=$path.time().".png";//生成的引用路径
            }elseif ($suffix == ".wbmp"){
            	$img_r = imagecreatefromwbmp($src); 
            	$ext=$path.time().".wbmp";//生成的引用路径
            }
            
            //$img_r = imagecreatefromjpeg($src); // 为什么只能上传jpg格式图片的肯本原因所在  // xum
            //$ext=$path.time().".jpg";//生成的引用路径
            $dst_r = ImageCreateTrueColor( $targ_w, $targ_h );

            imagecopyresampled($dst_r,$img_r,0,0,Yii::$app->request->post('x'),Yii::$app->request->post('y'),
                $targ_w,$targ_h,Yii::$app->request->post('w'),Yii::$app->request->post('h'));

            $img=Yii::$app->basePath.'/web/'.$ext;//真实的图片路径

            if(imagejpeg($dst_r,$img,$jpeg_quality) 
               ||imagegif($dst_r,$img)
               ||imagepng($dst_r,$img)
               ||imagewbmp($dst_r,$img)
            		){
                //更新用户头像
                $user=YiiUser::findOne(Yii::$app->user->getId());
                $user->thumb=$ext;
                $user->save();
                $arr['status']=1;
                $arr['data']=$ext;
                $arr['info']='裁剪成功！';
                echo json_encode($arr);

            }else{
                $arr['status']=0;
                echo json_encode($arr);
            }           
            exit;
        }
    }



}

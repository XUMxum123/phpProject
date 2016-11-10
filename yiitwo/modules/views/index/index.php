<?php
use yii\helpers\Html;
use app\assets\AppAsset;
use yii\widgets\ActiveForm;
?>
<!DOCTYPE HTML>
<html>
<head>
    <title>后台管理系统</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <?=Html::cssFile('@web/assets/css/dpl-min.css')?>
    <?=Html::cssFile('@web/assets/css/bui-min.css')?>
    <?=Html::cssFile('@web/assets/css/main-min.css')?>
    <?=Html::cssFile('@web/css/site.css')?>
    <?=Html::jsFile('@web/assets/js/jquery-1.8.1.min.js')?>
    <?=Html::jsFile('@web/assets/js/bui-min.js')?>
    <?=Html::jsFile('@web/assets/js/common/main-min.js')?>
    <?=Html::jsFile('@web/assets/js/config-min.js')?>
    <script>
        $(function(){
            ajaxPull();
            //轮询，实时更新消息数,10秒更新一次
             function ajaxPull(){
                setInterval(updateMsg,10000);
            }

            //每个轮询操作
             function updateMsg(){
                var msgnum=parseInt($("#msgnum").text());
                //异步操作，发送请求，对比消息数变更
                 $.get('/admin/msg/pull',{msgnum:msgnum},function(data){
                     if(data.status==1){
                         //更新消息提示
                         $("#msgnum").text(data.msgnum);
                     }
                 },'json');

            }



        })
    </script>
</head>
<body>

<div class="header">

    <div class="dl-title">
        <!--<img src="/chinapost/Public/assets/img/top.png">-->
    </div>

    <div class="dl-log">欢迎您，<span class="dl-log-user" id="<?=Yii::$app->user->getId()?>"><?=Yii::$app->user->identity->nickname?>(<?=Yii::$app->user->identity->user?>)</span>   <span class="glyphicon glyphicon-envelope"></span>  <span class="badge" id="msgnum"><?php if(Yii::$app->session->has('msg')):?> <?=Yii::$app->session->get('msg')?><?else:?>0<?endif?></span>  <a href="<?=Yii::$app->urlManager->createUrl(['admin/index/logout'])?>" title="退出系统" class="dl-log-quit">[退出]</a>
    </div>
</div>
<div class="content">
    <div class="dl-main-nav">
        <div class="dl-inform"><div class="dl-inform-title"><s class="dl-inform-icon dl-up"></s></div></div>
        <ul id="J_Nav"  class="nav-list ks-clear">
            <li class="nav-item dl-selected"><div class="nav-item-inner nav-home">系统管理</div></li>		<li class="nav-item dl-selected"><div class="nav-item-inner nav-order">业务管理</div></li>

        </ul>
    </div>
    <ul id="J_NavContent" class="dl-tab-conten">

    </ul>
</div>


<script>
    var test="<?= Yii::$app->urlManager->createUrl('admin/index/users')?>";
    var thumb="<?= Yii::$app->urlManager->createUrl('admin/index/thumb')?>";
    var sendmsg="<?= Yii::$app->urlManager->createUrl('admin/msg/sendmsg')?>";
    var msg="<?= Yii::$app->urlManager->createUrl('admin/msg/msg')?>";
    var mysend="<?= Yii::$app->urlManager->createUrl('admin/msg/mysend')?>";

    BUI.use('common/main',function(){
        var config = [
            {id:'1',menu:[
                  {text:'系统管理',items:[{id:'11',text:'朋友圈',href:test},{id:'12',text:'头像管理',href:thumb}]},
                  {text:'消息管理',items:[{id:'22',text:'我的消息',href:msg},{id:'23',text:'我发送的',href:mysend},{id:'24',text:'发送信息',href:sendmsg}]}
                ]},
            {id:'7',homePage : '9',menu:[{text:'业务管理',items:[{id:'9',text:'查询业务',href:test}]}]}
        ];
        new PageUtil.MainPage({
            modulesConfig : config
        });
    });
</script>
</body>
</html>
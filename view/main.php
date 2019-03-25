<?php self::commonCss('/css/common.css');?>
<meta name="viewport" content="width=device-width, initial-scale=1,user-scalable=no">
<?php self::title('모이자 - 취업센터');?>
<?php self::import('include.header');?>
<?php self::js('/js/index.js');?>

<div>
<?php
p($users);
?>
</div>

<?php self::import('include.footer');?>

<style>
body {padding-bottom:3.4em;}
.main-menu   {
    position:fixed;z-index:1;bottom:0;left:0;width:100%;
    box-shadow:0 -5px 10px rgba(0,0,0,.2);background-color:#ffffff;
}
.main-menu   a  {display:block;float:left;width:33.33%;text-align:center;text-decoration:none;padding:.7em 0;font-size:.8em;background-color:#f6f6f6;}
.main-menu   a  .icon {display:block;text-align:center;color:#bbbbbb;font-size:1.25em;height:1em;line-height:1em;}
.main-menu   a  .text   {color:#999999;}
.main-menu   a.on  {background-color:#ffffff;}
.main-menu   a.on  .icon {color:#4260fe;}
.main-menu   a.on  .text {color:#4260fe;font-weight:bold;}
.main-menu::after   {content:'';display:block;height:0;clear:both;}
</style>
<!-- menu -->
<div class="main-menu">
    <a href="/" class="<?php echo RouteHandler::$controller=='main' ? 'on' : '';?>">
        <span class="icon"><i class="fas fa-home"></i></span>
        <span class="text">처음으로</span>
    </a>
    <a href="/job">
        <span class="icon"><i class="fas fa-building"></i></span>
        <span class="text">채용정보</span>
    </a>
    <a href="/resume">
        <span class="icon"><i class="fas fa-user-tie"></i></span>
        <span class="text">인재정보</span>
    </a>
</div>
<!-- //menu -->
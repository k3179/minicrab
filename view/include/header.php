<?php self::css('/css/fontawesome/css/all.min.css',true);?>
<?php self::css('/css/common.css',true);?>
<style>
.site-header  {background-color:#6280fe;height:3em;color:#ffffff;text-align:center;color:#ffffff;}
.site-header  .logo {
    background-image:url('/images/logo.png');
    background-repeat:no-repeat;
    background-size:100% auto;
    background-position:center;
    display:inline-block;width:3.5em;height:1.4em;margin-top:.55em;
    font-weight:bold;font-size:1.25em;text-decoration:none;
    line-height:2.4em;color:#ffffff;
}
.site-header  .my    {position:absolute;right:0;font-size:1.2em;margin:.7em;}
.site-header  .my    i   {color:#ffffff;}
.site-search  {background-color:#6280fe;color:#ffffff;text-align:center;color:#ffffff;padding:0 .6em .6em;}
.site-search  .site-search-input   {background-color:#7095ff;border-radius:.25em;height:2.5em;margin:0 auto;line-height:2.5em;color:#a9c4f8;}
</style>
<div class="site-header">
    <a href="/" class="logo"></a>
    <a href="/my" class="my"><i class="fas fa-user"></i></a>
</div>
<div class="site-search">
    <div class="site-search-input"><i class="fas fa-search"></i> 검색어를 입력하세요</div>
</div>
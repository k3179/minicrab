<?php

class MainController{

    /**
     * main controller
     *
     * @throws Exception test
     * @return void
     */
    public static function view()
    {

		// get from cache
        //$users   =  Cache::getByTime('1m','test_data');

        $image_src  =   '/images/sample.jpg';
        $image_path =   WWW_PATH.$image_src;

        $cut_thumb   =   Image::cut($image_path,300,300);
        $resize_thumb   =   Image::resize($image_path,400,400);

        echo "<div><img src='{$image_src}' /></div>";
        echo "<div><img src='{$cut_thumb}' /></div>";
        echo "<div><img src='{$resize_thumb}' /></div>";

        Display::view('main',array(
            //'users'=>$users
        ));
    }

    

}
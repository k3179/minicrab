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
        $users   =  Cache::getByTime('1m','test_data');

        Display::view('main',array(
            'users'=>$users
        ));
    }

    

}
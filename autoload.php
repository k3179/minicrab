<?php
define('__CRAB__', true);
define('ROOT_PATH', __DIR__);
define('APP_PATH', ROOT_PATH.'/app');
define('CACHE_SOURCE_PATH', ROOT_PATH.'/cache/source');
define('CACHE_DATA_PATH', ROOT_PATH.'/cache/data');
define('CONFIG_PATH', ROOT_PATH.'/config');
define('CONTROLLER_PATH', ROOT_PATH.'/controller');
define('LIB_PATH', ROOT_PATH.'/lib');
define('WWW_PATH', ROOT_PATH.'/www');
include_once LIB_PATH.'/ExceptionHandler.class.php';
include_once LIB_PATH.'/_setup.php';
include_once LIB_PATH.'/_function.php';

/**
 * class autoload function
 *
 * @param string $class_name
 * @return void
 */
function crab_autoloader($class_name){
    if(empty($class_name) || !preg_match("/^[A-Z][a-zA-Z0-9\_]+$/",$class_name)){
        error_log('Error class : Class name error : '.$class_name);
        error_log(print_r(debug_backtrace(),1));
        exit;
    }
    $library_class_array    =  array(
        'RouteHandler','Display',
        'File','Cache','Db',
    );
    // check first character
    if(in_array($class_name,$library_class_array)){
        $class_dir =  LIB_PATH.'/';
    }else{
        $class_dir =  APP_PATH.'/';
    }

    $class_file =  $class_name.'.class.php';
    $class_path   =  $class_dir.$class_file;
    try{
        if(is_file($class_path)){
            include_once $class_path;
            if(method_exists($class_name,'__init')){
                $class_name::__init();
            }
            return;
        }
        throw new Exception('Class File Not Found');
    }catch(Exception $e){
        ExceptionHandler::catchError($e);
    }
}

spl_autoload_register('crab_autoloader');
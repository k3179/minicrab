<?php

class RouteHandler {

    public static $controller   =  '';
    public static $method   =  '';

    public function run(){
        try{
            $controller_info =  self::parseURI();
            self::exec($controller_info);
        }catch(Throwable $e){
            ExceptionHandler::catchError($e);
        }

    }


    /**
     * execute controller method
     *
     * @throws Exception
     * @param array $controller_info
     * @return void
     */
    private function exec($controller_info){
        if(
            empty($controller_info['controller']) ||
            empty($controller_info['method'])
        ) return;
        // check controller class file
        $controller_file_path = CONTROLLER_PATH.'/'.$controller_info['controller'].'.php';
        if(!is_file($controller_file_path)){
            throw new Exception('no controller file');
        }

        self::$controller  =  $controller_info['controller'];
        self::$method  =  $controller_info['method'];

        include_once($controller_file_path);
        $class_name =   $controller_info['controller'].'Controller';
        $method_name   =  $controller_info['method'];
        if(!method_exists($class_name,$method_name)){
            $method_name   =  'view';
        }
        $args    =  !empty($controller_info['args']) ? $controller_info['args'] : array();
        call_user_func_array($class_name.'::'.$method_name,$args);
    }

    /**
     * parse uri to controller and method and param
     *
     * @return array
     */
    private function parseURI(){
        $request_uri  =   $_SERVER['REQUEST_URI'];

        include_once __DIR__.'/_route.php';
        if(!empty($route_map)){
            foreach($route_map as $route=>$controller){
                $route_pattern =  '/^'.str_replace('/','\/',$route).'$/i';
                preg_match($route_pattern,$request_uri,$matches);
                if($matches){
                    $args =  array();
                    if(sizeof($matches)>1){
                        $args =  array_slice($matches,1);
                    }
                    return self::parseRouteMapController($controller,$args);
                }
            }
        }

        $path  =   stristr($request_uri,'?') ? substr($request_uri,0,strpos($request_uri,'?')) : $request_uri;

        $controller_info   =   array();

        if($path==='/'){
            $controller_info  =   self::defaultController();
        }else{
            $path = substr($path,1);
            $path_array   =   explode('/',$path);
            $controller_info['controller']   =  $path_array[0];
            if(sizeof($path_array)>1){
                $controller_info['method']  =  $path_array[1];
            }else{
                $controller_info['method']  =  'view';
            }
        }

        if(!empty($_SERVER['QUERY_STRING'])){
            parse_str($_SERVER['QUERY_STRING'],$arr);
            $controller_info =  array_merge($arr,$controller_info);
        }

        // check controller and method
        if(
            !preg_match('/^[a-zA-Z][a-zA-Z0-9\_]*$/i',$controller_info['controller']) ||
            !preg_match('/^[a-zA-Z][a-zA-Z0-9\_]*$/i',$controller_info['method'])
        ){
            $controller_info  =   self::defaultController();
        }

        return $controller_info;
    }

    /**
     * return default main@view controller
     *
     * @return array
     */
    private function defaultController(){
        return array(
            'controller'=>'main',
            'method'=>'view',
        );
    }

    /**
     * parse route map controller defined in _route.php
     *
     * @param array $controller
     * @param array $args
     * @return array
     */

    private function parseRouteMapController($controller,$args){
        $controller_info   =   array();
        preg_match('/^([^\@]+)\@([^\(]+)/i',$controller,$matches);
        if($matches){
            $controller_info['controller'] =  $matches[1];
            $controller_info['method'] =  $matches[2];
            if($args){
                $controller_info['args']   =   $args;
            }
            // param
            return $controller_info;
        }
    }

}
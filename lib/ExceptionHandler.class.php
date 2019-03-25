<?php

class ExceptionHandler {

    /**
     * catch error and print
     *
     * @param Exception $exception
     * @return void
     */
    public static function catchError($exception){
        //p($exception);
        $new_exception  =   self::getError($exception);
        Display::view('debug',[
            'exception'=>$new_exception
        ]);
        exit;
    }

    /**
     * rebuild error
     *
     * @param Exception $exception
     * @return Array
     */
    private static function getError($exception){
        $new_exception  =   [];
        $new_exception['message']   =   $exception->getMessage();
        $new_exception['file']   =   str_replace(ROOT_PATH,'',$exception->getFile());
        $new_exception['line']   =   $exception->getLine();
        $trace_array  =   array_map(function($trace){
            if(!empty($trace['file'])){
                $trace['file']  =   str_replace(ROOT_PATH,'',$trace['file']);
            }
            return $trace;
        },$exception->getTrace());
        $new_exception['trace'] =$trace_array;
        return $new_exception;
    }
}
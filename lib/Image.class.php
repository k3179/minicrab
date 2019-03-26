<?php

class Image {

    /**
     * get thumb image
     *
     * @param string $image_path
     * @param array $options
     * @throws Exception
     * @return bool,string
     */
    public static function thumb($image_path,$options){

        if(!is_file($image_path)){
            return false;
        }

        if(empty($optins['width']) && empty($otions['height'])){
            throw new Exception('width and height both empty');
        }

        if(!empty($options['width']) && (!is_numeric($options['width']) || $options['width']<1)){
            throw new Exception('width error');
        }

        if(!empty($options['height']) && (!is_numeric($options['height']) || $options['height']<1)){
            throw new Exception('height error');
        }

        if(empty($options['type']) || !in_array($options['type'],array('cut','resize'))){
            $options['type']    =   'cut';
        }

        $thumb_path  =   self::thumbPath($image_path,$options);
        if(is_file($thumb_path) && filemtime($thumb_path) < filemtime($image_path)){
            return self::thumbSrc($thumb_path);
        }

        $result =   false;
        if($options['type']=='cut'){
            $result  =   self::cut($image_path,$thumb_path,$options);
        }else if($options['type']=='resize'){
            $result  =   self::resize($image_path,$thumb_path,$options);
        }

        if($result){
            return self::thumbSrc($thumb_path);
        }else{
            return false;
        }
    }

    /**
     * cut timage
     *
     * @param string $image_path
     * @param string $thumb_path
     * @param array $options
     * @throws Exception
     * @return bool
     */
    private function cut($image_path,$thumb_path,$options){
        $image_info   =   self::getInfo($image_path);
        if(!$image_info) return false;
        return true;
    }

    /**
     * resize image
     *
     * @param string $image_path
     * @param string $thumb_path
     * @param array $options
     * @throws Exception
     * @return bool
     */
    private function resize($image_path,$thumb_path,$options){
        $image_info   =   self::getInfo($image_path);
        if(!$image_info) return false;
        return true;
    }

    /**
     * get image info
     *
     * @param string $image_path
     * @return bool,array
     */
    private function getInfo($image_path){
        $image_info   =   @getimagesize($image_path);
        if(empty($image_info) || empty($image_info[0]) || !is_numeric($image_info[0])){
            return false;
        }
    }

    /**
     * get thumb path
     *
     * @param string $image_path
     * @param array $options
     * @return string
     */
    private static function thumbPath($image_path,$options){
        $image_ext =  self::getExt($image_path);
        if(!$image_ext){
            $image_ext =  'jpg';
        }
        $thumb_path = THUMB_PATH.'/'.md5($image_path) . '_' . $options['type'] . '_' . $options['width'] . 'x' . $options['height'] . '.' . $image_ext;
        return $thumb_path;
    }


    /**
     * get thumb src url
     *
     * @param string $thumb_path
     * @return string
     */
    public static function thumbSrc($thumb_path){
        return str_replace(WWW_PATH,'/',$thumb_path);
    }

    /**
     * get image ext from image path
     *
     * @param string $image_path
     * @return string
     */
    private static function getExt($image_path){
        if(stripos($image_path,'.')===false){
            self::error(false,'no ext from image path');
            return null;
        }
        return substr($image_path, strrpos($image_path, '.') + 1);
    }


}
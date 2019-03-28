<?php

class Image {

    /**
     * get thumb image
     *
     * @param string $source_path
     * @param array $options
     * @throws Exception
     * @return bool,string
     */
    public static function thumb($source_path,$options){

        if(!$source_path || !is_string($source_path) || !is_file($source_path)){
            return false;
        }

        if(empty($options['type']) || !in_array($options['type'],array('cut','resize'))){
            throw new Exception('thumb type error');
        }

        if($options['type']=='cut'){
            if(empty($options['width']) || empty($options['height'])){
                throw new Exception('cut type need width and height');
            }
        }else if($options['type']=='resize'){
            if(empty($options['width']) && empty($options['height'])){
                throw new Exception('width and height both empty');
            }
        }

        if(!empty($options['width']) && (!is_numeric($options['width']) || $options['width']<1)){
            throw new Exception('width error');
        }

        if(!empty($options['height']) && (!is_numeric($options['height']) || $options['height']<1)){
            throw new Exception('height error');
        }

        $thumb_path  =   self::thumbPath($source_path,$options);
        if(is_file($thumb_path) && filemtime($thumb_path) >= filemtime($source_path)){
            return self::thumbSrc($thumb_path);
        }

        // create file directory
        File::makeFileDir($thumb_path);

        // call cut or resize method
        $result =   false;
        if($options['type']=='cut'){
            $result  =   self::cutThumb($source_path,$thumb_path,$options);
        }else if($options['type']=='resize'){
            $result  =   self::resizeThumb($source_path,$thumb_path,$options);
        }

        if($result){
            return self::thumbSrc($thumb_path);
        }else{
            return false;
        }
    }

    /**
     * get thumb image with cut type
     *
     * @param string $source_path
     * @param int $width
     * @param int $height
     * @throws Exception
     * @return bool,string
     */
    public static function cut($source_path,$width,$height){
        return self::thumb($source_path,['type'=>'cut','width'=>$width,'height'=>$height]);
    }

    /**
     * get thumb image with resize type
     *
     * @param string $source_path
     * @param int $width
     * @param int $height
     * @throws Exception
     * @return bool,string
     */
    public static function resize($source_path,$width,$height){
        return self::thumb($source_path,['type'=>'resize','width'=>$width,'height'=>$height]);
    }

    /**
     * cut image
     *
     * @param string $source_path
     * @param string $thumb_path
     * @param array $options
     * @throws Exception
     * @return bool
     */
    private function cutThumb($source_path,$thumb_path,$options){
        $image_info   =   self::getInfo($source_path);
        if(!$image_info) return false;
        // calculate size
        $old_rate   =  $image_info['width'] / $image_info['height'];
        $new_rate   =  $options['width'] / $options['height'];
        // compare rate
        if($old_rate < $new_rate){
            $source_width = $image_info['width'];
            $source_height = round($options['height'] * $image_info['width'] / $options['width']);
            $source_from_y = round(($image_info['height'] - $source_height) / 2);
            $source_from_x = 0;
        }else{
            $source_height = $image_info['height'];
            $source_width = round($options['width'] * $image_info['height'] / $options['height']);
            $source_from_x = round(($image_info['width'] - $source_width) / 2);
            $source_from_y = 0;
        }
        return self::createImage(
            $image_info['format'],
            $source_path,$thumb_path,
            $source_from_x,$source_from_y,
            0,0,
            $source_width,$source_height,
            $options['width'],$options['height']
        );
    }

    /**
     * resize image
     *
     * @param string $source_path
     * @param string $thumb_path
     * @param array $options
     * @throws Exception
     * @return bool
     */
    private function resizeThumb($source_path,$thumb_path,$options){
        $image_info   =   self::getInfo($source_path);
        if(!$image_info) return false;

        $rate_width    =  $rate_height =  0;
        if(!empty($options['width'])){
            $rate_width =  $options['width'] / $image_info['width'];
        }
        if(!empty($options['height'])){
            $rate_height =  $options['height'] / $image_info['height'];
        }
        $rate = ($rate_width && $rate_height) ? min($rate_width,$rate_height) : max($rate_width,$rate_height);

        $thumb_width =  round($image_info['width'] * $rate);
        $thumb_height =  round($image_info['height'] * $rate);
        return self::createImage(
            $image_info['format'],
            $source_path,$thumb_path,
            0,0,
            0,0,
            $image_info['width'],$image_info['height'],
            $thumb_width,$thumb_height
        );
    }

    /**
     * create image with detail params
     *
     * @param string $format
     * @param string $source_path
     * @param string $thumb_path
     * @param int $source_from_x
     * @param int $source_from_y
     * @param int $thumb_from_x
     * @param int $thumb_from_y
     * @param int $source_width
     * @param int $source_height
     * @param int $thumb_width
     * @param int $thumb_height
     * @return bool
     */
    private static function createImage(
        $format,
        $source_path,$thumb_path,
        $source_from_x,$source_from_y,
        $thumb_from_x,$thumb_from_y,
        $source_width,$source_height,
        $thumb_width,$thumb_height
    ){

        $thumb_image = imagecreatetruecolor($thumb_width,$thumb_height);
        $fill_color = imagecolorallocate($thumb_image, 255, 255, 255);
        imagefill($thumb_image,0,0,$fill_color);

        $source_image   =   null;
        switch($format){
            case 'jpg':
                $source_image  =   imagecreatefromjpeg($source_path);
                break;
            case 'gif':
                $source_image  =   imagecreatefromgif($source_path);
                break;
            case 'png':
                $source_image  =   imagecreatefrompng($source_path);
                imagealphablending($thumb_image,false);
                imagesavealpha($thumb_image,true);
                break;
        }

        imagecopyresampled($thumb_image,$source_image,$thumb_from_x,$thumb_from_y,$source_from_x,$source_from_y,$thumb_width,$thumb_height,$source_width,$source_height);

        $result  =   false;
        $quality =  100;
        switch($format){
            case 'jpg':
                if(!empty($_ENV['image']['jpg']['quality'])){
                    $quality =  $_ENV['image']['jpg']['quality'];
                }
                $result  =   imagejpeg($thumb_image,$thumb_path,$quality);
                break;
            case 'gif':
                if(!empty($_ENV['image']['gif']['quality'])){
                    $quality =  $_ENV['image']['gif']['quality'];
                }
                $result  =   imagegif($thumb_image,$thumb_path,$quality);
                break;
            case 'png':
                $quality =  9;
                if(!empty($_ENV['image']['png']['quality'])){
                    $quality =  $_ENV['image']['png']['quality'];
                }
                $result  =   imagepng($thumb_image,$thumb_path,$quality);
                break;
        }
        imagedestroy($source_image);
        imagedestroy($thumb_image);
        return $result;
    }


    /**
     * get image info
     *
     * @param string $image_path
     * @return bool,array
     */
    private function getInfo($image_path){
        $image_info   =   @getimagesize($image_path);
        if(
            empty($image_info) ||
            empty($image_info[0]) ||
            !is_numeric($image_info[0]) ||
            empty($image_info[1]) ||
            !is_numeric($image_info[1]) ||
            empty($image_info[2]) ||
            !in_array($image_info[2],array(1,2,3))
        ){
            return false;
        }
        $mime_array  =   array(
            1=>'gif',
            2=>'jpg',
            3=>'png'
        );
        $info =  array();
        $info['width'] =  $image_info[0];
        $info['height'] =  $image_info[1];
        $info['format'] =  $mime_array[$image_info[2]];
        $info['size'] =  filesize($image_path);
        return $info;
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
        return str_replace(WWW_PATH,'',$thumb_path).'?ver='.filemtime($thumb_path);
    }

    /**
     * get image ext from image path
     *
     * @param string $image_path
     * @return bool,string
     */
    private static function getExt($image_path){
        $image_name =  basename($image_path);
        if(stripos($image_name,'.')===false){
            return false;
        }
        return substr($image_path, strrpos($image_path, '.') + 1);
    }


}
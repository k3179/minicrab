<?php

class Cache {

    private static $mem_cache;

    /**
     * get cache data
     *
     * @param string $cache_name
     * @param mixed $cache_param
     * @throws Exception
     * @return array
     */
    public static function get($cache_name,$cache_param=null){
        if(!self::checkCacheName($cache_name)){
            throw new Exception('error cache name');
        }

        $mem_cache_data  =  self::getMemCache($cache_name,$cache_param);
        if($mem_cache_data !==null){
            return $mem_cache_data;
        }

        $file_cache_data =  self::getFileCache($cache_name,$cache_param);
        if($file_cache_data!==null){
            self::setMemCache($file_cache_data,$cache_name,$cache_param);
            return $file_cache_data;
        }

        $cache_data  =  self::set($cache_name,$cache_param);
        return $cache_data;
    }

    /**
     * set cache data with expire time
     *
     * @param string $expire_string (ex: 3d, 2h , 30m)
     * @param string $cache_name
     * @param mixed $cache_param
     * @return array
     */
	public static function getByTime($expire_string,$cache_name,$cache_param=null){
		$cache_file_path	=	self::getCacheFilePath($cache_name,$cache_param);
		$expire_time   =   self::getTime($expire_string);
        // not expired
        if(is_file($cache_file_path) && (time()-(filemtime($cache_file_path)))<$expire_time){
            $cache_data =   File::readJson($cache_file_path);
        }else{
            // expired , reset
            $cache_data =   self::set($cache_name,$cache_param);
        }
        return $cache_data;
	}

    /**
     * set unix_timestamp from expire_string
     *
     * @param string $expire_string (ex: 3d, 2h , 30m)
     * @throws Exception
     * @return int
     */
	private function getTime($expire_string){
        if(!preg_match('/[1-9][0-9]*[dhms]/i',$expire_string)){
			throw new Exception('expire_string format error');
		}
        if(is_numeric($expire_string)){   // second
            $unit   =   's';
            $num    =   (int)$expire_string;
        }else{
            $unit   =   substr($expire_string,-1);
            $num    =   (int)substr($expire_string,0,-1);
        }
        $time   =   0;
        switch($unit){
            case 'd':
                $time   =   3600*24*$num;
                break;
            case 'h':
                $time   =   3600*$num;
                break;
            case 'm':
                $time   =   60*$num;
                break;
            case 's':
                $time   =   $num;
                break;
        }
        return $time;
	}


    /**
     * set cache data ,save cache data to file
     *
     * @param string $cache_name
     * @param mixed $cache_param
     * @throws Exception
     * @return array
     */
    public static function set($cache_name,$cache_param=null){
        if(!self::checkCacheName($cache_name)){
            throw new Exception('error cache name');
        }
        $cache_file_path    =  CACHE_SOURCE_PATH.'/'.$cache_name.'.php';
        if(!is_file($cache_file_path)){
            throw new Exception('no cache source file');
        }
        // include to get $cache_data
        include $cache_file_path;
        if(!isset($cache_data)){
            throw new Exception('no cache_data defined');
        }
        self::setFileCache($cache_data,$cache_name,$cache_param);
        self::setMemCache($cache_data,$cache_name,$cache_param);
        return $cache_data;
    }


    /**
     * get cache from mem
     *
     * @param string $cache_name
     * @param mixed $cache_param
     * @throws Exception
     * @return array
     */
    private function getMemCache($cache_name,$cache_param=null){
        $cache_key   =  self::getCacheKey($cache_name,$cache_param);
        if(!$cache_key){
            throw new Exception('cache key error');
        }
        if(!empty(self::$mem_cache[$cache_key])){
            return self::$mem_cache[$cache_key];
        }
        return null;
    }

    /**
     * set cache to mem
     *
     * @param string $cache_name
     * @param mixed $cache_param
     * @throws Exception
     * @return void
     */
    private function setMemCache($cache_data,$cache_name,$cache_param=null){
        $cache_key   =  self::getCacheKey($cache_name,$cache_param);
        if(!$cache_key){
            throw new Exception('cache key error');
        }
        self::$mem_cache[$cache_key]  =  $cache_data;
    }






    /**
     * get cache from file
     *
     * @param string $cache_name
     * @param mixed $cache_param
     * @return array
     */
    private function getFileCache($cache_name,$cache_param=null){
		$cache_file_path	=	self::getCacheFilePath($cache_name,$cache_param);
		return File::readJson($cache_file_path);
    }

    /**
     * save cache data to file
     *
     * @param string $cache_name
     * @param mixed $cache_param
     * @return void
     */
    private function setFileCache($cache_data,$cache_name,$cache_param=null){
		$cache_file_path	=	self::getCacheFilePath($cache_name,$cache_param);
		File::writeJson($cache_file_path,$cache_data);
    }




    /**
     * cache cache name
     *
     * @param string $cache_name
     * @return bool
     */
    private function checkCacheName($cache_name){
        return is_string($cache_name) && preg_match("/^[a-z0-9\_]+$/i",$cache_name);
    }

    /**
     * get cache file path
     *
     * @param string $cache_name
     * @param mixed $cache_param
     * @return string
     */
    private function getCacheFilePath($cache_name,$cache_param=null){
        if($cache_param){
            $cache_path  =  CACHE_DATA_PATH.'/'.$cache_name.'/';
            $cache_file_name   =  '';
            if(is_numeric($cache_param)){
                $cache_file_name   =  $cache_param;
            }else if(is_string($cache_param)){
                if(preg_match('/^[a-z0-9\-\_]+$/i',$cache_param)){
                    $cache_file_name   =   $cache_param;
                }else{
                    $cache_file_name   =   md5($cache_param);
                }
            }else if(is_array($cache_param)){
                $cache_file_name   =   md5(json_encode($cache_param));
            }
            return $cache_path.$cache_file_name.'.php';
        }else{
            return CACHE_DATA_PATH.'/'.$cache_name.'.php';
        }
    }

    /**
     * get cache key with mem cache
     *
     * @param string $cache_name
     * @param mixed $cache_param
     * @return string
     */
    private function getCacheKey($cache_name,$cache_param=null){
        if($cache_param){
            if(is_numeric($cache_param)){
                return $cache_name.'@'.$cache_param;
            }else if(is_string($cache_param)){
                if(preg_match('/^[a-z0-9\-\_]+$/i',$cache_param)){
                    return $cache_name.'@'.$cache_param;
                }else{
                    return $cache_name.'@'.md5($cache_param);
                }
            }else if(is_array($cache_param)){
                return $cache_name.'@'.md5(json_encode($cache_param));
            }
        }else{
            return $cache_name;
        }
    }

}
<?php

class File {

    /**
     * write string to file path
     *
     * @param string $file_path
     * @param string $buff
     * @param string $mode
     * @return void
     */
    private function _write($file_path,$buff,$mode='w'){
        $f = fopen($file_path, $mode);
        $lock = flock($f, 2);
        if ($lock) {
            fwrite($f, $buff);
        }
        flock($f, 3);
        fclose($f);
    }

    /**
     * write string to file with make dir
     *
     * @param string $file_path
     * @param string $buff
     * @param string $mode
     * @return void
     */
    public static function write($file_path,$buff,$mode='w'){
        self::makeFileDir($file_path);
        self::_write($file_path,$buff,$mode);
    }


    /**
     * write log to file with make dir and append mode
     *
     * @param string $file_path
     * @param string $buff
     * @return void
     */
    public static function writeLog($file_path,$buff){
        self::write($file_path,$buff,'a');
    }

    /**
     * write array to file with make dir
     *
     * @param string $file_path
     * @param array $data
     * @return void
     */
    public static function writeJson($file_path,$data){
		$buff	=	json_encode($data);
        self::write($file_path,$buff);
    }


    /**
     * prepare file dir
     *
     * @param string $file_path
     * @return void
     */
    public static function makeFileDir($file_path){
		if(is_dir($file_path) || is_file($file_path)) return;
		$file_dir = substr($file_path, 0, strrpos($file_path, '/'));
		if(!is_dir($file_dir)){
			self::makeDir($file_dir);
		}
    }

    /**
     * make dir recursive
     *
     * @param string $file_dir
     * @return void
     */
    public static function makeDir($file_dir){
        $file_dir = trim($file_dir);
        if (!$file_dir){
            Error::show('File','makeDir','no file dir');
        }
		if(is_dir($file_dir)) return;
        mkdir($file_dir,0755,1);
    }

    /**
     * read string from file
     *
     * @param string $file_path
     * @return string
     */
    public static function read($file_path){
        if (
			!file_exists($file_path) || 
			!is_file($file_path) || 
			!filesize($file_path)
		){
            return '';
		}
        $f = fopen($file_path, 'r');
        $buff = fread($f, filesize($file_path));
        fclose($f);
        return $buff;
    }

    /**
     * read json data from file
     *
     * @param string $file_path
     * @return array
     */
    public static function readJson($file_path){
		$buff	=	self::read($file_path);
		if(!$buff) return null;
		return json_decode($buff,1);
    }

    /**
     * delete file
     *
     * @param string $file_path
     * @return bool
     */
    public static function deleteFile($file_path) {
        if (!$file_path || !is_file($file_path))
            return true;
        return unlink($file_path);
    }

}
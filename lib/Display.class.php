<?php

class Display {

    private static $html_title = '';
    private static $html_head_before   =  '';
    private static $html_head_after   =  '';
    private static $html_body_before   =  '';
    private static $html_body   =  '';
    private static $html_body_after   =  '';

    private static $js_files = array();
    private static $common_js_files = array();
    private static $css_files = array();
    private static $common_css_files = array();
    private static $other_link  =  array();

    // common meta http equiv
    private static $meta_http_equiv = array(
        'Content-Type' => 'text/html; charset=utf-8',
    );
    // common meta name
    private static $meta_name = array();
    private static $meta_keywords  =  array();
    private static $meta_description  =  '';

    /**
     * init function , register shutdown function
     *
     * @return void
     */
    public static function __init(){
        register_shutdown_function(array('Display', "__end"));
        ob_start();
    }


    /**
     * shutdown function , print all html
     *
     * @return void
     */
    public static function __end(){
        self::$html_body = ob_get_contents();
        ob_end_clean();
        echo self::getHtml();
    }

    /**
     * include view file with args
     *
     * @param string $path
     * @param array $args
     * @return void
     */
    public static function view($path,$args=null){
        if($args && is_array($args)){
            foreach($args as $key=>$value){
                $$key   =   $value;
            }
        }
        $view_file_path =   self::getViewPath($path);
        include $view_file_path;
    }

    /**
     * alias of view
     *
     * @param string $path
     * @param array $args
     * @return void
     */
    private function import($path,$args=null){
        self::view($path,$args);
    }

    /**
     * add js file to js file array
     *
     * @param string $js
     * @param bool $common
     * @return void
     */
    private function js($js,$common=false){
        if($common){
            if(!in_array(self::$common_js_files,self::$js_files)){
                self::$common_js_files[]  =   $js;
            }
        }else{
            if(!in_array($js,self::$js_files)){
                self::$js_files[]  =   $js;
            }
        }
    }

    /**
     * add common js file to js file array
     *
     * @param string $js
     * @return void
     */
    private function commonJs($js){
        self::js($js,true);
    }

    /**
     * add css file to js file array
     *
     * @param string $css
     * @param bool $common
     * @return void
     */
    private function css($css,$common=false){
        if($common){
            if(!in_array($css,self::$common_css_files)){
                self::$common_css_files[]  =   $css;
            }
        }else{
            if(!in_array($css,self::$css_files)){
                self::$css_files[]  =   $css;
            }
        }
    }

    /**
     * add common css file to js file array
     *
     * @param string $css
     * @return void
     */
    private function commonCss($css){
        self::css($css,true);
    }

    /**
     * add css file to js file array
     *
     * @param string $title
     * @return void
     */
    public static function title($title){
        self::$html_title  =   $title;
    }

    /**
     * get all html text
     *
     * @return string
     */
    private function getHtml(){
        $body_html =  self::getHtmlBody();
        $head_html = self::getHtmlHead();
        return "<!DOCTYPE html>" . "\n" .
            "<html>\n" .
            $head_html.
            $body_html.
            "</html>";
    }

    /**
     * get head html text
     *
     * @return string
     */
    private function getHtmlHead(){
        return "<head>\n" .
            self::$html_head_before .
            self::getMetaHtml() .
            self::getTitleHtml() .
            self::getLinkHtml() .
            self::getCssHtml() .
            self::getJsHtml() .
            self::$html_head_after .
            "</head>\n";
    }

    /**
     * get body html text
     *
     * @return string
     */
    private function getHtmlBody(){
        $html = "<body>\n" .
            self::$html_body_before .
            self::$html_body .
            self::$html_body_after .
            "\n</body>\n";

        $html = self::bodyCheckHeadContent($html);

        return $html;
    }


    /**
     * get meta html text
     *
     * @return string
     */
    private function getMetaHtml() {
        $html = '';
        if (!empty(self::$meta_http_equiv)) {
            foreach (self::$meta_http_equiv as $key => $value) {
                $html.= "<meta http-equiv=\"{$key}\" content=\"{$value}\">\n";
            }
        }
        if (!empty(self::$meta_name)) {
            foreach (self::$meta_name as $key => $value) {
                $html.= "<meta name=\"{$key}\" content=\"{$value}\">\n";
            }
        }
        return $html;
    }

    /**
     * get title html text
     *
     * @return string
     */
    private function getTitleHtml() {
        return "<title>".htmlspecialchars(self::$html_title)."</title>\n";
    }

    /**
     * get link html text
     *
     * @return string
     */
    private function getLinkHtml() {
        $html = '';
        if (!empty(self::$other_links)) {
            foreach (self::$other_links as $link) {
                $html.="<link ";
                foreach ($link as $key => $value) {
                    $html.="{$key}=\"{$value}\" ";
                }
                $html.="/>\n";
            }
        }
        return $html;
    }

    /**
     * get css html text
     *
     * @return string
     */
    private function getCssHtml() {
        if (empty(self::$css_files) && empty(self::$common_css_files))
            return '';
        $html = '';
        if(!empty(self::$common_css_files )){
            foreach(self::$common_css_files as $css_file){
                $ver    =   self::getFileVer($css_file);
                $html.= "<link rel=\"StyleSheet\" href=\"{$css_file}" . "?ver={$ver}" . "\" type=\"text/css\" media=\"all\" />\n";
            }
        }
        if(!empty(self::$css_files )){
            foreach (self::$css_files as $css_file) {
                $ver    =   self::getFileVer($css_file);
                $html.= "<link rel=\"StyleSheet\" href=\"{$css_file}" . "?ver={$ver}" . "\" type=\"text/css\" media=\"all\" />\n";
            }
        }
        return $html;
    }


    /**
     * get js html text
     *
     * @return string
     */
    private function getJsHtml() {
        if (empty(self::$js_files) && empty(self::$common_js_files))
            return '';

        $html = '';
        if (!empty(self::$common_js_files)) {
            foreach (self::$common_js_files as $js_file) {
                $ver    =   self::getFileVer($js_file);
                $html.= "<script type=\"text/javascript\" src=\"{$js_file}" . ($ver ? '?ver='.$ver : '') . "\"></script>\n";
            }
        }
        if (!empty(self::$js_files)) {
            foreach (self::$js_files as $js_file) {
                $ver    =   self::getFileVer($js_file);
                $html.= "<script type=\"text/javascript\" src=\"{$js_file}" . ($ver ? '?ver='.$ver : '') . "\"></script>\n";
            }
        }
        return $html;
    }


    /**
     * get view path from path string
     *
     * @param string $path
     * @throws Exception
     * @return string
     */
    private function getViewPath($path){
        if(!$path || !preg_match('/^[a-zA-Z0-9\-\_]+(\.[a-zA-Z0-9\-\_]+)*$/i',$path)){
            throw new Exception('view path error');
        }
        return ROOT_PATH.'/view/'.str_replace('.','/',$path).'.php';
    }

    /**
     * get file version from filemtime
     *
     * @param string $file_src
     * @return string
     */
    private function getFileVer($file_src){
        $ver  =  null;
        // ignore remote file
        if(!preg_match("/^https?\:\/\//i",$file_src)){
            if(preg_match("/^\//i",$file_src)){
                $file_path  =   WWW_PATH.'/'.substr($file_src,1);
            }else{
                // replace relative to absolute
                $file_path  =   WWW_PATH.'/'.substr(dirname($_SERVER['PHP_SELF']),1);
            }
            if(is_file($file_path)){
                $ver  =  filemtime($file_path);
            }
        }
        return $ver;
    }

    /**
     * move head html to head
     *
     * @param string $text
     * @return string
     */
    private function bodyCheckHeadContent($text) {
        $src = array(
            "/\<meta [^\>]+\>/i",
            "/\<link [^\>]+\>/i",
            "/\<style\>[^\<]*\<\/style\>/i",
        );
        $text = preg_replace_callback($src, 'self::moveToHead', $text);
        return $text;
    }

    /**
     * move matches to header
     *
     * @param array $matches
     * @return string
     */
    private function moveToHead($matches) {
        self::$html_head_after .= $matches[0] . "\n";
        return '';
    }


}
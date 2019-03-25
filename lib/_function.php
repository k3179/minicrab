<?php
/**
 * print var
 *
 * @param mixed $v
 * @return void
 */
function p($v){
    nprint($v);
}

/**
 * print var
 *
 * @param mixed $v
 * @return void
 */
function nprint($v){
    echo "<xmp>";
    print_r($v);
    echo "</xmp>";
}

/**
 * log var to error_log
 *
 * @param mixed $v
 * @return void
 */
function nlog($v){
    error_log(print_r($v,1));
}

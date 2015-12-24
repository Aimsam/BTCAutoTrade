<?php

/**
 * Class Logger
 * @author Tuzki
 * @desc 日志类，输出日志的方法
 */
class Logger {
    const INFO_LOG = 1;
    const WARNING_LOG = 2;
    const ERROR_LOG = 3;
    const FILE_TARGET = 1;
    const DB_TARGET = 2;
    const PRINT_TARGET = 3;

    private static $time = null;

    private static $log_level = array(
        self::INFO_LOG => 'info',
        self::WARNING_LOG => 'warning',
        self::ERROR_LOG => 'error'
    );

    private static $log_target = array(
        self::FILE_TARGET => '_file',
        self::DB_TARGET => '_db',
        self::PRINT_TARGET => '_print'
    );

    public static function Log($message, $level = self::INFO_LOG, $target = self::FILE_TARGET) {
        $func_name = self::$log_target[$target];
        $log = self::timeStamp();
        $log .= self::$log_level[$level] . ": $message \n";
        self::$func_name($log);
        // 返回用于发邮件~
        return $log;
    }

    private static function _file($log) {
        $file = fopen(APPLICATION_PATH . '/log/' . date('Y-m-d') . '.log', 'a');
        fwrite($file, $log);
    }

    private static function _db($log) {

    }

    private static function _print($log) {
        echo $log;
    }
    private static function timeStamp() {
        if(self::$time === null) {
            self::$time = microtime(true);
        }
        return date('Y-m-d H:i:s') . " | ";
    }
}
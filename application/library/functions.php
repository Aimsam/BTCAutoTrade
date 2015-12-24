<?php
function format_val($val) {
    if (is_numeric($val)) {
        return floatval($val);
    } else {
        return trim($val);
    }
}

function print_vars() {
    $vars = func_get_args();
    echo '<pre>';
    foreach ($vars as $var) {
        $string = var_export($var);
        echo htmlspecialchars($string);
    }
    echo '</pre>';
}

function get_account_config_keys($class_name) {
    $market_name = 'Market\\' . $class_name;
    $market = new $market_name;
    $reflection = new ReflectionProperty(get_class($market), 'config');
    $reflection->setAccessible(true);
    $configs = $reflection->getValue($market);
    $config_keys = array();
    foreach ($configs as $key => $value) {
        $config_keys[] = $key;
    }
    return $config_keys;
}


function get_config_keys() {
    $market_name = 'ConfigModel';
    $market = new $market_name('');
    $reflection = new ReflectionProperty(get_class($market), 'config');
    $reflection->setAccessible(true);
    $configs = $reflection->getValue($market);
    $config_keys = array();
    foreach ($configs as $key => $value) {
        $config_keys[] = $key;
    }
    return $config_keys;
}

/**
 * 下划线转驼峰
 * @param $string
 * @return mixed
 */
function underline_to_camel($string) {
    return preg_replace_callback('/_\w/', function($matches) {
        return strtoupper(substr($matches[0], 1, 1));
    }, $string);
    //return preg_replace('/_([a-zA-Z])/e', "strtoupper('\\1')", $string);
}

/**
 * 取文件最后$n行
 * @param string $filename 文件路径
 * @param int $n 最后几行
 * @return mixed false表示有错误，成功则返回字符串
 */
function file_last_lines($filename, $n) {
    if (!$fp = fopen($filename, 'r')) {
        return false;
    }
    $pos = -2;
    $eof = "";
    $str = "";
    while ($n > 0) {
        while ($eof != "\n") {
            if (!fseek($fp, $pos, SEEK_END)) {
                $eof = fgetc($fp);
                $pos--;
            } else {
                break;
            }
        }
        $str = fgets($fp) . $str;
        $eof = "";
        $n--;
    }
    return $str;
}
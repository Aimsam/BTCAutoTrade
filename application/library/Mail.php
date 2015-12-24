<?php

require APPLICATION_PATH . '/application/plugins/PHPMailer/PHPMailerAutoload.php';

class Mail {
    private static $redis_latest_mail_key = 'latest_mail_hash';
    public static function send($subject, $content) {
        // 十分钟一次
        $redis = Yaf\Registry::get('redis');
        $hash = $subject.'|'.intval(time() / 600);
        if($redis->get(self::$redis_latest_mail_key) == $hash) {
            return false;
        } else {
            // send mail
            $mail = new PHPMailer();
            $mail->isSMTP();
            $mail->Host = "smtp.163.com";
            $mail->Port = 25;
            $mail->SMTPAuth = true;
            $mail->Username = "ideawebcn@163.com";
            $mail->Password = "rabbitAimsam52";
            $mail->setFrom('ideawebcn@163.com', 'AutoTrade');
            // TODO
            $mail->addAddress('', '');
            $mail->Subject = $subject;
            $mail->Body = $content;
            if($mail->send()) {
                $redis->set(self::$redis_latest_mail_key, $hash);
                return true;
            } else {
                Logger::Log('Send mail failed' . $mail->ErrorInfo, Logger::ERROR_LOG);
                return false;
            }
        }
    }
}
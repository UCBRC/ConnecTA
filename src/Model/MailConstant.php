<?php

namespace App\Model;

use App\Service\PlainTextParsedown;
use Parsedown;

class MailConstant
{
    function renderCodePage($actionCN, $actionEN, $code)
    {

        $text = <<<EOD
您好！您在进行$actionCN 的操作。其验证码是：$code

请将其完整复制粘贴至对应的输入栏内。如果您没有进行相关操作，请无视本邮件

Hello! Your verification code for $actionEN is $code.

Please completely copy and paste it into the correct field. If you did not perform such action, please ignore this email.

EOD;
        return $this->base($text);
    }

    function renderRealnameFailed()
    {
        $text = <<<EOD
用户您好，您的账户认证申请已被退回，请登录查看详情。
Hello, your verification request has been rejected. Login to see more details.
EOD;
        return $this->base($text);
    }

    function renderRealnameSucceeded(string $timeCN, string $timeEN)
    {
        $text = <<<EOD
用户您好，您的账户认证已通过审核，有效期至$timeCN 。
Hello, your account has been verified. It will expire on $timeEN.
EOD;
        return $this->base($text);
    }

    function renderNoticePage(string $teacher, string $class, string $title, string $notice)
    {
        $parser = new PlainTextParsedown();
        $notice = $parser->parse($notice);
        $text = <<<EOD
$teacher posted a new note $title on the blackboard for group $class. There may be attachments, so please login in to see more details.
 
$notice

EOD;
        return $this->base($text);
    }

    function renderDeadlinePage(string $teacher, string $title, string $time, string $notice)
    {
        $parser = new PlainTextParsedown();
        $notice = $parser->parse($notice);
        $text = <<<EOD
$teacher wants to remind you that the deadline for $title is $time.
 
$notice

EOD;
        return $this->base($text);
    }

    function renderNewMessagePage(string $sender, string $content)
    {
        $paser = new PlainTextParsedown();
        $content = $paser->parse($content);
        $text = <<<EOD
您收到了来自用户 $sender 的一封新私信：

You have received a PM from user $sender:


$content
EOD;
        return $this->base($text);
    }

    function base($html)
    {
        return <<<EOD
UCBRC


$html


=========================

这篇邮件是由系统自动发送，请不要回复这封邮件。 

This email is sent by the system automatically. Please do not reply it.

EOD;
    }
}
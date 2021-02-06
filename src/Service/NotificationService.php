<?php
/**
 * Created by PhpStorm.
 * User: hqy
 * Date: 2018/7/22
 * Time: 5:51 PM
 */

namespace App\Service;


use App\Entity\School\Alumni;
use App\Entity\School\Notice;
use App\Entity\User\Chat;
use App\Entity\User\Device;
use App\Entity\User\User;
use App\Model\MailConstant;
use App\Type\AliyunTemplateType;
use App\Type\CodeActionType;
use App\Type\DeviceType;
use Doctrine\Common\Persistence\ObjectManager;
use Longman\TelegramBot\Telegram;

class NotificationService
{
    /**
     * @var SMSService
     */
    private $SMSService;
    /**
     * @var MailService
     */
    private $mailService;
    /**
     * @var APNSService
     */
    private $APNService;
    /**
     * @var ObjectManager
     */
    private $objectManager;
    /**
     * @var MailConstant
     */
    private $mailRenderer;
    /**
     * @var CacheService
     */
    private $cacheService;

    public function __construct(ObjectManager $objectManager, CacheService $cacheService, SMSService $SMSService, MailService $mailService, APNSService $APNSService)
    {
        $this->objectManager = $objectManager;
        $this->SMSService = $SMSService;
        $this->mailService = $mailService;
        $this->APNService = $APNSService;
        $this->cacheService = $cacheService;
        $this->mailRenderer = new MailConstant();
    }

    public function code($email = null, $phone = null, int $action) {
        switch ($action) {
            case CodeActionType::REGISTER:
                if (!is_null($email) && !$this->isEmailUsed($email)) {
                    $code = $this->getRandomString();
                    $this->cacheService->codeWrite($email, $code, $action);
                    return $this->mailService->send(
                        "no-reply@nfls.io",
                        "UCBRC",
                        $email,
                        "【UCBRC】账户注册 Account Registering",
                        $this->mailRenderer->renderCodePage(
                            "注册新账户",
                            "registering a new account",
                            $code
                        ));
                } else if (!is_null($phone) && !$this->isPhoneUsed($phone)) {
                    $code = $this->getRandomCode();
                    $this->cacheService->codeWrite($phone, $code, $action);
                    return $this->SMSService->send(
                        $phone,
                        AliyunTemplateType::REGISTER,
                        ["code" => $code]);
                }
                return false;
            case CodeActionType::RESET:
                if (!is_null($email) && $this->isEmailUsed($email)) {
                    $code = $this->getRandomString();
                    $this->cacheService->codeWrite($email, $code, $action);
                    return $this->mailService->send(
                        "no-reply@nfls.io",
                        "UCBRC",
                        $email,
                        "【UCBRC】重置密码 Password Resetting",
                        $this->mailRenderer->renderCodePage(
                            "重置账户密码",
                            "resetting your password",
                            $code
                        ));
                } else if (!is_null($phone) && $this->isPhoneUsed($phone)) {
                    $code = $this->getRandomCode();
                    $this->cacheService->codeWrite($phone, $code, $action);
                    return $this->SMSService->send(
                        $phone,
                        AliyunTemplateType::RESET,
                        ["code" => $code]);
                }
                return false;
            case CodeActionType::BIND:
                if (!is_null($email) && !$this->isEmailUsed($email)) {
                    $code = $this->getRandomString();
                    $this->cacheService->codeWrite($email, $code, $action);
                    return $this->mailService->send(
                        "no-reply@nfls.io",
                        "UCBRC",
                        $email,
                        "【UCBRC】邮箱绑定 Email Binding",
                        $this->mailRenderer->renderCodePage(
                            "绑定当前邮箱",
                            "binding this email with your account",
                            $code
                        ));
                } else if (!is_null($phone) && !$this->isPhoneUsed($phone)) {
                    $code = $this->getRandomCode();
                    $this->cacheService->codeWrite($phone, $code, $action);
                    return $this->SMSService->send(
                        $phone,
                        AliyunTemplateType::BIND,
                        ["code" => $code]);

                }
                return false;
        }
        return false;
    }

    /**
     * 返回值false时为邮箱，true时为手机，null时为不正确。
     */
    public function verify($email = null, $phone = null, string $code, int $action) {
        if(!is_null($email)) {
            if($this->cacheService->codeVerify($email, $code, $action))
                return false;
            else
                return null;
        } else if(!is_null($phone)) {
            if($this->cacheService->codeVerify($phone, $code, $action))
                return true;
            else
                return null;
        }
        return null;
    }

    public function realnamePassed(User $user, Alumni $alumni) {
        $expiryCN = "无期限";
        $expiryEN = "unlimited";
        $statusCN = $alumni->readableUserStatus()["zh"];
        $statusEN = $alumni->readableUserStatus()["en"];
        if(!is_null($alumni->getExpireAt())) {
            $expiryCN = $alumni->getExpireAt()->format("Y-m-d");
            $expiryEN = $expiryCN;
        }
        $this->mailService->send(
            "alumni@nfls.io",
            "UCBRC 实名认证系统",
            $user->getEmail(),
            "【UCBRC】实名认证 Verification",
            $this->mailRenderer->renderRealnameSucceeded(
                $statusCN,
                $statusEN,
                $expiryCN,
                $expiryEN));
        $this->APNService->bulk(
            $this->getDevices($user),
            "实名认证审核通过",
            "Verification passed",
            "您的实名认证审核已通过，有效期至$expiryCN ，当前身份为$statusCN 。You have been verified as $statusEN, which expires on $expiryEN",
            null,
            null,
            null);
        $this->SMSService->send(
            $user->getPhone(),
            AliyunTemplateType::REALNAME_SUCCEEDED,
            array("time"=>$expiryCN, "status"=>$statusCN));
    }

    public function realnameFailed(User $user) {
        $this->mailService->send(
            "alumni@nfls.io",
            "UCBRC 实名认证系统",
            $user->getEmail(),
            "【UCBRC】实名认证 Verification",
            $this->mailRenderer->renderRealnameFailed());
        $this->APNService->bulk(
            $this->getDevices($user),
            "实名认证审核退回",
            "Verification failed",
            "您的实名认证申请已被退回。Your verification request has been rejected.",
            null,
            null,
            null);
        $this->SMSService->send(
            $user->getPhone(),
            AliyunTemplateType::REALNAME_FAILED,
            array());
    }

    public function notifyNewMessage(Chat $chat) {
        $this->mailService->send(
            "message@nfls.io",
            "UCBRC 私信",
            $chat->getReceiver()->getEmail(),
            "【UCBRC】私信 PM",
            $this->mailRenderer->renderNewMessagePage($chat->getSender()->getUsername(), $chat->getContent())
        );
        $this->APNService->bulk(
            $this->getDevices($chat->getReceiver()),
            "来自 ".$chat->getSender()->getUsername()." 的新私信",
            "New message from user ".$chat->getSender()->getUsername(),
            $chat->getContent(),
            null,
            null,
            null);
        $this->SMSService->send($chat->getReceiver()->getPhone(),
            AliyunTemplateType::MESSAGE,
            array());
    }

    public function blackboardNotice(User $teacher, array $students, string $class, string $title, string $content) {
        $devices = [];
        $emails = [];
        $phones = [];
        foreach ($students as $student) {
            /** @var User $student */
            $devices = array_merge($devices, $this->getDevices($student));
            array_push($emails, $student->getEmail());
            array_push($phones, $student->getPhone());
        }
        $this->mailService->bulk(
            "study@nfls.io",
            "UCBRC Blackboard",
            $emails,
            "【UCBRC】New Notice",
            $this->mailRenderer->renderNoticePage($teacher->getValidAuth()->getEnglishName(), $class, $title, $content)
        );
        $this->SMSService->bulk(
            $phones,
            AliyunTemplateType::NOTICE,
            [
                "person" => mb_substr($teacher->getValidAuth()->getEnglishName(), 0, 20),
                "group" => mb_substr($class, 0, 20)
            ]
        );
        $parser = new PlainTextParsedown();
        $this->APNService->bulk(
            $devices,
            "New note for ". $class. " by " . $teacher->getValidAuth()->getEnglishName(),
            $title,
            $parser->text($content),
            1,
            null,
            null
        );
    }

    public function blackboardDeadline(User $teacher, array $students, string $title, string $content, string $deadline) {
        $devices = [];
        $emails = [];
        $phones = [];
        foreach ($students as $student) {
            /** @var User $student */
            $devices = array_merge($devices, $this->getDevices($student));
            array_push($emails, $student->getEmail());
            array_push($phones, $student->getPhone());
        }
        $this->mailService->bulk(
            "study@nfls.io",
            "UCBRC Blackboard",
            $emails,
            "【UCBRC】Deadline Reminder",
            $this->mailRenderer->renderDeadlinePage($teacher->getValidAuth()->getEnglishName(), $title, $deadline, $content)
        );
        $this->SMSService->bulk(
            $phones,
            AliyunTemplateType::DEADLINE,
            [
                "user" => mb_substr($teacher->getValidAuth()->getEnglishName(), 0, 20),
                "project" => mb_substr($title, 0, 20),
                "time" => $deadline
            ]
        );
        $parser = new PlainTextParsedown();
        $this->APNService->bulk(
            $devices,
            "Deadline reminder by " . $teacher->getValidAuth()->getEnglishName(),
            $title . " is due by " . $deadline,
            $parser->text($content),
            1,
            null,
            null
        );
    }

    public function cardEnabled(Alumni $ticket) {
        $this->SMSService->send($ticket->getUser()->getPhone(), AliyunTemplateType::CARD, ["name"=>$ticket->getChineseName()]);
        $this->APNService->bulk($this->getDevices(
            $ticket->getUser()),
            "通知",
            "您的出门证已启用",
            $ticket->getChineseName()."，您的出门证已启用。请更新App至最新版本。中午出门时，请打开App，扫描“出门”模块内的条形码后，即可出门。",
            0,
            null,
            null);
    }

    /**
     * @deprecated
     */
    public function newVerification(Alumni $ticket){
        $info = "【实名认证】新的实名认证请求，来自 " . $ticket->getChineseName(). "(".$ticket->getUser()->getUsername().")";
        $this->sendTelegram($info);
    }

    public function sendTelegram(string $text) {
        try {
            $telegram = new Telegram($_ENV["TELEGRAM_BOT_KEY"]);
            $result = \Longman\TelegramBot\Request::sendMessage(['chat_id'=>-1001244396269, 'text'=>$text]);
        } catch (\Exception $e) {
        }
    }

    private function getDevices(User $user) {
        return $this->objectManager->getRepository(Device::class)->findValidByUserAndType($user, DeviceType::IOS, true);
    }

    private function isPhoneUsed(?string $phone) {
        return ($this->objectManager->getRepository(User::class)->findOneBy(["phone" => $phone]) instanceof User);
    }

    private function isEmailUsed(?string $phone) {
        return ($this->objectManager->getRepository(User::class)->findOneBy(["email" => $phone]) instanceof User);
    }

    private function getRandomString() {
        return bin2hex(random_bytes(16));
    }

    private function getRandomCode() {
        return (string)mt_rand(100000, 999999);
    }
}
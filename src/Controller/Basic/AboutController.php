<?php

namespace App\Controller\Basic;

use App\Controller\AbstractController;
use App\Entity\Preference;
use App\Entity\User\Device;
use App\Service\APNSService;
use App\Service\CeleryService;
use App\Service\MailService;
use App\Service\SMSService;
use Longman\TelegramBot\Telegram;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatorInterface;

class AboutController extends AbstractController
{
}

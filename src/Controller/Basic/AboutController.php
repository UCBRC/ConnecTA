<?php

namespace App\Controller\Basic;

use App\Controller\ConnecTAController;
use App\Entity\Preference;
use App\Entity\User\Device;
use App\Service\APNSService;
use App\Service\CeleryService;
use App\Service\MailService;
use App\Service\SMSService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class AboutController extends ConnecTAController
{

}

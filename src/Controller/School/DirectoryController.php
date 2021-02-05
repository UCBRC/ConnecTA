<?php

namespace App\Controller\School;

use App\Controller\ConnecTAController;
use App\Entity\School\Alumni;
use App\Model\Permission;
use App\Service\CacheService;
use Doctrine\ORM\Cache;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class DirectoryController extends ConnecTAController
{
    /**
     * @Route("/alumni/directory/search", methods="POST")
     */
    public function search(Request $request)
    {
        $this->denyAccessUnlessGranted(Permission::IS_AUTHENTICATED);
        $alumni = $this->getDoctrine()->getManager()->getRepository(Alumni::class)->directoryQuery($request);
        $user = array_map(function ($val) {
            /** @var $val Alumni */
            return $val->getUser();
        }, $alumni);
        $user = array_values(array_unique($user));
        return $this->response()->responseEntity($user);
    }

    /**
     * @Route("/alumni/directory/query", methods="POST")
     */
    public function query(Request $request, TranslatorInterface $translator, CacheService $service)
    {
        $this->denyAccessUnlessGranted(Permission::IS_AUTHENTICATED);
        if(!$this->verifyCaptcha($request->request->get("captcha")))
            return $this->response()->response($translator->trans("incorrect-captcha"), Response::HTTP_UNAUTHORIZED);
        $text = $request->request->get("name");
        if(is_null($text) || $text == "")
            return $this->response()->response($translator->trans("blank-search"), Response::HTTP_FORBIDDEN);
        $registration = $request->request->get("registration");
        $class = $request->request->get("class");
        $alumni = $this->getDoctrine()->getManager()->getRepository(Alumni::class)->search($text, $registration, $class);
        $service->antiSpiderWrite($this->getUser(), $alumni, null);
        $user = array_map(function ($val) {
            /** @var $val Alumni */
            return $val->getUser();
        }, $alumni);
        $user = array_values(array_unique($user));
        return $this->response()->responseEntity($user);

    }
}

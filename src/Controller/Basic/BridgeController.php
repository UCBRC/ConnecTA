<?php
/**
 * Created by PhpStorm.
 * User: SYSTEM
 * Date: 2018/8/3
 * Time: 17:01
 */

namespace App\Controller\Basic;


use App\Controller\ConnecTAController;
use App\Entity\User\User;
use App\Service\NotificationService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BridgeController extends ConnecTAController
{

    /**
     * @Route("/bridge/notification", methods="POST")
     */
    public function notification(Request $request, NotificationService $service) {
        if($request->query->get("key") != $_ENV["BRIDGE_KEY"])
            return $this->response()->response(null, Response::HTTP_FORBIDDEN);
        $students = $this->getDoctrine()->getManager()->getRepository(User::class)->findByIds($request->request->get("students"));
        $teacher = $this->getDoctrine()->getManager()->getRepository(User::class)->find($request->request->get("teacher"));
        $title = $request->request->get("title");
        $content = $request->request->get("content");
        $class = $request->request->get("class");
        $deadline = $request->request->get("deadline");

        if(is_null($title) || is_null($content) || is_null($teacher) || is_null($students) || empty($students)){
            return $this->response()->response(null, Response::HTTP_FORBIDDEN);
        }
        if(!is_null($deadline)) {
            $service->blackboardDeadline($teacher, $students, $title, $content, $deadline);
            return $this->response()->response(null);
        }
        if(!is_null($class)) {
            $service->blackboardNotice($teacher, $students, $class, $title, $content);
            return $this->response()->response(null);
        }
        return $this->response()->response(null, Response::HTTP_FORBIDDEN);
    }
}
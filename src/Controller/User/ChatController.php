<?php

namespace App\Controller\User;

use App\Controller\ConnecTAController;
use App\Entity\User\Chat;
use App\Entity\User\User;
use App\Model\Permission;
use App\Service\CacheService;
use App\Service\NotificationService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class ChatController extends ConnecTAController
{

    /**
     * @Route("/chat/list", methods="GET")
     */
    public function list(Request $request)
    {
        $this->denyAccessUnlessGranted(Permission::IS_LOGIN);
        $this->updateUser();
        $list = $this->getDoctrine()->getManager()->getRepository(Chat::class)->list(
            $this->getUser(),
            $request->query->getInt("page", 1)
        );
        foreach ($list as $message) {
            /** @var $message Chat */
            $message->canReply = $message->getSender() !== $this->getUser();
        }
        return $this->response()->responseEntity(
            $list
        );
    }

    private function updateUser()
    {
        $this->getUser()->setReadTime(new \DateTime());
        $em = $this->getDoctrine()->getManager();
        $em->persist($this->getUser());
        $em->flush();
    }


    /**
     * @Route("/chat/send", methods="POST")
     */
    public function send(Request $request, TranslatorInterface $translator, NotificationService $service, CacheService $cacheService)
    {
        $this->denyAccessUnlessGranted(Permission::IS_LOGIN);
        $this->denyAccessUnlessGranted(Permission::IS_AUTHENTICATED);
        if(!$cacheService->rateVerify($this->getUser())) {
            return $this->response()->response($translator->trans("rate-limited"), Response::HTTP_FORBIDDEN);
        }
        $user = $this->getDoctrine()->getManager()->getRepository(User::class)->find(
            $request->request->getInt("id")
        );
        if (is_null($user))
            return $this->response()->response($translator->trans("receiver-not-exist"), Response::HTTP_FORBIDDEN);
        $chat = new Chat();
        $chat->setSender($this->getUser());
        $chat->setReceiver($user);
        if($chat->getReceiver() === $chat->getSender())
            return $this->response()->response($translator->trans("cannot-send-to-itself"),Response::HTTP_FORBIDDEN);
        $chat->setContent($request->request->get("content"));
        $em = $this->getDoctrine()->getManager();
        $em->persist($chat);
        $em->flush();
        $service->notifyNewMessage($chat);
        $cacheService->rateWrite($this->getUser());
        return $this->response()->response(null);
    }
}

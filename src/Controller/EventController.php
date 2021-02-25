<?php


namespace App\Controller;


use App\Controller\ConnecTAController;
use App\Entity\Event;
use App\Model\Permission;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class EventController extends ConnecTAController
{
    /**
     * @Route("/event/list", methods="GET")
     */
    public function list() {
        $this->denyAccessUnlessGranted(Permission::IS_LOGIN);
        if($this->getUser()->hasRole(Permission::IS_ADMIN))
            return $this->response()->responseJsonEntity($this
                ->getDoctrine()
                ->getManager()
                ->getRepository(Event::class)
                ->findAll());
        else
            return $this->response()->responseJsonEntity($this
                ->getDoctrine()
                ->getManager()
                ->getRepository(Event::class)
                ->findByPublished(true));
    }

    /**
     * @Route("/event/detail", methods="GET")
     */
    public function detail(Request $request) {
        $this->denyAccessUnlessGranted(Permission::IS_LOGIN);
        $id = $request->query->get("id");
        $event = $this->getDoctrine()->getManager()->getRepository(Event::class)->find($id);
        return $this->response()->responseEntity($event);
    }

    /**
     * @Route("/event/join", methods="POST")
     */
    public function join(Request $request, TranslatorInterface $translator, UserPasswordEncoderInterface $passwordEncoder) {
        $this->denyAccessUnlessGranted(Permission::IS_LOGIN);
        if(!$this->getUser()->hasRole(Permission::IS_STUDENT))
            return $this->response()->response($translator->trans("not-eligible-to-vote"), Response::HTTP_UNAUTHORIZED);
        $id = $request->request->get("id");
        /** @var Event $event */
        $event = $this->getDoctrine()->getManager()->getRepository(Event::class)->find($id);
        $em = $this->getDoctrine()->getManager();
        $event->addUser($this->getUser());
        $em->persist($event);
        $em->flush();
        return $this->response()->responseEntity($event);
    }

    /**
     * @Route("/event/edit")
     */
    public function edit(Request $request) {
        $this->denyAccessUnlessGranted(Permission::IS_ADMIN);
        $id = $request->query->get("id");

        $em = $this->getDoctrine()->getManager();
        if($id == "new")
            $event = new Event();
        else
            $event = $em->getRepository(Event::class)->find($id) ?? new Event();
        if($request->isMethod("POST")) {
            $event->setTitle($request->request->get("title"));
            $event->setContent($request->request->get("content"));
            $event->setThumbnail($request->request->get('thumbnail'));
            $event->setInstruction($request->request->get('instruction'));
            $event->setPublished($request->request->getBoolean('published'));
            $em->persist($event);
            $em->flush();
        }
        return $this->response()->responseEntity($event);
    }

}
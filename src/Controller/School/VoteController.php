<?php
/**
 * Created by PhpStorm.
 * User: hqy
 * Date: 2018/7/23
 * Time: 12:51 PM
 */

namespace App\Controller\School;


use App\Controller\ConnecTAController;
use App\Entity\School\Ticket;
use App\Entity\School\Vote;
use App\Model\Permission;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class VoteController
 * @deprecated No longer used.
 */
// Ctrl + Slash to recover
class VoteController extends ConnecTAController
{
//    /**
//     * @Route("/school/vote/list", methods="GET")
//     */
//    public function list() {
//        $this->denyAccessUnlessGranted(Permission::IS_LOGIN);
//        //if($this->getUser()->hasRole(Permission::IS_ADMIN))
//            return $this->response()->responseJsonEntity($this
//                ->getDoctrine()
//                ->getManager()
//                ->getRepository(Vote::class)
//                ->findAll());
//        /*else
//            return $this->response()->responseJsonEntity($this
//                ->getDoctrine()
//                ->getManager()
//                ->getRepository(Vote::class)
//                ->findByEnabled(true));
//        */
//    }
//
//    /**
//     * @Route("/school/vote/detail", methods="GET")
//     */
//    public function detail(Request $request) {
//        $this->denyAccessUnlessGranted(Permission::IS_LOGIN);
//        $id = $request->query->get("id");
//        $vote = $this->getDoctrine()->getManager()->getRepository(Vote::class)->find($id);
//        return $this->response()->responseEntity($vote);
//    }
//
//    /**
//     * @Route("/school/vote/vote", methods="POST")
//     */
//    public function vote(Request $request, TranslatorInterface $translator, UserPasswordEncoderInterface $passwordEncoder) {
//        $this->denyAccessUnlessGranted(Permission::IS_LOGIN);
//        if(!$this->getUser()->hasRole(Permission::IS_STUDENT))
//            return $this->response()->response($translator->trans("not-eligible-to-vote"), Response::HTTP_UNAUTHORIZED);
//
//        $auth = $this->getUser()->getValidAuth();
//        if($auth->getSeniorSchool() !== 2 and $auth->getSeniorSchool() !== 3)
//            return $this->response()->response($translator->trans("not-eligible-to-vote"), Response::HTTP_UNAUTHORIZED);
//        if($auth->getSeniorRegistration() < 2019 || $auth->getSeniorRegistration() > 2021)
//            return $this->response()->response($translator->trans("not-eligible-to-vote"), Response::HTTP_UNAUTHORIZED);
//        $id = $request->request->get("id");
//        /** @var Vote $vote */
//        $vote = $this->getDoctrine()->getManager()->getRepository(Vote::class)->find($id);
//        if(is_null($vote))
//            return $this->response()->response($translator->trans("vote-not-enabled"), Response::HTTP_UNAUTHORIZED);
//        if(!$vote->isEnabled())
//            return $this->response()->response($translator->trans("vote-not-enabled"), Response::HTTP_UNAUTHORIZED);
//        $em = $this->getDoctrine()->getManager();
//        if(!is_null($em->getRepository(Ticket::class)->findOneByUserAndVote($this->getUser(), $vote)))
//            return $this->response()->response($translator->trans("already-submit"), Response::HTTP_FORBIDDEN);
//        try {
//            if(!$passwordEncoder->isPasswordValid($this->getUser(), $request->request->get("password"))) {
//                return $this->response()->response($translator->trans("incorrect-password"), Response::HTTP_BAD_REQUEST);
//            }
//            if($this->getUser()->isOAuth && !$request->request->has("clientId")) {
//                return $this->response()->response($translator->trans("invalid-client"). Response::HTTP_BAD_REQUEST);
//            }
//            $ticket = new Ticket($vote, $this->getUser(), $request->request->get("choices"), ($request->headers->get("X-Forwarded-For") ?? "") . "|" . $request->getClientIp() , $request->headers->get("user-agent"), $request->request->get("clientId") ?? "");
//            $em->persist($ticket);
//            $em->flush();
//            $info = json_encode([
//                "request" => $request->request->all(),
//                "query" => $request->query->all(),
//                "cookies" => $request->cookies->all(),
//                "server" => $request->server->all(),
//                "file" => $request->files->all(),
//                "user" => $this->getUser()->getInfoArray()
//            ]);
//            file_put_contents("/var/log/vote.log", $info, FILE_APPEND);
//            $this->writeLog("UserVoted", json_encode($request->request->get("choices")));
//            return $this->response()->responseEntity($ticket, Response::HTTP_OK);
//        } catch(\Exception $e) {
//            return $this->response()->response($translator->trans("invalid-ticket"), Response::HTTP_BAD_REQUEST);
//        }
//    }
//
//    /**
//     * @Route("/school/vote/edit")
//     */
//    public function edit(Request $request) {
//        $this->denyAccessUnlessGranted(Permission::IS_ADMIN);
//        $id = $request->query->get("id");
//
//        $em = $this->getDoctrine()->getManager();
//        if($id == "new")
//            $vote = new Vote();
//        else
//            $vote = $em->getRepository(Vote::class)->find($id) ?? new Vote();
//        if($request->isMethod("POST")) {
//            $vote->setTitle($request->request->get("title"));
//            $vote->setContent($request->request->get("content"));
//            $vote->setOptions($request->request->get('options'));
//            $vote->setEnabled($request->request->getBoolean('enabled'));
//            $em->persist($vote);
//            $em->flush();
//        }
//        return $this->response()->responseEntity($vote);
//    }
//
//    /**
//     * @Route("/school/vote/result", methods="GET")
//     */
//    public function result(Request $request) {
//
//        $this->denyAccessUnlessGranted(Permission::IS_ADMIN);
//        $id = $request->query->get("id");
//        if($id == 'new')
//            return $this->response()->response(null);
//        $em = $this->getDoctrine()->getManager();
//        /** @var Vote $vote */
//        $vote = $em->getRepository(Vote::class)->find($id);
//        $tickets = $em->getRepository(Ticket::class)->findBy(["vote" => $vote]);
//        $result = array();
//        for($i=0; $i<count($vote->getOptions()); $i++) {
//            $result[$i] = array();
//            for($j=0; $j<count($vote->getOptions()[$i]["options"]); $j++) {
//                $result[$i][$j] = 0;
//            }
//        }
//        foreach ($tickets as $ticket) {
//            /** @var Ticket $ticket*/
//            foreach ($ticket->getChoices() as $key => $choice) {
//                $result[$key][$choice] ++;
//            }
//        }
//        return $this->response()->response(array("total" => count($tickets), "detail" => $result));
//    }
}
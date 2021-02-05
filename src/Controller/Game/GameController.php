<?php

namespace App\Controller\Game;

use App\Controller\ConnecTAController;
use App\Entity\Game\Game;
use App\Entity\Game\Rank;
use App\Model\Permission;
use phpseclib\Crypt\AES;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GameController extends ConnecTAController
{

    /**
     * @Route("/game/list", methods="GET")
     */
    public function list()
    {
        return $this->response()->responseRawEntity($this->getDoctrine()->getManager()->getRepository(Game::class)->findAll());
    }

    /**
     * @Route("game/listRank", methods="GET")
     */
    public function listRank()
    {
        return $this->response()->responseEntity($this->getDoctrine()->getManager()->getRepository(Rank::class)->getAllRankByUser($this->getUser()));
    }

    /**
     * @Route("game/update", methods="POST")
     */
    public function update(Request $request)
    {
        $cipher = new AES(AES::MODE_ECB);
        $this->denyAccessUnlessGranted(Permission::IS_LOGIN);
        $cipher->setKey($_ENV["GAME_KEY"]);
        $score = floatval($cipher->decrypt(base64_decode($request->request->get("value"))));
        if($score < 0 || $score > 3)
            return $this->response()->response(null, Response::HTTP_FORBIDDEN);
        $user = $this->getUser();
        if(!$user->isOAuth)
            return $this->response()->response(null, Response::HTTP_FORBIDDEN);
        $user->setPoint($user->getPoint() + $score);
        $this->getDoctrine()->getManager()->persist($user);
        $this->getDoctrine()->getManager()->flush();
        $this->writeLog("GameScoreUpdate", (string)$score, $this->getUser());
        return $this->response()->response(null);
    }

    /**
     * @Route("game/online", methods="POST")
     */
    public function online(Request $request) {
        $this->denyAccessUnlessGranted(Permission::IS_LOGIN);
        $user = $this->getUser();
        if(!$user->isOAuth)
            return $this->response()->response(null, Response::HTTP_FORBIDDEN);
        $user->setPoint($user->getPoint() + 1.0/4.0 );
        $this->getDoctrine()->getManager()->persist($user);
        $this->getDoctrine()->getManager()->flush();
        $this->writeLog("GameUptimeUpdate", "", $this->getUser());
        return $this->response()->response(null);
    }
    /**
     * @Route("game/log", methods="POST")
     */
    public function log(Request $request)
    {
        $this->writeLog("GameCustomLog", $request->request->get("message"));
        return $this->response()->response(null);
    }


}

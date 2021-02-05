<?php

namespace App\Controller\User;

use App\Controller\ConnecTAController;
use App\Entity\OAuth\AccessToken;
use App\Entity\OAuth\RefreshToken;
use App\Entity\School\Alumni;
use App\Entity\User\Chat;
use App\Entity\User\User;
use App\Model\Permission;
use App\Model\PrivacyLevel;
use App\Service\CacheService;
use App\Service\NotificationService;
use App\Type\AliyunTemplateType;
use App\Type\CodeActionType;
use GuzzleHttp\Client;
use function GuzzleHttp\Psr7\str;
use OTPHP\OTP;
use OTPHP\TOTP;
use ParagonIE\ConstantTime\Base32;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserController extends ConnecTAController
{
    /**
     * @Route("/user/register", name="register", methods="POST")
     */
    public function register(Request $request, NotificationService $service, UserPasswordEncoderInterface $passwordEncoder, TranslatorInterface $translator)
    {
        $em = $this->getDoctrine()->getManager();
        $user = new User();
        $username = $request->request->get("username");
        if(!is_null($this->getDoctrine()->getManager()->getRepository(User::class)->findOneBy(["username"=>$username]))){
            return $this->response()->response($translator->trans("already-used-username"), Response::HTTP_BAD_REQUEST);
        }
        if ($this->verifyUsername($username)) {
            $user->setUsername($username);
        } else {
            return $this->response()->response($translator->trans("illegal-username"), Response::HTTP_UNAUTHORIZED);
        }
        $password = $request->request->get("password");
        if ($this->verifyPassword($user, $password)) {
            $user->setPassword($passwordEncoder->encodePassword($user, $password));
        } else {
            return $this->response()->response($translator->trans("illegal-password"), Response::HTTP_UNAUTHORIZED);
        }
        $result = $service->verify(
            $request->request->get("email"),
            $request->request->get("phone"),
            $request->request->get("code"),
            CodeActionType::REGISTER);
        if (!is_null($result)) {
            if ($result === true) {
                $user->setPhone($request->request->get("phone"));
            } else {
                $user->setEmail($request->request->get("email"));
            }
        } else {
            return $this->response()->response($translator->trans("incorrect-code"), Response::HTTP_UNAUTHORIZED);
        }
        $em->persist($user);
        $em->flush();
        $user = $em->getRepository(User::class)->findByUsername($username);
        $this->writeLog("UserCreated", null, $user);
        $this->getDefaultAvatar($username, $user->getId());
        return $this->response()->response(null);
    }


    /**
     * @Route("/user/login", name="login")
     */
    public function login(Request $request, UserPasswordEncoderInterface $passwordEncoder, TranslatorInterface $translator, CacheService $cacheService)
    {
        $session = $request->getSession();
        if (!$session)
            $session = new Session();
        $session->start();
        $user = $this->getDoctrine()->getRepository(User::class)->search($request->request->get("username"));
        if (null === $user)
            return $this->response()->response($translator->trans("incorrect-username-or-password"), Response::HTTP_UNAUTHORIZED);
        if(!$cacheService->rateVerify($user))
            return $this->response()->response($translator->trans("rate-limited"), Response::HTTP_FORBIDDEN);
        if(!$user->isEnabled())
            return $this->response()->response($translator->trans("banned"), Response::HTTP_UNAUTHORIZED);
        if ($passwordEncoder->isPasswordValid($user, $request->request->get("password", $user->getSalt()))) {
            $session->set("user_token", $user->getToken());
            $this->writeLog("UserLoginSucceeded", null, $user);
            if ($request->request->get("remember") == "true") {
                $response = $this->response()->response(null);
                $time = new \DateTime();
                $time->add(new \DateInterval("P1M"));
                $response->headers->setCookie(new Cookie("remember_token", $user->getToken(), $time, "/", null, false, true));
                return $response;
            }
            return $this->response()->response(null);
        } else {
            $this->writeLog("UserLoginFailed", null, $user);
            $cacheService->rateWrite($user);
            return $this->response()->response($translator->trans("incorrect-username-or-password"), Response::HTTP_UNAUTHORIZED);
        }
    }


    /**
     * @Route("/user/reset", methods="POST")
     */
    public function reset(Request $request, NotificationService $service, UserPasswordEncoderInterface $passwordEncoder, TranslatorInterface $translator)
    {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository(User::class);

        $result = $service->verify(
            $request->request->get("email"),
            $request->request->get("phone"),
            $request->request->get("code"),
            CodeActionType::RESET);
        if (!is_null($result)) {
            if ($result === true) {
                $user = $repo->findOneBy(["phone" => $request->request->get("phone")]);
            } else {
                $user = $repo->findOneBy(["email" => $request->request->get("email")]);
            }
        } else {
            return $this->response()->response($translator->trans("incorrect-code"), Response::HTTP_UNAUTHORIZED);
        }
        $password = $request->request->get("password");
        if ($this->verifyPassword($user, $password)) {
            $user->setPassword($passwordEncoder->encodePassword($user, $password));
        } else {
            return $this->response()->response($translator->trans("illegal-password"), Response::HTTP_UNAUTHORIZED);
        }
        $em->persist($user);
        $em->flush();
        $this->writeLog("UserPasswordReset", null, $user);
        return $this->response()->response(null);
    }


    /**
     * @Route("/user/fastLogin", name="fastLogin")
     */
    public function fastLogin(Request $request) {
        $this->denyAccessUnlessGranted(Permission::IS_LOGIN);
        $user = $this->getUser();
        $session = $request->getSession();
        if (!$session)
            $session = new Session();
        $session->start();
        $session->set("user_token", $user->getToken());
        if($request->query->has("realname"))
            $response = new RedirectResponse("/#/alumni/auth");
        else
            $response = new RedirectResponse("/");
        return $response;

    }


    /**
     * @Route("/user/logout", methods="POST")
     */
    public function logout(Request $request)
    {
        $response = $this->response()->response(null, 200);
        $time = new \DateTime();
        $time->sub(new \DateInterval("P1M"));
        $response->headers->setCookie(new Cookie("remember_token", "deleted", $time, "/", null, false, true));
        $response->headers->setCookie(new Cookie("PHPSESSID", "deleted", $time, "/", null, false, true));
        return $response;
    }

    /**
     * @Route("/user/current", name="User(Current)", methods="GET")
     */
    public function current()
    {
        $this->denyAccessUnlessGranted(Permission::IS_LOGIN);
        if (null === $this->getUser())
            return $this->response()->response(null, Response::HTTP_NO_CONTENT);
        $info = $this->getUser()->getInfoArray();
        $info["unread"] = $this->getDoctrine()->getManager()->getRepository(Chat::class)->getInboxCount($this->getUser());
        return $this->response()->responseRawEntity($info);
    }

    /**
     * @Route("user/info", name="User(Info)", methods="GET")
     */
    public function info(Request $request)
    {
        $info = $request->query->get("info") ?? "";
        $repo = $this->getDoctrine()->getManager()->getRepository(User::class);
        $user = $repo->search($info);
        if (@null === $user) {
            return $this->response()->response(null, Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        return $this->response()->responseEntity($user);
    }

    /**
     * @Route("user/change", methods="POST")
     */
    public function change(Request $request, NotificationService $service, UserPasswordEncoderInterface $passwordEncoder, TranslatorInterface $translator)
    {
        $this->denyAccessUnlessGranted(Permission::IS_LOGIN);
        if ($passwordEncoder->isPasswordValid($this->getUser(), $request->request->get("password"))) {
            $newPassword = $request->request->get("newPassword");
            $newEmail = $request->request->get("newEmail");
            $unbindPhone = $request->request->getBoolean("unbindPhone", false);
            $newPhone = $request->request->get("newPhone");
            $phoneCode = $request->request->get("phoneCode") ?? "";
            $emailCode = $request->request->get("emailCode") ?? "";
            $clean = $request->request->getBoolean("clean", false);
            $user = $this->getUser();
            if ($newPassword) {
                if (!$this->verifyPassword($user, $newPassword))
                    return $this->response()->response($translator->trans("illegal-password"), Response::HTTP_UNAUTHORIZED);
                $password = $passwordEncoder->encodePassword($this->getUser(), $newPassword);
                $user->setPassword($password);
            }
            if ($newEmail && is_null($user->getEmail())) {
                if ($service->verify($newEmail, null , $emailCode, CodeActionType::BIND) === false)
                    $user->setEmail($newEmail);
                else
                    return $this->response()->response($translator->trans("incorrect-code"), Response::HTTP_UNAUTHORIZED);
            }

            if ($unbindPhone) {
                if ($user->getEmail()) {
                    $user->setPhone(null);
                } else {
                    return $this->response()->response($translator->trans("email-not-bind"), Response::HTTP_UNAUTHORIZED);
                }
            } else if ($newPhone) {
                if ($service->verify(null, $newPhone, $phoneCode, CodeActionType::BIND) === true)
                    $user->setPhone($newPhone);
                else
                    return $this->response()->response($translator->trans("incorrect-code"), Response::HTTP_UNAUTHORIZED);
            }

            if ($clean) {
                $this->clean();
                $user->setWeChatToken(null);
            }

            $this->writeLog("UserSecurityChanged");
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();
            return $this->response()->response(null, Response::HTTP_OK);
        } else {
            return $this->response()->response(null, Response::HTTP_UNAUTHORIZED);
        }
    }

    private function clean() {
        $this->denyAccessUnlessGranted(Permission::IS_LOGIN);
        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $accessTokens = $em->getRepository(AccessToken::class)->findBy(["user" => $user]);
        foreach($accessTokens as $accessToken) {
            $refreshTokens = $em->getRepository(RefreshToken::class)->findBy(["accessToken" => $accessToken]);
            foreach($refreshTokens as $refreshToken) {
                $em->remove($refreshToken);
            }
            $em->remove($accessToken);
        }
        $em->flush();
        return $this->response()->response(null);
    }

    /**
     * @Route("/user/avatar", methods="POST")
     */
    public function avatar(Request $request)
    {
        $this->denyAccessUnlessGranted(Permission::IS_LOGIN);
        $originalPhoto = $request->files->get("photo");
        $path = $originalPhoto->getRealPath();
        $avatar = new \Imagick($path);
        $height = $avatar->getImageHeight();
        $width = $avatar->getImageWidth();
        if ($height > $width) {
            $avatar->cropImage($width, $width, 0, ($height - $width) / 2);
        } else {
            $avatar->cropImage($width, $width, ($width - $height) / 2, 0);
        }
        $avatar->resizeImage(200, 200, \Imagick::FILTER_LANCZOS, 1);
        $avatar->setImageFormat("png");
        $avatar->writeImage($this->get('kernel')->getRootDir() . "/../public/avatar/" . strval($this->getUser()->getId()) . ".png");
        return $this->response()->response(null, Response::HTTP_OK);
    }

    /**
     * @Route("/user/rename", methods="POST")
     */
    public function rename(Request $request, TranslatorInterface $translator)
    {
        $this->denyAccessUnlessGranted(Permission::IS_LOGIN);
        $username = $request->request->get("username");
        if(!is_null($this->getDoctrine()->getManager()->getRepository(User::class)->findOneBy(["username"=>$username]))){
            return $this->response()->response($translator->trans("already-used-username"), Response::HTTP_BAD_REQUEST);
        }
        if ($this->verifyUsername($username)) {
            if ($this->getUser()->getPoint() >= 2) {
                $this->getUser()->minusPoints(2);
                $this->getUser()->setUsername($username);
                $em = $this->getDoctrine()->getManager();
                $em->persist($this->getUser());
                $em->flush();
                $this->writeLog("UserRenamed");
                return $this->response()->response(null);
            } else {
                return $this->response()->response($translator->trans("not-enough-points"), Response::HTTP_FORBIDDEN);
            }

        } else {
            return $this->response()->response($translator->trans("illegal-username"), Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * @Route("/user/weChat", methods="POST")
     */
    public function weChat(Request $request) {
        $this->denyAccessUnlessGranted(Permission::IS_LOGIN);
        $user = $this->getUser();
        $token = $this->verifyWeChat($request->request->get("token"));
        if(!$token)
            return $this->response()->response(null, Response::HTTP_UNAUTHORIZED);

        $openid = $token["openid"];

        $manager = $this->getDoctrine()->getManager();

        $existingUser = $this->getDoctrine()->getRepository(User::class)->findOneBy(["weChatToken" => $openid]);
        if (!is_null($existingUser)) {
            /** @var $existingUser User */
            $existingUser->setWeChatToken(null);
            $manager->persist($existingUser);
            $this->writeLog("UserWeChatConflict", "Replaced by: " . (string)$user->getId(), $existingUser);
        }

        $user->setWeChatToken($openid);
        $manager->persist($user);
        $this->writeLog("UserWeChatBind", null, $user);
        $manager->flush();

        return $this->response()->response(null);
    }

    /**
     * @Route("/user/weChatLogin", methods="POST")
     */
    public function weChatLogin(Request $request) {
        $token = $this->verifyWeChat($request->request->get("token"));
        if(!$token)
            return $this->response()->response(null, Response::HTTP_UNAUTHORIZED);

        $openid = $token["openid"];
        $sessionKey = $token["session_key"];


        $user = $this->getDoctrine()->getManager()->getRepository(User::class)->findOneBy(["weChatToken" => $openid]);

        if(is_null($user)) {
            return $this->response()->response(null, Response::HTTP_UNAUTHORIZED);
        } else {
            /** @var User $user */
            $session = $request->getSession();
            if (!$session)
                $session = new Session();
            $session->start();
            $session->set("user_token", $user->getToken());
            $session->set("wechat_session_key", $sessionKey);
            $this->writeLog("UserWeChatLogin", null, $user);
            return $this->response()->response(null);
        }
    }

    /**
     * @Route("/user/weChatLogout", methods="POST")
     */
    public function weChatLogout() {
        $this->denyAccessUnlessGranted(Permission::IS_LOGIN);
        $user = $this->getUser();
        $this->getUser()->setWeChatToken(null);
        $this->getDoctrine()->getManager()->persist($user);
        $this->getDoctrine()->getManager()->flush();
        return $this->response()->response(null);
    }

    /**
     * @Route("/user/privacy")
     */
    public function privacy(Request $request, TranslatorInterface $translator) {
        $this->denyAccessUnlessGranted(Permission::IS_LOGIN);
        if($request->isMethod("POST")) {
            $privacy = $request->request->getInt("privacy");
            if ($privacy > (PrivacyLevel::ONLY_ME) || $privacy < (PrivacyLevel::SAME_SCHOOL))
                return $this->response()->response($translator->trans("illegal-setting"), Response::HTTP_FORBIDDEN);
            $this->getUser()->setPrivacy($privacy);
            $antiSpider = $request->request->getBoolean("antiSpider");
            $this->getUser()->setAntiSpider($antiSpider);
            $this->getDoctrine()->getManager()->persist($this->getUser());
            $this->getDoctrine()->getManager()->flush();
        }
        return $this->response()->response(array("privacy" => $this->getUser()->getPrivacy(), "antiSpider" => $this->getUser()->isAntiSpider()));
    }

    /**
     * @Route("/user/page")
     */
    public function page(Request $request, TranslatorInterface $translator, CacheService $service) {
        $this->denyAccessUnlessGranted(Permission::IS_AUTHENTICATED);
        $id = $request->query->get("id");
        /** @var User $user */
        $user = $this->getDoctrine()->getManager()->getRepository(User::class)->find($id);
        if($service->antiSpiderUse($this->getUser(), $id) || $this->getUser()->isAdmin() || !$user->isAntiSpider()){
            /** @var Alumni $alumni */
            $alumni = $this->getDoctrine()->getManager()->getRepository(Alumni::class)->getLastAuth($user);
            if(is_null($alumni))
                return $this->response()->responseEntity(array(
                    "alumni" => null,
                    "info" => $user
                ));
            $sender = $this->getDoctrine()->getManager()->getRepository(Alumni::class)->getLastSuccessfulAuth($this->getUser());
            return $this->response()->responseEntity(array(
                "alumni" => $alumni->pbi($user->getPrivacy(), $sender),
                "info" => $user
            ));
        }else{
            return $this->response()->response($translator->trans("anti-spider-enabled"));
        }
    }


    /**
     * @Route("/user/wiki", methods="GET")
     */
    public function wiki()
    {
        $this->denyAccessUnlessGranted(Permission::IS_LOGIN);
        if (null === $this->getUser())
            return $this->response()->response(null, Response::HTTP_NO_CONTENT);
        return new JsonResponse(array("user" => $this->getUser()->getInfoArray()));
    }

    /**
     * @Route("/user/forum", methods="GET")
     */
    public function forum()
    {
        $this->denyAccessUnlessGranted(Permission::IS_LOGIN);
        if (null === $this->getUser())
            return $this->response()->response(null, Response::HTTP_NO_CONTENT);
        return new JsonResponse($this->getUser()->getInfoArray());
    }

    /**
     * @Route("/user/openid", methods="GET")
     */
    public function openid()
    {
        $this->denyAccessUnlessGranted(Permission::IS_LOGIN);
        if (null === $this->getUser())
            return $this->response()->response(null, Response::HTTP_NO_CONTENT);
        return new JsonResponse(array(
            "name" => $this->getUser()->getUsername(),
            "email" => $this->getUser()->getEmail()
        ));
    }


    /**
     * @Route("/user/code", methods="POST")
     */
    public function code(Request $request, NotificationService $service, TranslatorInterface $translator, CacheService $cacheService)
    {
        $type = $request->request->getInt("type");
        if($type == AliyunTemplateType::BIND)
            $this->denyAccessUnlessGranted(Permission::IS_LOGIN);
        if(!$cacheService->canSend($request->getClientIp(), $request->request->get("email") ?? $request->request->get("phone")))
            return $this->response()->response($translator->trans("rate-limited"), Response::HTTP_FORBIDDEN);
        $error = $service->code($request->request->get("email"), $request->request->get("phone"), $type);
        if($error)
            return $this->response()->response(null);
        else
            return $this->response()->response($translator->trans("already-used"), Response::HTTP_FORBIDDEN);
    }

    /**
     * @Route("/user/card")
     */
    public function card(Request $request, NotificationService $service) {

        if($request->isMethod("get")) {
            $this->denyAccessUnlessGranted(Permission::IS_LOGIN);
            if(!is_null($this->getUser()->getCard())) {
                return $this->response()->response([
                    "image" => $this->getUser()->getCard(),
                    "code" => Base32::encodeUpper(hash_hmac("sha256", $this->getUser()->getCard(), $_ENV["APP_SECRET"]))
                ]);
            }
        } else if($request->isMethod("post")) {
            if($request->request->get("key") != $_ENV["APP_SECRET"])
                throw $this->createAccessDeniedException();
            switch($request->request->get("action")) {
                case "fetch":
                    $users = $this->getDoctrine()->getManager()->getRepository(User::class)->findUserWithCard();
                    return $this->response()->responseRawEntity(array_values(array_map(function($user){
                        /** @var $user User */
                        return [
                            "id" => $user->getId(),
                            "username" => $user->getUsername(),
                            "chineseName" => $user->getValidAuth()->getChineseName(),
                            "englishName" => $user->getValidAuth()->getEnglishName(),
                            "class" => $this->getStudentClassName($user->getValidAuth()),
                            "code" => Base32::encodeUpper(hash_hmac("sha256", $user->getCard(), $_ENV["APP_SECRET"]))
                        ];
                    }, array_filter($users, function($user){/** @var $user User */return !is_null($user->getValidAuth());}))));
                case "query":
                    $name = $request->request->get("name");
                    $alumni = $this->getDoctrine()->getManager()->getRepository(Alumni::class)->findBy(["chineseName"=>$name, "status"=>Alumni::STATUS_PASSED], ["submitTime"=>"desc"]);
                    return $this->response()->response(array_map(function($alumni){
                        /** @var Alumni $alumni */
                        return [
                            "id" => $alumni->getId()->toString(),
                            "chineseName" => $alumni->getChineseName(),
                            "class" => $this->getStudentClassName($alumni),
                            "time" => $alumni->getSubmitTime()->format(DATE_ISO8601)
                        ];
                    }, $alumni));
                case "enable":
                    /** @var Alumni $alumni */
                    $alumni = $this->getDoctrine()->getManager()->getRepository(Alumni::class)->find($request->request->get("id"));
                    /** @var UploadedFile $image */
                    $id = $request->request->get("id");
                    //$name = bin2hex(random_bytes(8)).".".$image->guessExtension();
                    //$image->move("card/", $name);
                    $alumni->getUser()->setCard($id);
                    $this->getDoctrine()->getManager()->persist($alumni->getUser());
                    $this->getDoctrine()->getManager()->flush();
                    //$service->cardEnabled($alumni);
                    return $this->response()->response("成功。");
                case "upload":
                    /** @var UploadedFile $database */
                    $database = $request->files->get("database");
                    return $this->response()->response(null);
            }

        }
        return $this->response()->response(null);
    }

    private function getStudentClassName(Alumni $alumni) {
        $name = "";
        switch($alumni->getSeniorSchool()) {
            case 2:
                $name = "IB";
                break;
            case 3:
                $name = "AL";
                break;
            default:
                return "";
        }
        switch ($alumni->getSeniorRegistration()) {
            case 2021:
                $name = $name."高一";
                break;
            case 2020:
                $name = $name."高二";
                break;
            case 2019:
                $name = $name."高三";
                break;
        }
        $name = $name." ".(string)$alumni->getSeniorClass()."班";
        return $name;
    }

    private function verifyWeChat($code) {
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid=" . $_ENV["WECHAT_APP_ID"] . "&secret=" . $_ENV["WECHAT_APP_SECRET"] . "&js_code=$code&grant_type=authorization_code";
        $client = new Client();
        $result = $client->get($url);
        $data = json_decode($result->getBody(), true);
        if(isset($data["openid"]))
            return $data;
        else
            return false;
    }



    private function verifyUsername($username)
    {
        $re = '/^[A-Za-z0-9_\-\x{0800}-\x{9fa5}]{3,16}$/u';
        if (preg_match($re, $username) && !is_numeric($username[0])) {
            return true;
        } else {
            return false;
        }

    }

    private function verifyPassword(User $user, $password)
    {
        //Strength
        $re = '/^((?!.*[\s])(?=\S*?[a-zA-Z])(?=\S*?[0-9]).{6,})$/';
        if (!preg_match($re, $password))
            return false;
        //Email
        if (null !== $user->getEmail() && preg_match('/' . $user->getEmail() . '/', $password))
            return false;
        //Username
        if (preg_match('/' . $user->getUsername() . '/', $password))
            return false;

        return true;
    }

    private function getDefaultAvatar($username, $id)
    {
        file_put_contents($this->get('kernel')->getRootDir() . "/../public/avatar/" . strval($id) . ".png", fopen('http://identicon.relucks.org/' . md5($username) . '?size=200', 'r'));
    }
}

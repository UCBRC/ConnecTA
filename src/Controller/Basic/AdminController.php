<?php

namespace App\Controller\Basic;

use App\Controller\ConnecTAController;
use App\Entity\Log;
use App\Entity\School\Alumni;
use App\Entity\Preference;
use App\Entity\User\Device;
use App\Model\MailConstant;
use App\Model\Permission;
use App\Entity\User\User;
use App\Service\APNSService;
use App\Service\MailService;
use App\Type\DeviceType;
use Nexmo\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class AdminController extends ConnecTAController
{
    /**
     * @Route("/admin", name="admin")
     */
    public function index()
    {
        return new RedirectResponse("/admin/alumni/auth");
    }

    /**
     * @Route("/admin/preference")
     */
    public function getPreferenceList(Request $request)
    {
        $this->denyAccessUnlessGranted(Permission::IS_ADMIN);
        if ($request->request->has("identifier")) {
            $this->getDoctrine()->getManager()->getRepository(Preference::class)->set($request->request->get("identifier"), $request->request->get("content"));
            return $this->response()->response(null, 204);
        } else {
            return $this->response()->responseEntity($this->getDoctrine()->getManager()->getRepository(Preference::class)->findAll());
        }

    }

    /**
     * @Route("/admin/upload")
     */
    public function upload(Request $request){
        $this->denyAccessUnlessGranted(Permission::IS_LOGIN);
        if(!$this->getUser()->hasRole(Permission::IS_TEACHER) && !$this->getUser()->hasRole(Permission::IS_ADMIN))
            throw $this->createAccessDeniedException();
        $file = $request->files->get("file");
        /** @var UploadedFile $file */
        $name = md5(uniqid()).".".$file->guessExtension();
        $file->move("uploads", $name);


        return $this->response()->response("/uploads/".$name, 200);

    }

    /**
     * @Route("/admin/push", methods="POST")
     */
    public function push(Request $request, APNSService $service) {
        $this->denyAccessUnlessGranted(Permission::IS_ADMIN);
        $receiver = $request->request->get("receiver");
        if($receiver == "all") {
            $devices = $this->getDoctrine()->getManager()->getRepository(Device::class)->findByType(DeviceType::IOS);
        } else {
            $user = $this->getDoctrine()->getManager()->getRepository(User::class)->find($receiver);
            $devices = $this->getDoctrine()->getManager()->getRepository(Device::class)->findByUserAndType($user, DeviceType::IOS);
        }
        $service->bulk($devices,
            $request->request->get("title"),
            $request->request->get("subtitle"),
            $request->request->get("body"),
            $request->request->getInt("badge"),
            $request->request->get("imageUrl"),
            $request->request->get("link"));
        return $this->response()->response(null);
    }
    /**
     * @Route("/admin/mail", methods="POST")
     */
    public function mail(Request $request, MailService $mailService) {
        $this->denyAccessUnlessGranted(Permission::IS_ADMIN);
        $receiver = $request->request->get("receiver");
        if($receiver == "all") {
            $users = $this->getDoctrine()->getManager()->getRepository(User::class)->findAll();
            $receivers = array_map(function($user){
                return $user->getEmail();
            }, $users);
            $mailService->bulk(
                "announcement@betterrelations.org",
                "NFLS.IO/南外人",
                $receivers,
                "【NFLS.IO/南外人】".$request->request->get("title"),
                (new \Parsedown())->parse($request->request->get("content"))
                );
        } else {
            $user = $this->getDoctrine()->getManager()->getRepository(User::class)->find($receiver);
            $mailService->send(
                "announcement@betterrelations.org",
                "NFLS.IO/南外人",
                $user->getEmail(),
                "【NFLS.IO/南外人】".$request->request->get("title"),
                (new \Parsedown())->parse($request->request->get("content"))
            );
        }
        return $this->response()->response(null);

    }

    /**
     * @Route("/admin/user", methods="POST")
     */
    public function user(Request $request) {
        $this->denyAccessUnlessGranted(Permission::IS_ADMIN);
        $users = $this->getDoctrine()->getManager()->getRepository(User::class)->findByUsernameAndEmailAndPhoneAndEnabled(
            $request->request->get("username"),
            $request->request->get("email"),
            $request->request->get("phone"),
            $request->request->get("enabled"),
            $request->request->getBoolean("verified", true),
            $request->request->getBoolean("notNfls", true),
            $request->request->getInt("size"),
            ($request->request->getInt("page", 1) - 1) * $request->request->getInt("size"),
            $request->request->getBoolean("reverse", true)
        );
        $result = [];
        foreach ($users as $user) {
            /** @var User $user */
            $result[] = [
                "id" => $user->getId(),
                "username" => $user->getUsername(),
                "email" => $user->getEmail(),
                "phone" => $user->getPhone(),
                "enabled" => $user->isEnabled(),
                "joinTime" => $user->getJoinTime(),
                "readTime" => $user->getReadTime(),
                "chineseName" => $user->getChineseName()
            ];
        }
        return $this->response()->responseEntity($result);
    }

    /**
     * @Route("/admin/detail", methods="GET")
     */
    public function detail(Request $request) {
        $this->denyAccessUnlessGranted(Permission::IS_ADMIN);
        $id = $request->query->getInt("id", 1);
        $em = $this->getDoctrine()->getManager();
        /** @var User $user */
        $user = $em->getRepository(User::class)->find($id);
        $ios = $em->getRepository(Device::class)->findByUserAndType($user, DeviceType::IOS);
        $weChat = $em->getRepository(Device::class)->findByUserAndType($user, DeviceType::WE_CHAT);
        $log = $em->getRepository(Log::class)->findByUser($user);
        $tickets = $user->getAuthTickets();
        return $this->response()->responseEntity([
            "ios" => $ios,
            "weChat" => $weChat,
            "log" => $log,
            "tickets" => $tickets
        ]);
    }

    /**
     * @Route("/admin/disable", methods="POST")
     */
    public function disable(Request $request) {
        $this->denyAccessUnlessGranted(Permission::IS_ADMIN);
        $em = $this->getDoctrine()->getManager();
        $id = $request->query->getInt("id", 1);
        /** @var User $user */
        $user = $em->getRepository(User::class)->find($id);
        $user->setEnabled(!$user->isEnabled());
        $em->persist($user);
        $em->flush();
        return $this->response()->response(null);
    }

    /**
     * @Route("/admin/avatar", methods="POST")
     */
    public function avatar(Request $request) {
        $this->denyAccessUnlessGranted(Permission::IS_ADMIN);
        $em = $this->getDoctrine()->getManager();
        $id = $request->query->getInt("id", 1);
        /** @var User $user */
        $user = $em->getRepository(User::class)->find($id);
        $this->getDefaultAvatar($user->getUsername(), $user->getId());
        return $this->response()->response(null);
    }

    private function getDefaultAvatar($username, $id)
    {
        @unlink($this->get('kernel')->getRootDir() . "/../public/avatar/" . strval($id) . ".png");
        file_put_contents($this->get('kernel')->getRootDir() . "/../public/avatar/" . strval($id) . ".png", fopen('http://identicon.relucks.org/' . md5($username) . '?size=200', 'r'));
    }
}

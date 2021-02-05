<?php

namespace App\Controller\User;

use App\Controller\ConnecTAController;
use App\Entity\OAuth\Client;
use App\Entity\Preference;
use App\Entity\User\Device;
use App\Model\Permission;
use App\Type\DeviceStatusType;
use App\Type\DeviceType;
use Doctrine\DBAL\Event\SchemaColumnDefinitionEventArgs;
use function GuzzleHttp\default_ca_bundle;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DeviceController extends ConnecTAController
{
    /**
     * @Route("device/announcement/ios", methods="GET")
     */
    public function getHeader()
    {
        return $this->response()->response($this->setting()->get(Preference::IOS_ANNOUNCEMENT));
    }

    /**
     * @Route("device/version", methods="GET")
     */
    public function version(Request $request)
    {
        $repo = $this->getDoctrine()->getManager()->getRepository(Client::class);
        $client = $repo->getClientById($request->query->get("client_id"));
        return $this->response()->response($client->getVersion());
    }

    /**
     * @Route("device/pushRegister", methods="POST")
     */
    public function pushRegister(Request $request)
    {
        $this->denyAccessUnlessGranted(Permission::IS_LOGIN);
        $token = $request->request->get("token");
        if(is_null($token) || $token == "")
            throw new \InvalidArgumentException("Invalid token.");
        $em = $this->getDoctrine()->getManager();
        /** @var Device $device */
        $device = $em->getRepository(Device::class)->findOneByToken($token) ?? new Device();
        $device->setUser($this->getUser());
        $device->setToken($token);
        $device->setModel($request->request->get("model"));
        $device->setRemark($request->request->get("remark"));
        if($device->getStatus() === DeviceStatusType::INVALID)
            $device->setStatus(DeviceStatusType::NORMAL);
        $type = $request->request->get("type");
        switch($type) {
            case "ios":
                $device->setType(DeviceType::IOS);
                break;
            case "wechat":
                $device->setType(DeviceType::WE_CHAT);
                break;
            default:
                throw new \InvalidArgumentException("Invalid type.");
        }
        $device->setTime();
        $em->persist($device);
        $em->flush();
        return $this->response()->responseEntity($device);
    }

    /**
     * @Route("device/pushCallback", methods="POST")
     */
    public function pushCallback(Request $request) {
        $callbackToken = $request->request->get("callbackToken");
        $em = $this->getDoctrine()->getManager();
        /** @var Device $device */
        $device = $em->getRepository(Device::class)->findOneByCallbackToken($callbackToken);
        if(is_null($device))
            return $this->response()->response(null, Response::HTTP_FORBIDDEN);
        $type = $request->request->get("type");
        if($type === "server") {
            $status = $request->request->getInt("status", 0);
            switch ($status) {
                case -1:
                    $device->setStatus(DeviceStatusType::INVALID);
                    break;
                case 0:
                    $device->setStatus(DeviceStatusType::SERVER_ERROR);
                    break;
                case 1:
                    $device->setStatus(DeviceStatusType::SENT);
                    break;
            }
        }
        else if($type === "client")
            $device->setStatus(DeviceStatusType::ACKNOWLEDGED);
        else if($type === "view")
            $device->setStatus(DeviceStatusType::VIEWED);
        $em->persist($device);
        $em->flush();
        return $this->response()->response(null);
    }
}

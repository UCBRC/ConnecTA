<?php
/**
 * Created by PhpStorm.
 * User: hqy
 * Date: 2018/7/22
 * Time: 12:14 PM
 */

namespace App\Service;


use App\Entity\User\Device;
use App\Model\User;
use Doctrine\ORM\EntityManagerInterface;

class APNSService extends CeleryEnabledService
{

    /**
     * @param Device $device
     * @param null|string $title
     * @param null|string $subtitle
     * @param null|string $body
     * @param int|null $badge
     * @param null|string $image
     */
    public function push(Device $device, ?string $title, ?string $subtitle, ?string $body, ?int $badge, ?string $link, ?string $image) {
        try {
            $this->celery->client->PostTask("tasks.sendAPN",
                array(
                    $device->getToken(),
                    $device->getCallbackToken(),
                    $title,
                    $subtitle,
                    $body,
                    $badge,
                    [
                        "imageUrl"=> $image,
                        "link" => $link,
                        "callbackToken" => $device->getCallbackToken()
                    ]));
        } catch(\Exception $e) {
            return;
        }

    }

    /**
     * @param array $devices
     * @param null|string $title
     * @param null|string $subtitle
     * @param null|string $body
     * @param int|null $badge
     * @param null|string $link
     * @param null|string $image
     */
    public function bulk(array $devices, ?string $title, ?string $subtitle, ?string $body, ?int $badge, ?string $link, ?string $image) {
        foreach ($devices as $device) {
            $this->push($device, $title, $subtitle, $body, $badge, $link, $image);
        }
    }
}
<?php

namespace App\Model;

use App\Model\Normalizer\GalleryNormalizer;
use App\Model\Normalizer\GameNormalizer;
use App\Model\Normalizer\PhotoNormalizer;
use App\Model\Normalizer\UserNormalizer;
use App\Model\Normalizer\UuidNormalizer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class ApiResponse
{
    const REQUIRED_LOGIN = 1001;
    const REQUIRED_AUTHORIZED = 1002;
    private $serializer;
    private $rawSerializer;

    public function __construct($realname = false)
    {
        $encoder = new JsonEncoder();

        $defaultContext = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object) {
                return "_circular_";
            },
            AbstractNormalizer::CIRCULAR_REFERENCE_LIMIT => 0,
        ];
        $normalizer = new ObjectNormalizer(null, null, null, null, null, null, $defaultContext);
        $dateNormalizer = new DateTimeNormalizer();
        $photoNormalizer = new PhotoNormalizer();
        $userNormalizer = new UserNormalizer($realname);
        $uuidNormalizer = new UuidNormalizer();
        $gameNormalizer = new GameNormalizer();
        $galleryNormalizer = new GalleryNormalizer();
        $this->rawSerializer = new Serializer([$uuidNormalizer, $dateNormalizer, $normalizer, $photoNormalizer], [$encoder]);
        $this->serializer = new Serializer([$gameNormalizer, $uuidNormalizer, $dateNormalizer, $userNormalizer, $galleryNormalizer, $photoNormalizer, $normalizer], [$encoder]);
    }

    public function response($data, $code = 200)
    {
        $json = new JsonResponse();
        $array = array("code" => $code, "data" => $data);
        $json->setData($array);
        return $json;
    }

    public function responseRowEntity($data, $count, $code)
    {
        $data = json_decode($this->rawSerializer->serialize($data, "json"), TRUE);
        $json = new JsonResponse();
        //$array = array("code"=>$code,"data"=>$data);
        $json->setData(array("rows" => $data, "total" => $count));
        return $json;
    }

    function responseEntity($data, $code = Response::HTTP_OK)
    {
        $data = json_decode($this->serializer->serialize($data, "json"), TRUE);
        $json = new JsonResponse();
        $array = array("code" => $code, "data" => $data);
        $json->setData($array);
        return $json;
    }

    function responseRawEntity($data, $code = Response::HTTP_OK)
    {
        $data = json_decode($this->rawSerializer->serialize($data, "json"), TRUE);
        $json = new JsonResponse();
        $array = array("code" => $code, "data" => $data);
        $json->setData($array);
        return $json;
    }

    function responseJsonEntity($entity, $code = Response::HTTP_OK) {
        $serializer = new Serializer([new JsonSerializableNormalizer()],[new JsonEncoder()]);
        $data = json_decode($serializer->serialize($entity, "json"), TRUE);
        $json = new JsonResponse();
        $array = array("code" => $code, "data" => $data);
        $json->setData($array);
        return $json;
    }

}
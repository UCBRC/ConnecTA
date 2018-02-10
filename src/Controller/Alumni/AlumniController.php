<?php

namespace App\Controller\Alumni;

use App\Controller\AbstractController;
use App\Entity\Alumni;
use App\Model\Normalizer\UuidNormalizer;
use App\Model\Permission;
use Doctrine\Common\Collections\ArrayCollection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AlumniController extends AbstractController
{

    /**
     * @Route("alumni/form",methods="GET")
     */
    public function getForm(Request $request)
    {
        $this->denyAccessUnlessGranted(Permission::IS_LOGIN);
        return $this->response->response(json_decode(file_get_contents($this->get('kernel')->getRootDir() . "/Files/Form.json")));
    }

    /**
     * @Route("alumni/countries", methods="GET")
     */
    public function getCountries()
    {
        $this->denyAccessUnlessGranted(Permission::IS_LOGIN);
        return $this->response->response(json_decode(file_get_contents($this->get('kernel')->getRootDir() . "/Files/Countries.json")));
    }

    /**
     * @Route("alumni/current", methods="GET")
     */
    public function getCurrentStatus()
    {
        $this->denyAccessUnlessGranted(Permission::IS_LOGIN);
        $repo = $this->getDoctrine()->getManager()->getRepository(Alumni::class);
        $auths = $repo->getLastSuccessfulAuth($this->getUser());
        return $this->response->responseEntity($auths);
    }

    /**
     * @Route("alumni/info", methods="GET")
     */
    public function getInfo()
    {
        $this->denyAccessUnlessGranted(Permission::IS_LOGIN);
        $repo = $this->getDoctrine()->getManager()->getRepository(Alumni::class);
        return $this->response->responseEntity($repo->getAuths($this->getUser()));
    }

    /**
     * @Route("alumni/new", methods="POST")
     */
    public function newForm(Request $request)
    {
        $this->denyAccessUnlessGranted(Permission::IS_LOGIN);
        $this->verfityCsrfToken($request->request->get("_token"),AbstractController::CSRF_ALUMNI_FORM);
        $repo = $this->getDoctrine()->getManager()->getRepository(Alumni::class);
        if ((count($repo->findBy(["user" => $this->getUser(), "status" => Alumni::STATUS_NOT_SUBMITTED])) + count($repo->findBy(["user" => $this->getUser(), "status" => Alumni::STATUS_SUBMITTED]))) > 0) {
            return $this->response->response("alumni.already.new", 403);
        }
        $alumni = new Alumni();
        $alumni->setUser($this->getUser());
        $em = $this->getDoctrine()->getManager();
        $em->persist($alumni);
        $em->flush();
        return $this->response->response(null);
    }

    /**
     * @Route("alumni/detail", methods="GET")
     */
    public function getFormDetail(Request $request)
    {
        $this->denyAccessUnlessGranted(Permission::IS_LOGIN);
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository(Alumni::class);
        $id = $request->query->get("id");
        if($this->getUser()->hasRole(Permission::IS_ADMIN))
            $form = $repo->findOneBy(["id" => $id]);
        else
            $form = $repo->findOneBy(["id" => $id, "user" => $this->getUser()]);
        return $this->response->responseEntity($form);
    }

    /**
     * @Route("alumni/save",methods="POST")
     */
    public function saveForm(Request $request)
    {
        $this->denyAccessUnlessGranted(Permission::IS_LOGIN);
        $id = $request->query->get("id");
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository(Alumni::class);
        /**
         * @var Alumni $form
         */
        if($this->getUser()->hasRole(Permission::IS_ADMIN))
            $form = $repo->findOneBy(["id" => $id]);
        else
            $form = $repo->findOneBy(["id" => $id, "user" => $this->getUser()]);

        $form->setUserStatus($request->request->get("userStatus"));
        $form->setChineseName($request->request->get("chineseName"));
        $form->setEnglishName($request->request->get("englishName"));
        $birthday = \DateTime::createFromFormat("Y-m-d\TH:i:s\.000\Z", $request->request->get("birthday"));
        $birthday->add(new \DateInterval("PT11H"));
        $form->setBirthday($birthday);
        $form->setGender($request->request->get("gender"));
        $form->setJuniorSchool($request->request->get("juniorSchool"));
        $form->setJuniorRegistration($request->request->get("juniorRegistration"));
        $form->setJuniorClass($request->request->get("juniorClass"));
        $form->setSeniorSchool($request->request->get("seniorSchool"));
        $form->setSeniorRegistration($request->request->get("seniorRegistration"));
        $form->setSeniorClass($request->request->get("seniorClass"));
        $form->setUniversity($request->request->get("university"));
        $form->setMajor($request->request->get("major"));
        $form->setWorkInfo($request->request->get("workInfo"));
        $form->setPersonalInfo($request->request->get("personalInfo"));
        $form->setCountry($request->request->get("country"));
        $form->setLocation($request->request->get("location"));
        $form->setOnlineContact($request->request->get("onlineContact"));
        $form->setRemark($request->request->get("remark"));
        $em->persist($form);
        $em->flush();

        return $this->response->response(null);
    }

    /**
     * @Route("alumni/submit", methods="POST")
     */
    public function submitForm(Request $request, ValidatorInterface $validator)
    {
        $this->denyAccessUnlessGranted(Permission::IS_LOGIN);
        $id = $request->query->get("id");
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository(Alumni::class);
        /**
         * @var Alumni $form
         */
        $form = $repo->findOneBy(["id" => $id, "status" => Alumni::STATUS_NOT_SUBMITTED, "user" => $this->getUser()]);
        $errors = $validator->validate($form);
        $error_ids = [];
        foreach ($errors as $error) {
            array_push($error_ids, $error->getMessage());
        }
        $normalizer = new ObjectNormalizer();
        $normalizer->setCircularReferenceLimit(2);
        $normalizer->setCircularReferenceHandler(function ($object) {
            return null;
        });
        $serializer = new Serializer([new UuidNormalizer(), $normalizer], [new JsonEncoder()]);
        $formArray = json_decode($serializer->serialize($form, "json"), true);
        $content = json_decode(file_get_contents($this->get('kernel')->getRootDir() . "/Files/Form.json"), true);
        foreach ($content as $item) {
            if ($item["type"] == "select" && null !== $formArray[$item["key"]]) {
                $values = $item["values"][(int)$formArray[$item["key"]]];
                if (isset($values["hidden"])) {
                    foreach ($values["hidden"] as $hidden) {
                        foreach ($error_ids as $key => $error) {
                            if (strpos($error, $hidden) !== false) {
                                unset($error_ids[$key]);
                            }
                        }
                    }
                }

            }
        }
        if (count($error_ids) > 0)
            return $this->response->responseEntity($error_ids, 400);

        $form->setStatus(1);
        $form->setSubmitTime(new \DateTime());
        $em->persist($form);
        $em->flush();
        return $this->response->responseEntity(null, 200);
    }

    /**
     * @Route("alumni/cancel", methods="POST")
     */
    public function cancelForm(Request $request)
    {
        $this->denyAccessUnlessGranted(Permission::IS_LOGIN);
        $id = $request->query->get("id");
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository(Alumni::class);
        /**
         * @var Alumni $form
         */
        $form = $repo->findOneBy(["id" => $id, "status" => Alumni::STATUS_SUBMITTED, "user" => $this->getUser()]);
        if (!$form)
            return $this->response->response(null, 403);
        $form->setStatus(Alumni::STATUS_CANCELED);
        $em->persist($form);
        $em->flush();
        return $this->response->responseEntity(null, 200);
    }

    /**
     * @Route("admin/alumni/auth", methods="GET")
     */
    public function renderAdmin()
    {
        $this->denyAccessUnlessGranted(Permission::IS_ADMIN);
        return $this->render("admin/alumni/auth.html.twig");
    }

    /**
     * @Route("admin/alumni/auth/list", methods="GET")
     */
    public function getList()
    {
        $this->denyAccessUnlessGranted(Permission::IS_ADMIN);
        $repo = $this->getDoctrine()->getManager()->getRepository(Alumni::class);
        return $this->response->responseEntity($repo->getToReview());
    }

    /**
     * @Route("admin/alumni/auth/update", methods="POST")
     */
    public function update(Request $request)
    {
        $this->denyAccessUnlessGranted(Permission::IS_ADMIN);
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository(Alumni::class);
        $id = $request->request->get("id");
        $action = $request->request->get("action");
        $time = \DateTime::createFromFormat("Y-m-d\TH:i:s\.000\Z", $request->request->get("time"));
        if($time)
            $time->add(new \DateInterval("PT11H"));
        else
            $time = null;
        $ticket = $repo->findOneBy(["id"=>$id]);
        switch($action){
            case "reject":
                $ticket->setStatus(Alumni::STATUS_REJECTED);
                break;
            case "accept":
                $ticket->setStatus(Alumni::STATUS_PASSED);
                $ticket->setExpireAt($time);
                break;
            default:
                break;
        }
        $em->persist($ticket);
        $em->flush();
        return $this->response->response(null,200);
    }


}

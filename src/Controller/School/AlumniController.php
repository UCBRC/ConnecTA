<?php

namespace App\Controller\School;

use App\Controller\ConnecTAController;
use App\Entity\School\Alumni;
use App\Entity\School\Ticket;
use App\Model\Normalizer\UuidNormalizer;
use App\Model\Permission;
use App\Service\NotificationService;
use Proxies\__CG__\App\Entity\Preference;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AlumniController extends ConnecTAController
{

    /**
     * @Route("alumni/form",methods="GET")
     */
    public function getForm(Request $request)
    {
        $this->denyAccessUnlessGranted(Permission::IS_LOGIN);
        return $this->response()->response(json_decode(file_get_contents($this->get('kernel')->getRootDir() . "/Files/Form.json")));
    }

    /**
     * @Route("alumni/header", methods="GET")
     */
    public function getHeader()
    {
        return $this->response()->response($this->setting()->get(Preference::ALUMNI_HEADER));
    }

    /**
     * @Route("alumni/countries", methods="GET")
     */
    public function getCountries()
    {
        $this->denyAccessUnlessGranted(Permission::IS_LOGIN);
        return $this->response()->response(json_decode(file_get_contents($this->get('kernel')->getRootDir() . "/Files/Countries.json")));
    }

    /**
     * @Route("alumni/current", methods="GET")
     */
    public function getCurrentStatus()
    {
        $this->denyAccessUnlessGranted(Permission::IS_LOGIN);
        $repo = $this->getDoctrine()->getManager()->getRepository(Alumni::class);
        $auths = $repo->getLastSuccessfulAuth($this->getUser());
        return $this->response()->responseEntity($auths);
    }

    /**
     * @Route("alumni/info", methods="GET")
     */
    public function getInfo()
    {
        $this->denyAccessUnlessGranted(Permission::IS_LOGIN);
        $repo = $this->getDoctrine()->getManager()->getRepository(Alumni::class);
        return $this->response()->responseEntity($repo->getAuths($this->getUser()));
    }

    /**
     * @Route("alumni/new", methods="POST")
     */
    public function newForm(Request $request, TranslatorInterface $translator)
    {
        $this->denyAccessUnlessGranted(Permission::IS_LOGIN);
        $repo = $this->getDoctrine()->getManager()->getRepository(Alumni::class);
        if ((count($repo->findBy(["user" => $this->getUser(), "status" => Alumni::STATUS_NOT_SUBMITTED])) + count($repo->findBy(["user" => $this->getUser(), "status" => Alumni::STATUS_SUBMITTED]))) > 0) {
            return $this->response()->response($translator->trans("unfinished-form"), Response::HTTP_FORBIDDEN);
        }
        $alumni = new Alumni();
        $alumni->setUser($this->getUser());
        $em = $this->getDoctrine()->getManager();
        $em->persist($alumni);
        $em->flush();
        return $this->response()->response(null);
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
        if ($this->getUser()->hasRole(Permission::IS_ADMIN))
            $form = $repo->findOneBy(["id" => $id]);
        else
            $form = $repo->findOneBy(["id" => $id, "user" => $this->getUser()]);
        return $this->response()->responseEntity($form);
    }

    /**
     * @Route("alumni/duplicate",methods="POST")
     */
    public function duplicate(Request $request, TranslatorInterface $translator)
    {
        $this->denyAccessUnlessGranted(Permission::IS_LOGIN);
        $repo = $this->getDoctrine()->getManager()->getRepository(Alumni::class);
        if ((count($repo->findBy(["user" => $this->getUser(), "status" => Alumni::STATUS_NOT_SUBMITTED])) + count($repo->findBy(["user" => $this->getUser(), "status" => Alumni::STATUS_SUBMITTED]))) > 0) {
            return $this->response()->response($translator->trans("unfinished-form"), Response::HTTP_FORBIDDEN);
        }
        $id = $request->query->get("id");
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository(Alumni::class);
        /**
         * @var Alumni $form
         */
        if ($this->getUser()->hasRole(Permission::IS_ADMIN))
            $form = $repo->findOneBy(["id" => $id]);
        else
            $form = $repo->findOneBy(["id" => $id, "user" => $this->getUser()]);

        $f = clone $form;
        $em->persist($f);
        $em->flush();
        return $this->response()->response($f->getId());
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
        if ($this->getUser()->hasRole(Permission::IS_ADMIN))
            $form = $repo->findOneBy(["id" => $id]);
        else
            $form = $repo->findOneBy(["id" => $id, "user" => $this->getUser()]);

        $form->setUserStatus($request->request->get("userStatus"));
        $form->setChineseName($request->request->get("chineseName"));
        $form->setEnglishName($request->request->get("englishName"));
        $birthday = \DateTime::createFromFormat("Y/m/d", substr($request->request->get("birthday"), 0, 10));
        if(!$birthday)
            $birthday = null;
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

        return $this->response()->response(null);
    }

    /**
     * @Route("alumni/submit", methods="POST")
     */
    public function submitForm(Request $request, ValidatorInterface $validator, TranslatorInterface $translator, NotificationService $service)
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
        $normalizer->setCircularReferenceLimit(1);
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
        $error_ids = array_map(function($val)use($translator){
            return $translator->trans($val);
        },$error_ids);
        if (count($error_ids) > 0)
            return $this->response()->responseEntity($error_ids, 400);

        $form->setStatus(1);
        $form->setSubmitTime(new \DateTime());
        $service->newVerification($form);
        $em->persist($form);
        $em->flush();
        return $this->response()->responseEntity(null, 200);
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
            return $this->response()->response(null, 403);
        $form->setStatus(Alumni::STATUS_CANCELED);
        $em->persist($form);
        $em->flush();
        return $this->response()->responseEntity(null, 200);
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
        return $this->response()->responseEntity($repo->getToReview());
    }

    /**
     * @Route("admin/alumni/auth/update", methods="POST")
     */
    public function update(Request $request, NotificationService $service)
    {
        $this->denyAccessUnlessGranted(Permission::IS_ADMIN);
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository(Alumni::class);
        $id = $request->request->get("id");
        $action = $request->request->get("action");
        $time = \DateTime::createFromFormat("Y-m-d\TH:i:s\.000\Z", $request->request->get("time"));
        if ($time)
            $time->add(new \DateInterval("PT11H"));
        else
            $time = null;
        /** @var Alumni $ticket */
        $ticket = $repo->findOneBy(["id" => $id]);
        switch ($action) {
            case "reject":
                $ticket->setStatus(Alumni::STATUS_REJECTED);
                $service->realnameFailed($ticket->getUser());
                break;
            case "accept":
                if($ticket->getUserStatus() == 5)
                    $ticket->setStatus(Alumni::STATUS_NOT_NFLS);
                else
                    $ticket->setStatus(Alumni::STATUS_PASSED);
                $ticket->setExpireAt($time);
                $service->realnamePassed($ticket->getUser(),$ticket);
                break;
            default:
                break;
        }
        $em->persist($ticket);
        $em->flush();
        return $this->response()->response(null, 200);
    }


}

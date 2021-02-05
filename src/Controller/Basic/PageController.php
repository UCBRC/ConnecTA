<?php

namespace App\Controller\Basic;

use App\Controller\ConnecTAController;
use App\Entity\Preference;
use DeviceDetector\DeviceDetector;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class PageController extends ConnecTAController
{
    /**
     * @Route("/",name="index",methods="GET")
     */
    public function index(Request $request)
    {
        return $this->render("index.html.twig");
        /*
        if ($_ENV["APP_ENV"] == "dev") {

        } else {
            if ($request->cookies->get("continue") == "true")
                return $this->render("index.html.twig");
            $dd = new DeviceDetector($request->headers->get("user-agent"));
            $dd->parse();
            if ($dd->getClient("name") == "Chrome" && version_compare($dd->getClient("version"), "49.0", ">="))
                return $this->render("index.html.twig");
            if ($dd->getClient("name") == "Chrome Mobile" && version_compare($dd->getClient("version"), "49.0", ">="))
                return $this->render("index.html.twig");

            if ($dd->isBot())
                return $this->render("index.html.twig");
            if ($dd->getOs("name") == "iOS")
                return $this->render("index.html.twig");
        }
        return new RedirectResponse("https://outdated.nfls.io");
        */

    }

    /**
     * @Route("/message/announcement", methods="GET")
     */
    public function getAnnouncement(Request $request)
    {
        return $this->response()->response($this->getDoctrine()->getRepository(Preference::class)->get(Preference::DASHBOARD_ANNOUNCEMENT));
    }

    /**
     * @Route("/index/photos", methods="GET")
     */
    public function getPhotos(Request $request)
    {
        return $this->response()->response($this->getDoctrine()->getRepository(Preference::class)->get(Preference::PHOTOS));
    }
}

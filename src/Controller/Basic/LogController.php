<?php

namespace App\Controller\Basic;

use App\Controller\ConnecTAController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

class LogController extends ConnecTAController
{
    /**
     * @Route("/log", name="log")
     */
    public function index()
    {
        // replace this line with your own code!
        return $this->render('@Maker/demoPage.html.twig', ['path' => str_replace($this->getParameter('kernel.project_dir') . '/', '', __FILE__)]);
    }
}

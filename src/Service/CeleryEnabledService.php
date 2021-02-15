<?php
/**
 * Created by PhpStorm.
 * User: hqy
 * Date: 2018/7/22
 * Time: 2:06 PM
 */

namespace App\Service;


use Doctrine\ORM\EntityManagerInterface;

class CeleryEnabledService
{
    protected $celery;

    protected $objectManager;

    public function __construct(CeleryService $celeryService, EntityManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
        $this->celery = $celeryService;
    }
}
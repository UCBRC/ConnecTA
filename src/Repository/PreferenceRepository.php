<?php

namespace App\Repository;

use App\Entity\Preference;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PreferenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Preference::class);
    }

    public function get($identifier)
    {
        $preference = $this->findOneBy(["identifier" => $identifier]);
        if ($preference->getType() == "json")
            return json_decode($preference->getContent());
        else
            return $preference->getContent();

    }

    public function set($identifier, $content)
    {
        $preference = $this->findOneBy(["identifier" => $identifier]);
        if ($preference->getType() == "json")
            $content = json_encode($content);
        $preference->setContent($content);
        $this->getEntityManager()->persist($preference);
        $this->getEntityManager()->flush();
    }
}

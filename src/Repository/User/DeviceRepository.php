<?php

namespace App\Repository\User;

use App\Entity\User\Device;
use App\Entity\User\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DeviceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Device::class);
    }

    public function findOneByToken(string $token) {
        return $this->findOneBy(["token" => $token]);
    }

    public function findByUserAndType(User $user, int $type) {
        return $this->findBy(["user" => $user, "type" => $type]);
    }

    public function findValidByUserAndType(User $user, int $type) {
        return $this->createQueryBuilder("u")
            ->where("u.user = :user")
            ->andWhere("u.type = :type")
            ->andWhere("u.status > :status")
            ->setParameter("user", $user)
            ->setParameter("type", $type)
            ->setParameter("status", -1)
            ->getQuery()
            ->getResult();
    }

    public function findByType(int $type) {
        return $this->findBy(["type" => $type]);
    }

    public function findOneByCallbackToken(string $token) {
        return $this->findOneBy(["callbackToken" => $token]);
    }
}

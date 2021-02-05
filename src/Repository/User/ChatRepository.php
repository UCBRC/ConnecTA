<?php

namespace App\Repository\User;

use App\Entity\User\Chat;
use App\Entity\User\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;

class ChatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Chat::class);
    }

    /**
     * @param User $user
     * @param int $page
     * @return ArrayCollection
     */
    public function list(User $user, int $page){
        return $this->createQueryBuilder("u")
            ->where("u.sender = :user")
            ->orWhere("u.receiver = :user")
            ->orderBy("u.time", "desc")
            ->setParameter("user", $user)
            ->setFirstResult(($page - 1)*20)
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();
    }

    public function getInboxCount(User $user)
    {
        return intval($this->createQueryBuilder("u")
            ->where("u.receiver = :receiver")
            ->setParameter("receiver", $user)
            ->andWhere("u.time > :time")
            ->setParameter("time", $user->getReadTime())
            ->select("count(u)")
            ->getQuery()
            ->getSingleScalarResult());
    }
}

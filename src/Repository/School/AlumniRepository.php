<?php

namespace App\Repository\School;

use App\Entity\School\Alumni;
use Doctrine\Bundle\DoctrineBundle\Command\Proxy\QueryRegionCacheDoctrineCommand;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;

class AlumniRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Alumni::class);
    }

    public function getAuths($user)
    {
        return $this->createQueryBuilder("u")
            ->where("u.user = :user")
            ->setParameter("user", $user)
            ->orderBy("u.submitTime", "DESC")
            ->getQuery()
            ->getResult();
    }

    public function getLastSuccessfulAuth($user)
    {
        $tickets = $this->createQueryBuilder("u")
            ->where("u.user = :user")
            ->setParameter("user", $user)
            ->andWhere("u.status = :status")
            ->setParameter("status", Alumni::STATUS_PASSED)
            ->orderBy("u.submitTime", "DESC")
            ->getQuery()
            ->getResult();
        $result = array_filter($tickets, function ($val) {
            return $val->getStatus() == Alumni::STATUS_PASSED;
        });
        if (isset($result[0]))
            return $result[0];
        else
            return null;
    }

    public function getLastAuth($user) {
        $tickets = $this->createQueryBuilder("u")
            ->where("u.user = :user")
            ->setParameter("user", $user)
            ->andWhere("u.status = :status")
            ->setParameter("status", Alumni::STATUS_PASSED)
            ->orderBy("u.submitTime", "DESC")
            ->getQuery()
            ->getResult();
        return $tickets[0];

    }

    public function getToReview()
    {
        return $this->createQueryBuilder("u")
            ->where("u.status = :submitted")
            ->setParameter("submitted", Alumni::STATUS_SUBMITTED)
            ->orWhere("u.status = :reviewing")
            ->setParameter("reviewing", Alumni::STATUS_REVIEWING)
            ->orderBy("u.submitTime", "ASC")
            ->getQuery()
            ->getResult();
    }

    public function directoryQuery(Request $request)
    {
        $query = $this->createQueryBuilder("u")
            ->where("u.status = :status")
            ->setParameter("status", 5);
        $query = $this->addQuery("juniorSchool", $request, $query);
        $query = $this->addQuery("seniorSchool", $request, $query);
        $query = $this->addQuery("juniorRegistration", $request, $query);
        $query = $this->addQuery("seniorRegistration", $request, $query);
        $query = $this->addLikeQuery("university", $request, $query);
        $query = $this->addLikeQuery("major", $request, $query);
        $query = $this->addLikeQuery("workInfo", $request, $query);
        return $query->setMaxResults(30)->getQuery()->getResult();
    }

    public function search(string $name, ?string $registration, ?string $class)
    {
        $qb = $this->createQueryBuilder("u");
        $query = $qb
            ->where("u.status = :status")
            ->setParameter("status", 5);

        $query = $query->andWhere($qb->expr()->orX(
            $qb->expr()->like("u.university", ":name"),
            $qb->expr()->like("u.major", ":name"),
            $qb->expr()->like("u.workInfo", ":name"),
            $qb->expr()->like("u.personalInfo", ":name"),
            $qb->expr()->like("u.chineseName", ":name"),
            $qb->expr()->like("u.englishName", ":name")
        ))->setParameter("name", "%" . $name . "%");

        if(!is_null($registration))
            $query = $query->andWhere($qb->expr()->orX(
                $qb->expr()->eq("u.juniorRegistration", ":registration"),
                $qb->expr()->eq("u.seniorRegistration", ":registration")
            ))->setParameter("registration", $registration);

        if(!is_null($class))
            $query = $query->andWhere($qb->expr()->orX(
                $qb->expr()->eq("u.juniorClass", ":class"),
                $qb->expr()->eq("u.juniorClass", ":class")
            ))->setParameter("class", $class);

        return $query
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();
    }

    public function like($key, $name, QueryBuilder $query)
    {
        return $query->orWhere("u." . $key . " like :" . $key)
            ->setParameter($key, "%" . $name . "%");
    }

    private function addQuery($key, Request $request, QueryBuilder $query)
    {
        if ($request->request->has($key) && $request->request->get($key) != "") {
            return $query->andWhere("u." . $key . " = :" . $key)
                ->setParameter($key, $request->request->get($key));
        } else {
            return $query;
        }
    }

    private function addLikeQuery($key, Request $request, QueryBuilder $query)
    {
        if ($request->request->has($key) && $request->request->get($key) != "") {
            return $query->andWhere("u." . $key . " like :" . $key)
                ->setParameter($key, "%" . $request->request->get($key) . "%");
        } else {
            return $query;
        }
    }
}

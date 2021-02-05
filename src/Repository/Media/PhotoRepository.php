<?php

namespace App\Repository\Media;

use App\Entity\Media\Gallery;
use App\Entity\Media\Photo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PhotoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Photo::class);
    }

    /**
     * @param $page int
     * @param $pagesize int
     * @param $showAll boolean
     * @param $belongTo Gallery
     * @param $startTime \DateTime
     * @param $endTime \DateTime
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getList($page, $pagesize, $showAll = true, $belongTo = null, $startTime = null, $endTime = null)
    {
        $query = $this->createQueryBuilder("u");
        if (!$showAll) {
            if (@is_null($belongTo))
                $query = $query->where("u.gallery is NULL");
            else
                $query = $query->where("u.gallery = :gallery")->setParameter("gallery", $belongTo);
        }
        return $query->setMaxResults($pagesize)->setFirstResult(($pagesize) * ($page - 1))->getQuery()->getResult();
    }

    public function getListCount($page, $pagesize, $showAll = true, $belongTo = null, $startTime = null, $endTime = null)
    {
        $query = $this->createQueryBuilder("u");
        if (!$showAll) {
            if (@is_null($belongTo))
                $query = $query->where("u.gallery is NULL");
            else
                $query = $query->where("u.gallery = :gallery")->setParameter("gallery", $belongTo);
        }
        return intval($query->select("count(u)")->getQuery()->getSingleScalarResult());
    }

    public function getPhoto($id)
    {
        return $this->findOneBy(["id" => $id]);
    }

    /**
     * @param $file
     * @return Photo|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getPhotoByFile($file){
        return $this->createQueryBuilder("u")
            ->where("u.thumb = :file")
            ->orWhere("u.hd = :file")
            ->orWhere("u.origin = :file")
            ->setParameter("file", $file)
            ->getQuery()
            ->getOneOrNullResult();
    }
}


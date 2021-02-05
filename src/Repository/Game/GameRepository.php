<?php

namespace App\Repository\Game;

use App\Entity\Game\Game;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class GameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }


    public function listAll()
    {
        //return $this->findAll();
    }

    public function findGame($id)
    {
        return $this->findOneBy(["id" => $id]);
    }


}

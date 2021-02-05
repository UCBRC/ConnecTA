<?php

namespace App\Repository\OAuth;

use App\Entity\OAuth\AuthCode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use Doctrine\Persistence\ManagerRegistry;

class AuthCodeRepository extends ServiceEntityRepository implements AuthCodeRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuthCode::class);
    }

    public function getNewAuthCode()
    {
        return new AuthCode();
    }

    public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity)
    {
        $em = $this->getEntityManager();
        $em->persist($authCodeEntity);
        $em->flush();
    }

    public function revokeAuthCode($codeId)
    {
        $em = $this->getEntityManager();
        $em->remove($this->findOneBy(["token" => $codeId]));
        $em->flush();
    }

    public function isAuthCodeRevoked($codeId)
    {
        return ($this->findOneBy(["token" => $codeId]) === null);
    }


}

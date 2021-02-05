<?php

namespace App\Repository\OAuth;

use App\Entity\OAuth\RefreshToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use Doctrine\Persistence\ManagerRegistry;

class RefreshTokenRepository extends ServiceEntityRepository implements RefreshTokenRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RefreshToken::class);
    }

    public function getNewRefreshToken()
    {
        $token = new RefreshToken();
        return $token;
    }

    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity)
    {

        $em = $this->getEntityManager();
        $em->persist($refreshTokenEntity);
        $em->flush();
    }

    public function revokeRefreshToken($tokenId)
    {
        $em = $this->getEntityManager();
        $em->remove($this->findOneBy(["token" => $tokenId]));
        $em->flush();
    }

    public function isRefreshTokenRevoked($tokenId)
    {
        $token = $this->findOneBy(["token" => $tokenId]);
        if (@null === $token)
            return true;
        else
            return false;
    }


}

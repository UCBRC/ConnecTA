<?php

namespace App\Repository\OAuth;

use App\Entity\OAuth\AccessToken;
use App\Entity\OAuth\RefreshToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use Doctrine\Persistence\ManagerRegistry;

class AccessTokenRepository extends ServiceEntityRepository implements AccessTokenRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccessToken::class);
    }

    public function getTokenById($tokenId)
    {
        return $this->findOneBy(["token" => $tokenId]);
    }


    public function getNewToken(ClientEntityInterface $clientEntity, array $scopes, $userIdentifier = null)
    {
        $accessToken = new AccessToken();
        $accessToken->setClient($clientEntity);
        foreach ($scopes as $scope) {
            $accessToken->addScope($scope);
        }
        $accessToken->setUserIdentifier($userIdentifier);
        return $accessToken;

    }

    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity)
    {
        $this->getEntityManager()->persist($accessTokenEntity);
        //var_dump($accessTokenEntity);
        $this->getEntityManager()->flush();
    }

    public function revokeAccessToken($tokenId)
    {
        /** @var AccessToken $token */
        $token = $this->findOneBy(["token" => $tokenId]);
        /** @var RefreshToken $refreshToken */
        $refreshToken = $this->getEntityManager()->getRepository(RefreshToken::class)->findOneBy(["accessToken"=>$token]);
        if (null !== $token) {
            $refreshToken->setAccessToken(null);
            $this->getEntityManager()->persist($refreshToken);
            $this->getEntityManager()->remove($token);
            $this->getEntityManager()->flush();
        }

    }

    public function isAccessTokenRevoked($tokenId)
    {
        $token = $this->findOneBy(["token" => $tokenId]);
        if (@null === $token)
            return true;
        else
            return false;
    }


}

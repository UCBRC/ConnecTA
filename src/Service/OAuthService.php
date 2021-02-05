<?php

namespace App\Service;

use App\Entity\OAuth\AccessToken;
use App\Entity\OAuth\AuthCode;
use App\Entity\OAuth\Client;
use App\Entity\OAuth\RefreshToken;
use App\Entity\OAuth\Scope;
use App\Entity\User\User;
use DateInterval;
use Doctrine\ORM\EntityManagerInterface;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Grant\AuthCodeGrant;
use League\OAuth2\Server\Grant\ImplicitGrant;
use League\OAuth2\Server\Grant\PasswordGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Server\ResourceServer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class OAuthService extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    private function getPrivateKey(){
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return "C:/Users/huqin/private.key";
        } else {
            return "/etc/cert/oauth.key";
        }
    }

    private function getPublicKey(){
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return "C:/Users/huqin/public.key";
        } else {
            return "/etc/cert/oauth.pub";
        }
    }

    public function getServer()
    {
        $em = $this->em;
        $clientRepo = $em->getRepository(Client::class);
        $accessTokenRepo = $em->getRepository(AccessToken::class);
        $scopeRepo = $em->getRepository(Scope::class);
        $authCodeRepo = $em->getRepository(AuthCode::class);
        $refreshTokenRepo = $em->getRepository(RefreshToken::class);
        $userRepo = $em->getRepository(User::class);


        $refreshTokenExpiry = new DateInterval("P6M");
        $authCodeExpiry = new DateInterval("PT15M");
        $accessTokenExpiry = new DateInterval("P7D");

        $authCodeGrant = new AuthCodeGrant($authCodeRepo, $refreshTokenRepo, $authCodeExpiry);
        $authCodeGrant->setRefreshTokenTTL($refreshTokenExpiry);

        $passwordGrant = new PasswordGrant($userRepo, $refreshTokenRepo);
        $passwordGrant->setRefreshTokenTTL($refreshTokenExpiry);

        $implicitGrant = new ImplicitGrant($accessTokenExpiry);

        $refreshTokenGrant = new RefreshTokenGrant($refreshTokenRepo);
        $refreshTokenGrant->setRefreshTokenTTL($refreshTokenExpiry);
        //$refreshTokenGrant->canRespondToAccessTokenRequest();
        $server = new AuthorizationServer($clientRepo, $accessTokenRepo, $scopeRepo, $this->getPrivateKey(), $this->getPublicKey());
        $server->enableGrantType($passwordGrant, $accessTokenExpiry);
        $server->enableGrantType($authCodeGrant, $accessTokenExpiry);
        $server->enableGrantType($implicitGrant, $accessTokenExpiry);
        $server->enableGrantType($refreshTokenGrant, $accessTokenExpiry);

        return $server;
    }

    public function getValidator()
    {
        $em = $this->em;
        $accessTokenRepo = $em->getRepository(AccessToken::class);
        $server = new ResourceServer($accessTokenRepo, $this->getPublicKey());
        return $server;
    }
}

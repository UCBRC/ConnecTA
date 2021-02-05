<?php

namespace App\Entity\OAuth;

use App\Model\Token;
use Doctrine\ORM\Mapping as ORM;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\OAuth\AccessTokenRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class AccessToken extends Token implements AccessTokenEntityInterface, UserInterface
{
//    public function convertToJWT(CryptKey $privateKey)
//    {
//        $key = Key\LocalFileReference::file($privateKey->getKeyPath());
//        $config = Configuration::forAsymmetricSigner(new Sha256(), $key, $key);
//        return $config->builder()
//            // ->identifiedBy($this->getClient()->getIdentifier())
//            ->identifiedBy($this->getIdentifier(), true)
//            ->issuedAt(time())
//            ->canOnlyBeUsedAfter(time())
//            ->expiresAt($this->getExpiryDateTime()->getTimestamp())
//            ->relatedTo($this->getUserIdentifier()->getUsername())
//            ->withClaim('scopes', $this->getScopes())
//    }

    public function getRoles()
    {
        $scopes = $this->getScopes();
        // TODO
    }

    public function getPassword()
    {
        return $this->token;
    }

    public function getSalt()
    {
        return null;
    }

    public function getUsername()
    {
        return $this->user->getUsername();
    }

    public function eraseCredentials()
    {
        // TODO: Implement eraseCredentials() method.
    }

    public function setPrivateKey(CryptKey $privateKey)
    {
        // TODO: Implement setPrivateKey() method.
    }

    public function __toString()
    {
        return "";
    }
}

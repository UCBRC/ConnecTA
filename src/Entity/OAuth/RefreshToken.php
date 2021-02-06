<?php

namespace App\Entity\OAuth;

use Doctrine\ORM\Mapping as ORM;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\OAuth\RefreshTokenRepository")
 */
class RefreshToken implements RefreshTokenEntityInterface
{

    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(type="string", unique=true)
     */
    protected $token;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetimetz")
     */
    protected $expiryTime;

    /**
     * @var AccessTokenEntityInterface
     *
     * @ORM\OneToOne(targetEntity="App\Entity\OAuth\AccessToken")
     * @ORM\JoinColumn(name="access_token_id", referencedColumnName="token")
     */
    protected $accessToken;

    public function getAccessToken()
    {
        return $this->accessToken;
    }

    public function setAccessToken(?AccessTokenEntityInterface $accessToken)
    {
        $this->accessToken = $accessToken;
    }

    public function getIdentifier()
    {
        return $this->token;
    }

    public function setIdentifier($identifier)
    {
        $this->token = $identifier;
    }

    public function getExpiryDateTime()
    {
        return $this->expiryTime;
    }

    public function setExpiryDateTime(\DateTimeImmutable $dateTime)
    {
        $this->expiryTime = $dateTime;
    }
}

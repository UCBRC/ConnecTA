<?php

namespace App\Entity\OAuth;

use Doctrine\ORM\Mapping as ORM;
use League\OAuth2\Server\Entities\ClientEntityInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\OAuth\ClientRepository")
 */
class Client implements ClientEntityInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string",length=1024)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(type="string",length=128)
     */
    private $clientId;

    /**
     * @var string
     *
     * @ORM\Column(type="string",length=128)
     */
    private $clientSecret;

    /**
     * @var array
     *
     * @ORM\Column(type="json",length=128)
     */
    private $grantType;

    /**
     * @var string
     *
     * @ORM\Column(type="string",length=1024)
     */
    private $redirectUrl;

    /**
     * @var string
     *
     * @ORM\Column(type="string",length=1024)
     */
    private $version;

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->clientId;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getRedirectUri(): string
    {
        return $this->redirectUrl;
    }

    /**
     * @return string
     */
    public function getSecret(): string
    {
        return $this->clientSecret;
    }


    /**
     * @return array
     */
    public function getGrantType(): array
    {
        return $this->grantType;
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @param string $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }


    public function isConfidential()
    {
        return true;
    }
}

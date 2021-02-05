<?php

namespace App\Model;

use App\Entity\OAuth\Client;
use App\Entity\OAuth\Scope;
use App\Entity\User\User;
use App\Service\ScopeService;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Mapping as ORM;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\TokenInterface;


abstract class Token implements TokenInterface
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
     * @var \App\Entity\User\User
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    /**
     * @var Client
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\OAuth\Client")
     * @ORM\JoinColumn(name="client_id", referencedColumnName="id")
     */
    protected $client;

    /**
     * @var string
     *
     * @ORM\Column(type="json",length=1024)
     */
    protected $scopes;

    /**
     * @var ObjectManager
     */
    protected $em;

    public function __construct()
    {
        $this->scopes = json_encode([]);
    }

    /**
     * @ORM\PostLoad
     * @ORM\PostPersist
     */
    public function fetchEntityManager(LifecycleEventArgs $args)
    {
        $this->em = $args->getEntityManager();
    }

    /** @ORM\PreFlush */
    public function fetchUser(PreFlushEventArgs $args)
    {
        if (!($this->user instanceof \App\Entity\User\User)) {
            $em = $args->getEntityManager()->getRepository(User::class);
            $this->user = $em->findOneBy(["id" => $this->user]);
        }
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

    public function setUserIdentifier($identifier)
    {
        $this->user = $identifier;
    }

    public function getUserIdentifier()
    {
        return $this->user;
    }

    public function getClient()
    {
        return $this->client;
    }

    public function setClient(ClientEntityInterface $client)
    {
        $this->client = $client;
    }

    public function addScope(ScopeEntityInterface $scope)
    {
        $scopes = json_decode($this->scopes, true);
        if (!in_array($scope->getIdentifier(), $scopes)) {
            array_push($scopes, $scope->getIdentifier());
        }
        $this->scopes = json_encode($scopes);
    }

    public function getScopes()
    {
        $scopes = json_decode($this->scopes, true);
        $repo = $this->em->getRepository(Scope::class);
        return array_map(function ($scope) use ($repo) {
            return $repo->findOneBy(["token" => $scope]);
        }, $scopes);
    }
}
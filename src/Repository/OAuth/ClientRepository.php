<?php

namespace App\Repository\OAuth;

use App\Entity\OAuth\Client;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use Doctrine\Persistence\ManagerRegistry;

class ClientRepository extends ServiceEntityRepository implements ClientRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Client::class);
    }

    public function getClientEntity($clientIdentifier, $grantType = null, $clientSecret = null, $mustValidateSecret = true)
    {

        $client = $this->getClientById($clientIdentifier);
        if (@null === $client)
            return null;
        if (!in_array($grantType, $client->getGrantType()))
            return null;
        if ($mustValidateSecret && $clientSecret != $client->getSecret())
            return null;

        return $client;
    }

    public function getClientById($id)
    {
        return $this->findOneBy(["clientId" => $id]);
    }

    public function validateClient($clientIdentifier, $clientSecret, $grantType)
    {
        $client = $this->getClientById($clientIdentifier);
        if (@null === $client)
            return false;
        return $clientSecret == $client->getSecret();
    }
}

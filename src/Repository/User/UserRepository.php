<?php

namespace App\Repository\User;

use App\Entity\User\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;


/**
 * Class UserRepository
 * @package App\Repository
 */
class UserRepository extends ServiceEntityRepository implements UserRepositoryInterface
{
    /**
     * UserRepository constructor.
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findByUsername($username)
    {
        return $this->findOneBy(["username" => $username]);
    }

    public function findByToken($token)
    {
        return $this->findOneBy(["token" => $token]);
    }

    public function findByPhone($phone)
    {
        return $this->findOneBy(["phone" => $phone]);
    }

    public function findByEmail($email)
    {
        return $this->findOneBy(["email" => $email]);
    }

    public function getUserEntityByUserCredentials($username, $password, $grantType, ClientEntityInterface $clientEntity)
    {
        $user = $this->search($username);
        if (@null === $user) {
            return null;
        } else {
            if (password_verify($password, $user->getPassword())) {
                return $user;
            } else {
                return null;
            }
        }
    }

    public function search($info)
    {
        return $this->createQueryBuilder("u")
            ->where("u.phone = :phone")
            ->setParameter("phone", $info)
            ->orWhere("u.email = :email")
            ->setParameter("email", $info)
            ->orWhere("u.username = :username")
            ->setParameter("username", $info)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByUsernameAndEmailAndPhoneAndEnabled(?string $username, ?string $email, ?string $phone, string $enabled, bool $verified, int $size, int $offset, bool $reverse) {
        $query = $this->createQueryBuilder("u");
        $first = true;
        $first = $this->addOptionalWhere($query, "username", $username, $first);
        $first = $this->addOptionalWhere($query, "email", $email, $first);
        $this->addOptionalWhere($query, "phone", $phone, $first);
        if($enabled === "true")
            $query = $query->andWhere("u.enabled = :enabled")->setParameter("enabled", true);
        else if($enabled === "false")
            $query = $query->andWhere("u.enabled = :enabled")->setParameter("enabled", false);
        if($reverse)
            $query = $query->orderBy("u.id", "DESC");
        if($verified)
            $query = $query->join("u.authTickets", "a")
                ->andWhere("a.status = :status")
                ->setParameter("status", 5);
        return $query->setFirstResult($offset)
            ->setMaxResults($size)
            ->getQuery()
            ->getResult();
    }

    private function addOptionalWhere(QueryBuilder &$query, $key, $value, $first) {
        if(is_null($value) || $value == "")
            return $first;
        if($value == " ") {
            if($first)
                $query = $query->where("u.".$key. " IS NOT NULL");
            else
                $query = $query->andWhere("u.".$key. " IS NOT NULL");
            return false;
        } else if($first)
            $query = $query->where("u.".$key." LIKE :".$key);
        else
            $query = $query->andHaving("u.".$key." LIKE :".$key);
        $query = $query->setParameter($key, "%".$value."%");
        return false;
    }


}

<?php

namespace App\Entity\User;

use App\Entity\School\Alumni;
use App\Model\Permission;
use App\Model\PrivacyLevel;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use League\OAuth2\Server\Entities\UserEntityInterface;
use Symfony\Component\Security\Core\User\UserInterface;


/**
 * @ORM\Entity(repositoryClass="App\Repository\User\UserRepository")
 */
class User implements UserInterface, UserEntityInterface, \JsonSerializable
{

    /**
     * @var bool
     */
    public $isOAuth = false;
    /**
     * @var bool
     */
    public $disableAdmin = false;
    /**
     * @var string
     */
    public $realUsername = null;
    /**
     * @var string
     */
    public $htmlUsername = null;
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", unique=true)
     */
    private $id;
    /**
     * @var string
     *
     * @ORM\Column(type="string", unique=true)
     */
    private $username;
    /**
     * @var string
     *
     * @ORM\Column(type="string", unique=true, nullable = true)
     */
    private $email;
    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $password;
    /**
     * @var integer
     *
     * @ORM\Column(type="bigint", unique=true, nullable = true)
     */
    private $phone;
    /**
     * @var string
     *
     * @ORM\Column(type="string", unique=true)
     */
    private $token;
    /**
     * @var string
     *
     * @ORM\Column(type="string", unique=true, nullable=true)
     */
    private $weChatToken;
    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", options={"default": true})
     */
    private $enabled = true;
    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    private $admin = false;
    /**
     * @var float
     *
     * @ORM\Column(type="float", options={"unsigned":true, "default":0})
     */
    private $point = 0;
    /**
     * @var integer
     *
     * @ORM\Column(type="integer", options={"unsigned":true, "default":1})
     */
    private $privacy;
    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean", options={"default": true})
     */
    private $antiSpider = true;
    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", options={"default":"CURRENT_TIMESTAMP"})
     */
    private $readTime;
    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", options={"default":"CURRENT_TIMESTAMP"})
     */
    private $joinTime;

    /**
     * @var ArrayCollection
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\Event", mappedBy="users")

     */
    private $events;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\School\Alumni", mappedBy="user")
     * @ORM\OrderBy({"submitTime" = "desc"})
     */
    private $authTickets;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $card;

    public function __construct()
    {
        $this->joinTime = new \DateTime();
        $this->readTime = new \DateTime();
        $this->authTickets = new ArrayCollection();
        $this->privacy = PrivacyLevel::SAME_REGISTRATION;
        $this->regenerateToken();
    }

    public function regenerateToken(){
        $this->token = str_replace(["=","/","+"],["","",""],base64_encode(random_bytes(64)));
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
        $this->regenerateToken();
    }

    /**
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param int $phone
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    }

    /**
     * @return int
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @return \DateTime
     */
    public function getReadTime()
    {
        return $this->readTime;
    }

    /**
     * @param \DateTime $readTime
     */
    public function setReadTime($readTime)
    {
        $this->readTime = $readTime;
    }

    public function getInfoArray()
    {
        return array(
            "id" => $this->id,
            "point" => $this->getPoint(),
            "username" => $this->username,
            "verified" => $this->isVerified(),
            "htmlUsername" => $this->htmlUsername,
            "email" => $this->email,
            "phone" => $this->phone,
            "joinTime" => $this->joinTime,
            "admin" => $this->isAdmin(),
            // "status" => ($this->getValidAuth() ?? new Alumni())->getUserStatus()
        );
    }

    /**
     * @return bool
     */
    public function isAdmin(): bool
    {
        if($this->disableAdmin)
            return false;
        return $this->admin;
    }

    public function isVerified()
    {
        $auth = $this->getValidAuth();
        if(is_null($auth)){
            return false;
        }else{
            $this->realUsername = $this->username . "( " . $auth->getEnglishName() . " | " . $auth->getChineseName() . " )";
            $this->htmlUsername = $this->username . "&nbsp;<span style='background-color:#3CDBC0;'>" . $auth->getEnglishName() . "</span>&nbsp;<span style='background-color:#2DCCD3;'>" . $auth->getChineseName() . "</span>";
            return true;
        }
    }

    /**
     * @return Alumni|null
     */
    public function getValidAuth(){
        $valid = array_filter($this->authTickets->toArray(), array($this, "getValidRegardlessTime"));
        if (count($valid) > 0) {
            /** @var $auth Alumni */
            $auth = array_values($valid)[0];
            return $auth;
        } else {
            return null;
        }
    }

    /**
     * @return float
     */
    public function getPoint(): float
    {
        return $this->point;
    }

    /**
     * @param float $point
     */
    public function setPoint($point)
    {
        $this->point = $point;
    }

    /**
     * @param int $point
     */
    public function minusPoints($point)
    {
        $this->point -= $point;
    }

    /**
     * @return \DateTime
     */
    public function getJoinTime()
    {
        return $this->joinTime;
    }

    /**
     * @param \DateTime $joinTime
     */
    public function setJoinTime(\DateTime $joinTime): void
    {
        $this->joinTime = $joinTime;
    }

    /**
     * @return string
     */
    public function getWeChatToken(): ?string
    {
        return $this->weChatToken;
    }

    /**
     * @param string $weChatToken
     */
    public function setWeChatToken(?string $weChatToken): void
    {
        $this->weChatToken = $weChatToken;
    }


    public function getSalt()
    {
        return null;
    }

    public function eraseCredentials()
    {

    }

    public function hasRole($role)
    {
        $roles = $this->getRoles();
        if (in_array($role, $roles))
            return true;
        else
            return false;
    }

    public function getRoles()
    {
        if ($this->isAdmin())
            $permissions = [Permission::IS_ADMIN];
        else
            $permissions = [];
        $valid = array_filter($this->authTickets->toArray(), array($this, "getValid"));
        if (count($valid) > 0) {
            array_push($permissions, Permission::IS_AUTHENTICATED);
            foreach (array_values($valid) as $value) {
                array_push($permissions, $this->getAlumniPermission($value));
            }
            if(!is_null($this->getPhone()))
                array_push($permissions, Permission::HAS_PHONE);

        }
        return $permissions;
    }

    /**
     * @param $val Alumni
     * @return bool
     */
    public function getAlumniPermission($val)
    {
        return Permission::IS_STUDENT;
    }

    /**
     * @param $val Alumni
     * @return bool
     */
    public function getValid($val)
    {
        if ($val->getStatus() == Alumni::STATUS_PASSED)
            return true;
        else
            return false;
    }

    /**
     * @param $val Alumni
     * @return bool
     */
    public function getValidRegardlessTime($val)
    {
        if ($val->getStatus() == Alumni::STATUS_PASSED || $val->getStatus() == Alumni::STATUS_EXPIRED)
            return true;
        else
            return false;
    }

    public function getIdentifier()
    {
        return $this;
    }

    public function jsonSerialize()
    {
        return $this->getId();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getPrivacy(): int
    {
        return $this->privacy;
    }

    /**
     * @param int $privacy
     */
    public function setPrivacy(int $privacy): void
    {
        $this->privacy = $privacy;
    }

    /**
     * @return bool
     */
    public function isAntiSpider(): bool
    {
        return $this->antiSpider;
    }

    /**
     * @param bool $antiSpider
     */
    public function setAntiSpider(bool $antiSpider): void
    {
        $this->antiSpider = $antiSpider;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    /**
     * @return string
     */
    public function getCard(): ?string
    {
        return $this->card;
    }

    /**
     * @param string $card
     */
    public function setCard(string $card): void
    {
        $this->card = $card;
    }

    public function getAuthTickets()
    {
        return $this->authTickets;
    }

    public function getChineseName() {
        foreach ($this->authTickets as $ticket) {
            /** @var Alumni $ticket */
            if($ticket->getStatus() == 5 || $ticket->getStatus() == 6) {
                return $ticket->getChineseName();
            } else if($ticket->getStatus() == 7){
                return $ticket->getChineseName()."（非南外）";
            }
        }
        return null;
    }
    public function __toString()
    {
        return $this->username;
    }
}


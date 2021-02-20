<?php

namespace App\Entity\School;

use App\Entity\User\User;
use App\Model\PrivacyLevel;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component;
use Symfony\Component\Validator\Constraints as Assert;
use Ramsey\Uuid\Doctrine\UuidGenerator;

/**
 * @ORM\Entity(repositoryClass="App\Repository\School\AlumniRepository")
 */
class Alumni
{

    const STATUS_NOT_SUBMITTED = 0;
    const STATUS_SUBMITTED = 1;
    const STATUS_CANCELED = 2;
    const STATUS_REVIEWING = 3;
    const STATUS_REJECTED = 4;
    const STATUS_PASSED = 5;
    const STATUS_EXPIRED = 6;
    const STATUS_NOT_NFLS = 7;
    /**
     * @var \Ramsey\Uuid\UuidInterface
     *
     * @ORM\Id
     * @ORM\Column(type="uuid_binary_ordered_time", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="Ramsey\Uuid\Doctrine\UuidOrderedTimeGenerator")
     */
    private $id;
    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User\User",inversedBy="authTickets")
     */
    private $user;
    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetimetz", nullable=true)
     */
    private $submitTime;
    /**
     * @var integer
     *
     * @ORM\Column(type="smallint", nullable=true)
     */
    private $status = 0;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    private $chineseName;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    private $englishName;

    /**
     * @var string|null
     * @Assert\NotBlank(message="alumni-auth-error-university-blank")
     * @ORM\Column(type="string", nullable=true)
     */
    private $university;

    /**
     * @var int|null
     * @Assert\Choice(choices={0,1,2,3},message="alumni-auth-error-source-invalid")
     * @ORM\Column(type="smallint", nullable=true)
     */
    private $source;

    /**
     * @var int|null
     * @Assert\NotBlank(message="alumni-auth-error-source-invalid")
     * @ORM\Column(type="string", nullable=true)
     */
    private $sourceCustom;


    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=300, nullable=true)
     */
    private $remark;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="date", nullable=true)
     */
    private $expireAt;

    /**
     * @return UuidInterface
     */
    public function getId(): UuidInterface
    {
        return $this->id;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @return \DateTime|null
     */
    public function getSubmitTime(): ?\DateTime
    {
        return $this->submitTime;
    }

    /**
     * @param \DateTime|null $submitTime
     */
    public function setSubmitTime(?\DateTime $submitTime): void
    {
        $this->submitTime = $submitTime;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        if ($this->expireAt && $this->expireAt < new \DateTime())
            return self::STATUS_EXPIRED;
        return $this->status;
    }

    /**
     * @param int $status
     */
    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    /**
     * @return null|string
     */
    public function getChineseName(): ?string
    {
        return $this->chineseName;
    }

    /**
     * @param null|string $chineseName
     */
    public function setChineseName(?string $chineseName): void
    {
        $this->chineseName = $chineseName;
    }

    /**
     * @return null|string
     */
    public function getEnglishName(): ?string
    {
        return $this->englishName;
    }

    /**
     * @param null|string $englishName
     */
    public function setEnglishName(?string $englishName): void
    {
        $this->englishName = $englishName;
    }

    /**
     * @return null|string
     */
    public function getUniversity(): ?string
    {
        return $this->university;
    }

    /**
     * @param null|string $university
     */
    public function setUniversity(?string $university): void
    {
        $this->university = $university;
    }

    /**
     * @return null|string
     */
    public function getRemark(): ?string
    {
        return $this->remark;
    }

    /**
     * @param null|string $remark
     */
    public function setRemark(?string $remark): void
    {
        $this->remark = $remark;
    }

    public function setSource(?int $source): void
    {
        $this->source = $source;
    }

    public function getSource(): ?int
    {
        return $this->source;
    }

    public function setSourceCustom(?string $sourceCustom): void
    {
        $this->sourceCustom = $sourceCustom;
    }

    public function getSourceCustom(): ?string
    {
        return $this->sourceCustom;
    }

    /**
     * @return \DateTime|null
     */
    public function getExpireAt(): ?\DateTime
    {
        return $this->expireAt;
    }

    /**
     * @param \DateTime|null $expireAt
     */
    public function setExpireAt(?\DateTime $expireAt): void
    {
        $this->expireAt = $expireAt;
    }


    /**
     * @Assert\IsTrue(message="alumni-auth-error-either-englishName-or-chineseName")
     */
    public function hasName(): bool
    {
        return ($this->englishName != null && $this->englishName !== "") ||
            ($this->chineseName != null && $this->chineseName !== "");
    }

    public function pbi(int $level, Alumni $sender) {
        $array = [
            "chineseName" => $this->chineseName,
            "englishName" => $this->englishName,
            "source" => $this->source,
            "sourceCustom" => $this->sourceCustom,
            "university" => $this->university
        ];
//        if(
//            $level === PrivacyLevel::SAME_SCHOOL ||
//            (
//                $level === PrivacyLevel::SAME_REGISTRATION &&
//                (
//                    $sender->getJuniorRegistration() === $this->getJuniorRegistration() ||
//                    $sender->getSeniorRegistration() === $this->getSeniorRegistration()
//                )
//            )
//        ){
//            $array["university"] = $this->university;
//        }
        return $array;
    }

    public function __clone()
    {
        $this->id = null;
        $this->status = 0;
        $this->expireAt = null;
    }

}
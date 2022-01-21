<?php

namespace App\Entity\Route;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Route\BranchRepository")
 */
class Branch
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"Default"})
     */
    private $id;

    /**
     * @var string|Uuid|null
     * @ORM\Column(type="uuid", nullable=true)
     * @JMS\Type("string")
     * @Groups({"Default"})
     */
    private $guid;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     * @Groups({"Default"})
     */
    protected $branchCode;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     * @Groups({"Default"})
     */
    protected $branchName;

    /**
     * @var string|Uuid|null
     * @ORM\Column(type="uuid", nullable=true)
     * @JMS\Type("string")
     * @Groups({"Default"})
     */
    protected $directionGuid;

    /**
     * @var string|Uuid|null
     * @ORM\Column(type="uuid", nullable=true)
     * @JMS\Type("string")
     * @Groups({"Default"})
     */
    protected $mainDepartmentGuid;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGuid(): ?UuidInterface
    {
        if (is_string($this->guid)) {
            return Uuid::fromString($this->guid);
        }

        return $this->guid;
    }

    public function setGuid(string $guid): self
    {
        $this->guid = $guid;

        return $this;
    }

    public function getBranchCode(): ?string
    {
        return $this->branchCode;
    }

    public function setBranchCode(?string $branchCode): self
    {
        $this->branchCode = $branchCode;

        return $this;
    }

    public function getBranchName(): ?string
    {
        return $this->branchName;
    }

    public function setBranchName(?string $branchName): self
    {
        $this->branchName = $branchName;

        return $this;
    }

    public function getDirectionGuid(): ?UuidInterface
    {
        if (is_string($this->directionGuid)) {
            return Uuid::fromString($this->directionGuid);
        }

        return $this->directionGuid;
    }

    public function setDirectionGuid(string $directionGuid): self
    {
        $this->directionGuid = $directionGuid;

        return $this;
    }

    public function getMainDepartmentGuid(): ?UuidInterface
    {
        if (is_string($this->mainDepartmentGuid)) {
            return Uuid::fromString($this->mainDepartmentGuid);
        }

        return $this->mainDepartmentGuid;
    }

    public function setMainDepartmentGuid(string $mainDepartmentGuid): self
    {
        $this->mainDepartmentGuid = $mainDepartmentGuid;

        return $this;
    }
}

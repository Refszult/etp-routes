<?php

namespace App\Entity\Route;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Route\CargoPipelinePlacesOfEventRepository")
 */
class CargoPipelinePlacesOfEvent
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"Default"})
     */
    private $id;

    /**
     * @var Uuid
     * @ORM\Column(name="guid", type="uuid", unique=true)
     * @JMS\Type("string")
     * @Groups({"Default"})
     */
    protected $guid;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     * @Groups({"Default"})
     */
    protected $branchCode;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"Default"})
     */
    private $name;

    /**
     * @ORM\OneToMany(
     *     targetEntity=CargoPipeline::class,
     *     mappedBy="cargoPipelineEvent",
     *     cascade={"remove"}
     * )
     * @JMS\Exclude()
     */
    private $cargoPipelines;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     * @JMS\Exclude()
     */
    private $isCancel = false;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     * @JMS\Exclude()
     */
    private $isDeleted = false;

    public function __construct()
    {
        $this->cargoPipelines = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGuid(): ?UuidInterface
    {
        if (!$this->guid) {
            return null;
        }
        if (is_string($this->guid)) {
            return Uuid::fromString($this->guid);
        }

        return $this->guid;
    }

    public function setGuid(string $guid): self
    {
        $this->guid = Uuid::fromString($guid);

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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection|CargoPipeline[]
     */
    public function getCargoPipelines(): Collection
    {
        return $this->cargoPipelines;
    }

    public function addCargoPipeline(CargoPipeline $cargoPipeline): self
    {
        if (!$this->cargoPipelines->contains($cargoPipeline)) {
            $this->cargoPipelines[] = $cargoPipeline;
            $cargoPipeline->setCargoPipelinePlaceOfEvent($this);
        }

        return $this;
    }

    public function removeCargoPipeline(CargoPipeline $cargoPipeline): self
    {
        if ($this->cargoPipelines->contains($cargoPipeline)) {
            $this->cargoPipelines->removeElement($cargoPipeline);
            // set the owning side to null (unless already changed)
            if ($cargoPipeline->getCargoPipelinePlaceOfEvent() === $this) {
                $cargoPipeline->setCargoPipelinePlaceOfEvent(null);
            }
        }

        return $this;
    }

    public function getIsCancel(): ?bool
    {
        return $this->isCancel;
    }

    public function setIsCancel(bool $isCancel): self
    {
        $this->isCancel = $isCancel;

        return $this;
    }

    public function getIsDeleted(): ?bool
    {
        return $this->isDeleted;
    }

    public function setIsDeleted(bool $isDeleted): self
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }
}

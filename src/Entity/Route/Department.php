<?php

namespace App\Entity\Route;

use App\Entity\Traits\GuidTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Route\DepartmentRepository")
 */
class Department
{
    use GuidTrait;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"Default"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"Default"})
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=50)
     * @Groups({"Default"})
     */
    private $code;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     * @Groups({"Default"})
     */
    private $timeZone;

    /**
     * @var string|Uuid|null
     * @ORM\Column(type="uuid", nullable=true)
     * @JMS\Type("string")
     * @Groups({"Default"})
     */
    protected $branchGuid;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Route\Warehouse", mappedBy="department")
     * @JMS\Exclude()
     */
    private $warehouses;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Route\Warehouse")
     * @Groups({"Default"})
     */
    private $mainWarehouse;

    /**
     * @ORM\Column(type="boolean", options={"default" : false})
     * @Groups({"Default"})
     */
    private $extraditionPoint = false;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Route\RouteWayDimension", mappedBy="firstPoint")
     * @JMS\Exclude()
     */
    private $routeWayDimensionsIsFirst;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Route\RouteWayDimension", mappedBy="lastPoint")
     * @JMS\Exclude()
     */
    private $routeWayDimensionsIsLast;

    public function __construct()
    {
        $this->warehouses = new ArrayCollection();
        $this->routeWayDimensionsIsFirst = new ArrayCollection();
        $this->routeWayDimensionsIsLast = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getTimeZone(): ?string
    {
        return $this->timeZone;
    }

    public function setTimeZone(?string $timeZone): self
    {
        $this->timeZone = $timeZone;

        return $this;
    }

    public function getBranchGuid(): ?UuidInterface
    {
        if (is_string($this->branchGuid)) {
            return Uuid::fromString($this->branchGuid);
        }

        return $this->branchGuid;
    }

    public function setBranchGuid(string $branchGuid): self
    {
        $this->branchGuid = $branchGuid;

        return $this;
    }

    /**
     * @return Collection|Warehouse[]
     */
    public function getWarehouses(): Collection
    {
        return $this->warehouses;
    }

    public function addWarehouse(Warehouse $warehouse): self
    {
        if (!$this->warehouses->contains($warehouse)) {
            $this->warehouses[] = $warehouse;
            $warehouse->setDepartment($this);
        }

        return $this;
    }

    public function removeWarehouse(Warehouse $warehouse): self
    {
        if ($this->warehouses->contains($warehouse)) {
            $this->warehouses->removeElement($warehouse);
            // set the owning side to null (unless already changed)
            if ($warehouse->getDepartment() === $this) {
                $warehouse->setDepartment(null);
            }
        }

        return $this;
    }

    public function getMainWarehouse(): ?Warehouse
    {
        return $this->mainWarehouse;
    }

    public function setMainWarehouse(?Warehouse $mainWarehouse): self
    {
        $this->mainWarehouse = $mainWarehouse;

        return $this;
    }

    public function getExtraditionPoint(): ?bool
    {
        return $this->extraditionPoint;
    }

    public function setExtraditionPoint(?bool $extraditionPoint): self
    {
        $this->extraditionPoint = $extraditionPoint;

        return $this;
    }

    /**
     * @return Collection|RouteWayDimension[]
     */
    public function getRouteWayDimensionsIsFirst(): Collection
    {
        return $this->routeWayDimensionsIsFirst;
    }

    public function addRouteWayDimensionsIsFirst(RouteWayDimension $routeWayDimension): self
    {
        if (!$this->routeWayDimensionsIsFirst->contains($routeWayDimension)) {
            $this->routeWayDimensionsIsFirst[] = $routeWayDimension;
        }

        return $this;
    }

    public function removeRouteWayDimensionsIsFirst(RouteWayDimension $routeWayDimension): self
    {
        if ($this->routeWayDimensionsIsFirst->contains($routeWayDimension)) {
            $this->routeWayDimensionsIsFirst->removeElement($routeWayDimension);
        }

        return $this;
    }

    /**
     * @return Collection|RouteWayDimension[]
     */
    public function getRouteWayDimensionsIsLast(): Collection
    {
        return $this->routeWayDimensionsIsLast;
    }

    public function addRouteWayDimensionsIsLast(RouteWayDimension $routeWayDimension): self
    {
        if (!$this->routeWayDimensionsIsLast->contains($routeWayDimension)) {
            $this->routeWayDimensionsIsLast[] = $routeWayDimension;
        }

        return $this;
    }

    public function removeRouteWayDimensionsIsLast(RouteWayDimension $routeWayDimension): self
    {
        if ($this->routeWayDimensionsIsLast->contains($routeWayDimension)) {
            $this->routeWayDimensionsIsLast->removeElement($routeWayDimension);
        }

        return $this;
    }
}

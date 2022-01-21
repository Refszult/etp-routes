<?php

namespace App\Entity\Route;

use App\Entity\Agreement\Organization;
use App\Entity\Traits\CancelTrait;
use App\Entity\Traits\GuidTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Route\TransportationRepository")
 */
class Transportation
{
    use GuidTrait;
    use CancelTrait;

    const TYPE_DRIVER_EXPEDITION = 8;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"Default"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=50)
     * @Groups({"Default"})
     */
    private $name;

    /**
     * @ORM\ManyToMany(targetEntity=Organization::class, mappedBy="transportations")
     * @Groups({"Default"})
     */
    private $organizations;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Route\RouteWayTransportation", mappedBy="transportation")
     * @JMS\Exclude()
     */
    private $routeWayTransportations;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"Default"})
     */
    private $description;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     * @Groups({"Default"})
     */
    private $color;

    /**
     * @var TransportationSettings
     * @ORM\OneToOne(targetEntity="App\Entity\Route\TransportationSettings", mappedBy="transportation")
     * @Groups({"Default"})
     */
    private $transportationSettings;

    public function __construct()
    {
        $this->routeWayTransportations = new ArrayCollection();
        $this->organizations = new ArrayCollection();
    }

    /**
     * @param mixed $id
     *
     * @return Transportation
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
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

    /**
     * @return Collection|RouteWayTransportation[]
     */
    public function getRouteWayTransportations(): Collection
    {
        return $this->routeWayTransportations;
    }

    public function addRouteWayTransportations(RouteWayTransportation $routeWayTransportation): self
    {
        if (!$this->routeWayTransportations->contains($routeWayTransportation)) {
            $this->routeWayTransportations[] = $routeWayTransportation;
            $routeWayTransportation->setTransportation($this);
        }

        return $this;
    }

    public function removeRouteWayTransportations(RouteWayTransportation $routeWayTransportation): self
    {
        if ($this->routeWayTransportations->contains($routeWayTransportation)) {
            $this->routeWayTransportations->removeElement($routeWayTransportation);
            // set the owning side to null (unless already changed)
            if ($routeWayTransportation->getTransportation() === $this) {
                $routeWayTransportation->setTransportation(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Organization[]
     */
    public function getOrganizations(): Collection
    {
        return $this->organizations;
    }

    public function addOrganization(Organization $organization): self
    {
        if (!$this->organizations->contains($organization)) {
            $this->organizations[] = $organization;
        }

        return $this;
    }

    public function removeOrganization(Organization $organization): self
    {
        $this->organizations->removeElement($organization);

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function getTransportationSettings()
    {
        return $this->transportationSettings;
    }

    public function setTransportationSettings(TransportationSettings $transportationSettings): Transportation
    {
        $this->transportationSettings = $transportationSettings;

        return $this;
    }
}

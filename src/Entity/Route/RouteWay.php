<?php

namespace App\Entity\Route;

use App\Entity\Tender\RouteTemplate;
use App\Entity\Traits\CreatedTrait;
use App\Entity\Traits\DeletedTrait;
use App\Entity\Traits\GuidTrait;
use App\Entity\Traits\UpdatedTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;
use Ramsey\Uuid\Uuid;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Route\RouteWayRepository")
 * @Gedmo\SoftDeleteable(fieldName="deletedOn", timeAware=false, hardDelete=false)
 */
class RouteWay
{
    use GuidTrait;
    use CreatedTrait;
    use UpdatedTrait;
    use DeletedTrait;

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
    private $code;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"Default"})
     */
    private $name;

    /**
     * @ORM\Column(type="boolean", options={"default" : true})
     * @JMS\Groups({"RouteWay_base"})
     * @Groups({"RouteWay_base"})
     */
    private $active = true;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Route\Route", mappedBy="routeWay")
     * @JMS\Groups({"RouteWay_details"})
     * @Groups({"RouteWay_details"})
     */
    private $routes;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Route\TransportationType")
     * @JMS\Groups({"RouteWay_base"})
     * @Groups({"RouteWay_base"})
     */
    private $transportationType;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Route\RouteWayPoint", mappedBy="routeWay")
     * @JMS\Groups({"RouteWay_info", "Tender_details"})
     * @Groups({"RouteWay_info", "Tender_details"})
     */
    private $routeWayPoints;

    /**
     * @ORM\Column(type="json_array", nullable=true)
     * @JMS\Groups({"RouteWay_details"})
     * @Groups({"RouteWay_details"})
     */
    private $routeWayDirections;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Route\RouteWayTransportation", mappedBy="routeWay", orphanRemoval=true, cascade={"remove"})
     * @Groups({"Default"})
     */
    private $routeWayTransportations;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Tender\RouteTemplate", mappedBy="routeWay", orphanRemoval=true, cascade={"remove"})
     * @Groups({"Default"})
     * @JMS\Exclude()
     */
    private $routeTemplates;

    /**
     * @ORM\Column(type="smallint", options={"default" : 0})
     * @JMS\Groups({"MQ"})
     * @Groups({"MQ"})
     */
    private $updatedFrom = 0;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     * @Assert\IsFalse(
     *     groups={"inRoute"},
     *     message="Вы пытаетесь установить отмененный маршрут рейса"
     * )
     * @Groups({"Default"})
     */
    private $isCancel = false;

//    /**
//     * @ORM\OneToOne(targetEntity="App\Entity\Route\RouteWayDimension", mappedBy="routeWay")
//     * @ORM\JoinColumn(nullable=true)
//     */
//    private $routeWayDimension;

    public function __construct()
    {
        $this->routes = new ArrayCollection();
        $this->routeWayPoints = new ArrayCollection();
        $this->routeTemplates = new ArrayCollection();
        $this->routeWayTransportations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @return Collection|Route[]
     */
    public function getRoutes(): Collection
    {
        return $this->routes;
    }

    public function addRoute(Route $route): self
    {
        if (!$this->routes->contains($route)) {
            $this->routes[] = $route;
            $route->setRouteWay($this);
        }

        return $this;
    }

    public function removeRoute(Route $route): self
    {
        if ($this->routes->contains($route)) {
            $this->routes->removeElement($route);
            // set the owning side to null (unless already changed)
            if ($route->getRouteWay() === $this) {
                $route->setRouteWay(null);
            }
        }

        return $this;
    }

    public function getTransportationType(): ?TransportationType
    {
        return $this->transportationType;
    }

    public function setTransportationType(?TransportationType $transportationType): self
    {
        $this->transportationType = $transportationType;

        return $this;
    }

    /**
     * @return Collection|RouteWayPoint[]
     */
    public function getRouteWayPoints(): Collection
    {
        return $this->routeWayPoints;
    }

    public function addRouteWayPoint(RouteWayPoint $routeWayPoint): self
    {
        if (!$this->routeWayPoints->contains($routeWayPoint)) {
            $this->routeWayPoints[] = $routeWayPoint;
            $routeWayPoint->setRouteWay($this);
        }

        return $this;
    }

    public function removeRouteWayPoint(RouteWayPoint $routeWayPoint): self
    {
        if ($this->routeWayPoints->contains($routeWayPoint)) {
            $this->routeWayPoints->removeElement($routeWayPoint);
            // set the owning side to null (unless already changed)
            if ($routeWayPoint->getRouteWay() === $this) {
                $routeWayPoint->setRouteWay(null);
            }
        }

        return $this;
    }

    /**
     * Получение первой точки маршрута.
     *
     * @return RouteWayPoint|null
     */
    public function getFirstRouteWayPoint(): ?RouteWayPoint
    {
        $min = 0;
        $minPoint = null;
        foreach ($this->getRouteWayPoints() as $routeWayPoint) {
            $number = $routeWayPoint->getRowNumber();
            if (0 === $min) {
                $min = $number;
                $minPoint = $routeWayPoint;
            } elseif ($min > $number) {
                $min = $number;
                $minPoint = $routeWayPoint;
            }
        }

        return $minPoint;
    }

    /**
     * Получение последней точки маршрута.
     *
     * @return RouteWayPoint|null
     */
    public function getLastRouteWayPoint(): ?RouteWayPoint
    {
        $max = 0;
        $maxPoint = null;
        foreach ($this->getRouteWayPoints() as $routeWayPoint) {
            $number = $routeWayPoint->getRowNumber();
            if ($max < $number) {
                $max = $number;
                $maxPoint = $routeWayPoint;
            }
        }

        return $maxPoint;
    }

    public function getRouteWayDirections()
    {
        return $this->routeWayDirections;
    }

    public function setRouteWayDirections($routeWayDirections): self
    {
        $this->routeWayDirections = $routeWayDirections;

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
            $routeWayTransportation->setRouteWay($this);
        }

        return $this;
    }

    public function removeRouteWayTransportations(RouteWayTransportation $routeWayTransportation): self
    {
        if ($this->routeWayTransportations->contains($routeWayTransportation)) {
            $this->routeWayTransportations->removeElement($routeWayTransportation);
            // set the owning side to null (unless already changed)
            if ($routeWayTransportation->getRouteWay() === $this) {
                $routeWayTransportation->setRouteWay(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|RouteTemplate[]
     */
    public function getRouteTemplates(): Collection
    {
        return $this->routeTemplates;
    }

    public function addRouteTemplate(RouteTemplate $routeTemplate): self
    {
        if (!$this->routeTemplates->contains($routeTemplate)) {
            $this->routeTemplates[] = $routeTemplate;
            $routeTemplate->setRouteWay($this);
        }

        return $this;
    }

    public function removeRouteTemplate(RouteTemplate $routeTemplate): self
    {
        if ($this->routeTemplates->contains($routeTemplate)) {
            $this->routeTemplates->removeElement($routeTemplate);
            // set the owning side to null (unless already changed)
            if ($routeTemplate->getRouteWay() === $this) {
                $routeTemplate->setRouteWay(null);
            }
        }

        return $this;
    }

    public function getUpdatedFrom(): ?int
    {
        return $this->updatedFrom;
    }

    public function setUpdatedFrom(int $updatedFrom): self
    {
        $this->updatedFrom = $updatedFrom;

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

//    public function getRouteWayDimension(): ?RouteWayDimension
//    {
//        return $this->routeWayDimension;
//    }
//
//    public function setRouteWayDimension(?RouteWayDimension $routeWayDimension): self
//    {
//        $this->routeWayDimension = $routeWayDimension;
//
//        return $this;
//    }
}

<?php

namespace App\Entity\Route;

use App\Entity\Traits\CreatedTrait;
use App\Entity\Traits\UpdatedTrait;
use App\Entity\Vehicle\Dimension;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;


/**
 * @ORM\Entity(repositoryClass="App\Repository\Route\RouteWayDimensionRepository")
 * @UniqueEntity(
 *     fields={"firstPoint", "lastPoint"},
 *     errorPath="firstPoint, lastPoint",
 *     message="Для выбранной пары точек маршрута габарит ТС уже выбран."
 * )
 * @ORM\Table(uniqueConstraints={
 *     @ORM\UniqueConstraint(name="rwd_ukey", columns={
 *     "first_point_id",
 *     "last_point_id"
 * })
 * })
 */
class RouteWayDimension
{
    use CreatedTrait;
    use UpdatedTrait;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"Default"})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Vehicle\Dimension", inversedBy="routeWayDimensions")
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"Default"})
     */
    private $dimension;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Route\Department", inversedBy="routeWayDimensionsIsFirst")
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"Default"})
     */
    private $firstPoint;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Route\Department", inversedBy="routeWayDimensionsIsLast")
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"Default"})
     */
    private $lastPoint;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Vehicle\Dimension", mappedBy="optionalRouteWayDimensions")
     * @Groups({"Default"})
     */
    private $optionalDimensions;

    public function __construct()
    {
        $this->optionalDimensions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDimension(): ?Dimension
    {
        return $this->dimension;
    }

    public function setDimension(Dimension $dimension): self
    {
        $this->dimension = $dimension;

        return $this;
    }

    public function getFirstPoint(): ?Department
    {
        return $this->firstPoint;
    }

    public function setFirstPoint(Department $firstPoint): self
    {
        $this->firstPoint = $firstPoint;

        return $this;
    }

    public function getLastPoint(): ?Department
    {
        return $this->lastPoint;
    }

    public function setLastPoint(Department $lastPoint): self
    {
        $this->lastPoint = $lastPoint;

        return $this;
    }

    /**
     * @return Collection|Dimension[]
     */
    public function getOptionalDimensions(): ?Collection
    {
        return $this->optionalDimensions;
    }

    public function addOptionalDimension(Dimension $optionalDimension): self
    {
        if (!$this->optionalDimensions->contains($optionalDimension)) {
            $this->optionalDimensions[] = $optionalDimension;
            $optionalDimension->addOptionalRouteWayDimension($this);
        }

        return $this;
    }

    public function removeOptionalDimension(Dimension $optionalDimension): self
    {
        if ($this->optionalDimensions->contains($optionalDimension)) {
            $this->optionalDimensions->removeElement($optionalDimension);
            $optionalDimension->removeOptionalRouteWayDimension($this);
        }

        return $this;
    }
}

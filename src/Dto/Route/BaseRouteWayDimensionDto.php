<?php

namespace App\Dto\Route;

use App\Dto\DtoClass;
use App\Entity\Route\Department;
use App\Entity\Vehicle\Dimension;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;


abstract class BaseRouteWayDimensionDto extends DtoClass
{
    /**
     * @var int
     * @JMS\Type("integer")
     */
    protected $id;

    /**
     * @var Dimension
     * @JMS\Type("App\Entity\Vehicle\Dimension")
     * @Assert\NotBlank(
     *     message="Габарит ТС обязателен для заполнения."
     * )
     */
    protected $dimension;

    /**
     * @var Department
     * @JMS\Type("App\Entity\Route\Department")
     * @Assert\NotBlank(
     *     message="Первая точка маршрута обязательна для заполнения."
     * )
     */
    protected $firstPoint;

    /**
     * @var Department
     * @JMS\Type("App\Entity\Route\Department")
     * @Assert\NotBlank(
     *     message="Последняя точка маршрута обязательна для заполнения."
     * )
     */
    protected $lastPoint;

    /**
     * @var Collection
     * @JMS\Type("ArrayCollection<App\Entity\Vehicle\Dimension>")
     */
    private $optionalDimensions;

    /**
     * @var array
     * @JMS\Exclude()
     */
    public $createFields = [
        'dimension',
        'firstPoint',
        'lastPoint',
    ];

    /**
     * @var array
     * @JMS\Exclude()
     */
    public $updateFields = [
        'dimension',
    ];

    public function __construct()
    {
        $this->optionalDimensions = new ArrayCollection();
    }

    /**
     * @return int
     */
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

    public function getOptionalDimensions(): ?Collection
    {
        return $this->optionalDimensions;
    }

    public function addOptionalDimensions(Dimension $dimension): self
    {
        if (!$this->optionalDimensions->contains($dimension)) {
            $this->optionalDimensions[] = $dimension;
        }

        return $this;
    }

    public function removeOptionalDimensions(Dimension $dimension): self
    {
        if ($this->optionalDimensions->contains($dimension)) {
            $this->optionalDimensions->removeElement($dimension);
        }

        return $this;
    }
}

<?php

namespace App\Dto\Route;

use App\Classes\StaticStorage\UpdatedFrom;
use App\Dto\DtoClass;
use App\Dto\Vehicle\ApiVehicleDto;
use App\Entity\Container\Container;
use App\Entity\Contractor;
use App\Entity\Customer;
use App\Entity\Driver\ContractorDriver;
use App\Entity\Driver\Driver;
use App\Entity\Route\Route;
use App\Entity\Route\RouteContainer;
use App\Entity\Route\RouteMovementPoint;
use App\Entity\Route\RouteOwner;
use App\Entity\Route\RouteWay;
use App\Entity\Route\RouteWayPoint;
use App\Entity\Route\Transportation;
use App\Entity\Route\TransportationType;
use App\Entity\Route\VehicleOrder;
use App\Entity\Vehicle\Vehicle;
use App\Exceptions\WrongObjectException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use JMS\Serializer\Annotation as JMS;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


abstract class BaseRouteWayDto extends DtoClass
{
    /**
     * @var bool
     * @JMS\Type("bool")
     */
    protected $isCancel = false;

    /**
     * @var int
     * @JMS\Type("integer")
     */
    protected $id;

    /**
     * @var int
     * @JMS\Type("integer")
     * @JMS\Groups({"MQ"})
     */
    protected $updatedFrom = UpdatedFrom::UPDATED_FROM_ETP;

    /**
     * @var string
     * @JMS\Type("string")
     */
    protected $guid;

    /**
     * @var string
     * @JMS\Type("string")
     */
    protected $code;

    /**
     * @var string
     * @JMS\Type("string")
     */
    protected $name;

    /**
     * @var bool
     * @JMS\Type("boolean")
     */
    protected $active = true;

    /**
     * @var TransportationType
     * @JMS\Type("App\Entity\Route\TransportationType")
     */
    protected $transportationType;

    /**
     * @var Collection
     * @JMS\Type("ArrayCollection<App\Entity\Route\RouteWayPoint>")
     * @JMS\Groups({"RouteWay_info", "RouteWay_des"})
     */
    protected $routeWayPoints;

    /**
     * @var array
     * @JMS\Type("array")
     * @JMS\Groups({"RouteWay_base", "RouteWay_des"})
     */
    protected $routeWayDirections;

    /**
     * @var array
     * @JMS\Type("array")
     * @JMS\Groups({"RouteWay_base", "RouteWay_des"})
     */
    protected $routeWayTransportationKinds;

    /**
     * @var array
     * @JMS\Exclude()
     */
    public $createFields = [
        'updatedFrom',
        'guid',
        'code',
        'name',
        'active',
        'routeWayDirections',
    ];

    /**
     * @var array
     * @JMS\Exclude()
     */
    public $updateFields = [
        'updatedFrom',
        'code',
        'name',
        'active',
        'routeWayDirections',
    ];

    public function __construct()
    {
        $this->routeWayPoints = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return bool
     */
    public function getIsCancel(): bool
    {
        return $this->isCancel;
    }

    /**
     * @param bool $isCancel
     */
    public function setIsCancel(bool $isCancel): void
    {
        $this->isCancel = $isCancel;
    }

    /**
     * @return int
     */
    public function getUpdatedFrom(): int
    {
        return $this->updatedFrom;
    }

    /**
     * @param int $updatedFrom
     */
    public function setUpdatedFrom(int $updatedFrom): void
    {
        $this->updatedFrom = $updatedFrom;
    }

    /**
     * @return string
     */
    public function getGuid(): string
    {
        return $this->guid;
    }

    /**
     * @param string $guid
     */
    public function setGuid(string $guid): void
    {
        $this->guid = $guid;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @param string $code
     */
    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return bool
     */
    public function getActive(): bool
    {
        return $this->active;
    }

    /**
     * @param bool $active
     */
    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    /**
     * @return TransportationType
     */
    public function getTransportationType(): TransportationType
    {
        return $this->transportationType;
    }

    /**
     * @param TransportationType $transportationType
     */
    public function setTransportationType(TransportationType $transportationType): void
    {
        $this->transportationType = $transportationType;
    }

    public function getRouteWayPoints(): Collection
    {
        return $this->routeWayPoints;
    }

    public function addRouteWayPoints(RouteWayPoint $routeWayPoint): self
    {
        if (!$this->routeWayPoints->contains($routeWayPoint)) {
            $this->routeWayPoints[] = $routeWayPoint;
        }

        return $this;
    }

    public function removeRouteWayPoints(RouteWayPoint $routeWayPoint): self
    {
        if ($this->routeWayPoints->contains($routeWayPoint)) {
            $this->routeWayPoints->removeElement($routeWayPoint);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getRouteWayDirections(): array
    {
        return $this->routeWayDirections;
    }

    /**
     * @param array $routeWayDirections
     */
    public function setRouteWayDirections(array $routeWayDirections): void
    {
        $this->routeWayDirections = $routeWayDirections;
    }
}

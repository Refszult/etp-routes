<?php

namespace App\Dto\Route;

use App\Classes\StaticStorage\UpdatedFrom;
use App\Dto\DtoInterface;
use App\Entity\Route\Route;
use App\Entity\Route\RouteMovementPoint;
use App\Entity\Route\Transportation;
use App\Entity\Route\TransportationType;
use App\Entity\Route\VehicleOrder;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use JMS\Serializer\Annotation as JMS;

class MQRouteDto extends BaseRouteDto implements DtoInterface
{
    /**
     * @var int
     * @JMS\Type("integer")
     * @JMS\Groups({"MQ"})
     */
    protected $updatedFrom = UpdatedFrom::UPDATED_FROM_IBMMQ;

    /**
     * @var string
     * @JMS\Type("string")
     * @JMS\Groups({"MQ"})
     */
    protected $guid;

    /**
     * @var bool
     * @JMS\Type("boolean")
     * @JMS\Groups({"MQ"})
     */
    protected $isDraft;

    /**
     * @var bool
     * @JMS\Type("boolean")
     * @JMS\Groups({"MQ"})
     */
    protected $isLinked = false;

    /**
     * @var string
     * @JMS\Type("string")
     */
    protected $routeCode;

    /**
     * @var bool
     * @JMS\Type("boolean")
     */
    protected $cargoFlow = false;

    /**
     * @var Route
     * @JMS\Type("App\Entity\Route\Route")
     * @JMS\Groups({"Route_info", "Route_des"})
     */
    protected $routeParent;

    /**
     * @var VehicleOrder
     * @JMS\Type("App\Entity\Route\VehicleOrder")
     * @JMS\Groups({"Route_info", "Route_des"})
     */
    protected $vehicleOrder;

    /**
     * @var \DateTime
     * @JMS\Type("DateTime")
     */
    protected $planDateOfFirstPointLoading;

    /**
     * @var \DateTime
     * @JMS\Type("DateTime")
     */
    protected $factDateOfFirstPointArrive;

    /**
     * @var string
     * @JMS\Type("string")
     * @JMS\Groups({"MQ"})
     */
    protected $userId;

    /**
     * @var TransportationType
     * @JMS\Type("App\Entity\Route\TransportationType")
     */
    protected $transportationType;

    /**
     * @var Transportation
     * @JMS\Type("App\Entity\Route\Transportation")
     */
    protected $transportation;

    /**
     * @var array
     * @JMS\Type("array")
     * @JMS\Groups({"MQ"})
     */
    protected $directionsOfLoading;

    /**
     * @var array
     * @JMS\Type("array")
     * @JMS\Groups({"MQ"})
     */
    protected $transportRoute;

    /**
     * @var array
     * @JMS\Type("array")
     * @JMS\Groups({"MQ"})
     */
    protected $routePointsUtilization;

    /**
     * @var array
     * @JMS\Type("array")
     * @JMS\Groups({"MQ"})
     */
    protected $routePointsLimitation;

    /**
     * @var array
     * @JMS\Type("array")
     * @JMS\Groups({"MQ"})
     */
    protected $routeDocuments;

    /**
     * @var array
     * @JMS\Type("array")
     * @JMS\Groups({"MQ"})
     */
    protected $routeFreight;

    /**
     * @var string
     * @JMS\Type("string")
     */
    protected $freightSumm;

    /**
     * @var Collection|RouteMovementPoint[]|null
     * @JMS\Type("ArrayCollection<App\Entity\Route\RouteMovementPoint>")
     * @JMS\Groups({"Route_info", "Route_des"})
     */
    protected $movementPoints;

    /**
     * @var bool
     * @JMS\Type("boolean")
     */
    protected $deleted = false;

    /**
     * @var array
     * @JMS\Exclude()
     */
    public $createFields = [
        'updatedFrom',
        'guid',
        'isDraft',
        'isLinked',
        'orderDate',
        'routeDate',
        'boostFlag',
        'routeCode',
        'cargoFlow',
        'planDateOfFirstPointArrive',
        'planDateOfFirstPointLoading',
        'factDateOfFirstPointArrive',
        'routeOwnerId',
        'userId',
        'directionsOfLoading',
        'transportRoute',
        'routePointsUtilization',
        'routePointsLimitation',
        'routeDocuments',
        'routeFreight',
        'haulerBlocked',
        'freightSumm',
        'organization',
    ];

    /**
     * @var array
     * @JMS\Exclude()
     */
    public $updateFields = [
        'updatedFrom',
        'isDraft',
        'isLinked',
        'orderDate',
        'routeDate',
        'boostFlag',
        'routeCode',
        'cargoFlow',
        'planDateOfFirstPointArrive',
        'planDateOfFirstPointLoading',
        'factDateOfFirstPointArrive',
        'routeOwnerId',
        'userId',
        'directionsOfLoading',
        'transportRoute',
        'routePointsUtilization',
        'routePointsLimitation',
        'routeDocuments',
        'routeFreight',
        'haulerBlocked',
        'freightSumm',
        'organization',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->movementPoints = new ArrayCollection();
    }

    public function getUpdatedFrom(): int
    {
        return $this->updatedFrom;
    }

    public function setUpdatedFrom(int $updatedFrom): void
    {
        $this->updatedFrom = $updatedFrom;
    }

    public function getGuid(): string
    {
        return $this->guid;
    }

    public function setGuid(string $guid): void
    {
        $this->guid = $guid;
    }

    public function getIsDraft(): bool
    {
        return $this->isDraft;
    }

    public function setIsDraft(bool $isDraft): void
    {
        $this->isDraft = $isDraft;
    }

    public function getIsLinked(): bool
    {
        return $this->isLinked;
    }

    public function setIsLinked(bool $isLinked): void
    {
        $this->isLinked = $isLinked;
    }

    public function getRouteCode(): string
    {
        return $this->routeCode;
    }

    public function setRouteCode(string $routeCode): void
    {
        $this->routeCode = $routeCode;
    }

    public function getCargoFlow(): bool
    {
        return $this->cargoFlow;
    }

    public function setCargoFlow(bool $cargoFlow): void
    {
        $this->cargoFlow = $cargoFlow;
    }

    public function getRouteParent(): Route
    {
        return $this->routeParent;
    }

    public function setRouteParent(Route $routeParent): void
    {
        $this->routeParent = $routeParent;
    }

    public function getVehicleOrder(): ?VehicleOrder
    {
        return $this->vehicleOrder;
    }

    public function setVehicleOrder(VehicleOrder $vehicleOrder): void
    {
        $this->vehicleOrder = $vehicleOrder;
    }

    public function getPlanDateOfFirstPointLoading(): ?\DateTime
    {
        return $this->planDateOfFirstPointLoading;
    }

    public function setPlanDateOfFirstPointLoading(\DateTime $planDateOfFirstPointLoading): void
    {
        $this->planDateOfFirstPointLoading = $planDateOfFirstPointLoading;
    }

    public function getFactDateOfFirstPointArrive(): ?\DateTime
    {
        return $this->factDateOfFirstPointArrive;
    }

    public function setFactDateOfFirstPointArrive(\DateTime $factDateOfFirstPointArrive): void
    {
        $this->factDateOfFirstPointArrive = $factDateOfFirstPointArrive;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function setUserId(string $userId): void
    {
        $this->userId = $userId;
    }

    public function getTransportationType(): TransportationType
    {
        return $this->transportationType;
    }

    public function setTransportationType(TransportationType $transportationType): void
    {
        $this->transportationType = $transportationType;
    }

    public function getTransportation(): Transportation
    {
        return $this->transportation;
    }

    public function setTransportation(Transportation $transportation): void
    {
        $this->transportation = $transportation;
    }

    public function getDirectionsOfLoading(): array
    {
        return $this->directionsOfLoading;
    }

    public function setDirectionsOfLoading(array $directionsOfLoading): void
    {
        $this->directionsOfLoading = $directionsOfLoading;
    }

    public function getTransportRoute(): array
    {
        return $this->transportRoute;
    }

    public function setTransportRoute(array $transportRoute): void
    {
        $this->transportRoute = $transportRoute;
    }

    public function getRoutePointsUtilization(): array
    {
        return $this->routePointsUtilization;
    }

    public function setRoutePointsUtilization(array $routePointsUtilization): void
    {
        $this->routePointsUtilization = $routePointsUtilization;
    }

    public function getRoutePointsLimitation(): array
    {
        return $this->routePointsLimitation;
    }

    public function setRoutePointsLimitation(array $routePointsLimitation): void
    {
        $this->routePointsLimitation = $routePointsLimitation;
    }

    public function getRouteDocuments(): array
    {
        return $this->routeDocuments;
    }

    public function setRouteDocuments(array $routeDocuments): void
    {
        $this->routeDocuments = $routeDocuments;
    }

    public function getRouteFreight(): array
    {
        return $this->routeFreight;
    }

    public function setRouteFreight(array $routeFreight): void
    {
        $this->routeFreight = $routeFreight;
    }

    public function getFreightSumm(): string
    {
        return $this->freightSumm;
    }

    public function setFreightSumm(string $freightSumm): void
    {
        $this->freightSumm = $freightSumm;
    }

    public function getMovementPoints(): Collection
    {
        return $this->movementPoints;
    }

    public function addMovementPoint(RouteMovementPoint $movementPoint): self
    {
        if (!$this->movementPoints->contains($movementPoint)) {
            $this->movementPoints[] = $movementPoint;
        }

        return $this;
    }

    public function removeMovementPoint(RouteMovementPoint $movementPoint): self
    {
        if ($this->movementPoints->contains($movementPoint)) {
            $this->movementPoints->removeElement($movementPoint);
        }

        return $this;
    }

    public function getDeleted(): bool
    {
        return $this->deleted;
    }

    public function setDeleted(bool $deleted): void
    {
        $this->deleted = $deleted;
    }
}

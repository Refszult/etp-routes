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
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;
use App\Validator\Constraints as AppAssert;

class CommandRouteDto extends BaseRouteDto implements DtoInterface
{
    /**
     * @var int
     * @JMS\Type("integer")
     * @JMS\Groups({"MQ"})
     */
    protected $updatedFrom = UpdatedFrom::UPDATED_FROM_ETP;

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
     * @Assert\NotBlank(
     *     message="Плановая дата прибытия в первую точку не может быть пустой."
     * )
     *
     * @var \DateTime
     * @JMS\Type("DateTime")
     */
    protected $planDateOfFirstPointArrive;

    /**
     * @var string
     * @JMS\Type("string")
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
        'routeWay',
        'organization',
        'contractor',
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
        'contractor',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->movementPoints = new ArrayCollection();
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
     * @return bool
     */
    public function getIsDraft(): bool
    {
        return $this->isDraft;
    }

    /**
     * @param bool $isDraft
     */
    public function setIsDraft(bool $isDraft): void
    {
        $this->isDraft = $isDraft;
    }

    /**
     * @return bool
     */
    public function getIsLinked(): bool
    {
        return $this->isLinked;
    }

    /**
     * @param bool $isLinked
     */
    public function setIsLinked(bool $isLinked): void
    {
        $this->isLinked = $isLinked;
    }

    /**
     * @return string
     */
    public function getRouteCode(): string
    {
        return $this->routeCode;
    }

    /**
     * @param string $routeCode
     */
    public function setRouteCode(string $routeCode): void
    {
        $this->routeCode = $routeCode;
    }

    /**
     * @return bool
     */
    public function getCargoFlow(): bool
    {
        return $this->cargoFlow;
    }

    /**
     * @param bool $cargoFlow
     */
    public function setCargoFlow(bool $cargoFlow): void
    {
        $this->cargoFlow = $cargoFlow;
    }

    /**
     * @return Route
     */
    public function getRouteParent(): Route
    {
        return $this->routeParent;
    }

    /**
     * @param Route $routeParent
     */
    public function setRouteParent(Route $routeParent): void
    {
        $this->routeParent = $routeParent;
    }

    /**
     * @return VehicleOrder
     */
    public function getVehicleOrder(): VehicleOrder
    {
        return $this->vehicleOrder;
    }

    /**
     * @param VehicleOrder $vehicleOrder
     */
    public function setVehicleOrder(VehicleOrder $vehicleOrder): void
    {
        $this->vehicleOrder = $vehicleOrder;
    }

    /**
     * @return \DateTime|null
     */
    public function getPlanDateOfFirstPointLoading(): ?\DateTime
    {
        return $this->planDateOfFirstPointLoading;
    }

    /**
     * @param \DateTime $planDateOfFirstPointLoading
     */
    public function setPlanDateOfFirstPointLoading(\DateTime $planDateOfFirstPointLoading): void
    {
        $this->planDateOfFirstPointLoading = $planDateOfFirstPointLoading;
    }

    /**
     * @return \DateTime|null
     */
    public function getFactDateOfFirstPointArrive(): ?\DateTime
    {
        return $this->factDateOfFirstPointArrive;
    }

    /**
     * @param \DateTime $factDateOfFirstPointArrive
     */
    public function setFactDateOfFirstPointArrive(\DateTime $factDateOfFirstPointArrive): void
    {
        $this->factDateOfFirstPointArrive = $factDateOfFirstPointArrive;
    }

    /**
     * @return string
     */
    public function getUserId(): string
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     */
    public function setUserId(string $userId): void
    {
        $this->userId = $userId;
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

    /**
     * @return Transportation
     */
    public function getTransportation(): Transportation
    {
        return $this->transportation;
    }

    /**
     * @param Transportation $transportation
     */
    public function setTransportation(Transportation $transportation): void
    {
        $this->transportation = $transportation;
    }

    /**
     * @return array
     */
    public function getDirectionsOfLoading(): array
    {
        return $this->directionsOfLoading;
    }

    /**
     * @param array $directionsOfLoading
     */
    public function setDirectionsOfLoading(array $directionsOfLoading): void
    {
        $this->directionsOfLoading = $directionsOfLoading;
    }

    /**
     * @return array
     */
    public function getTransportRoute(): array
    {
        return $this->transportRoute;
    }

    /**
     * @param array $transportRoute
     */
    public function setTransportRoute(array $transportRoute): void
    {
        $this->transportRoute = $transportRoute;
    }

    /**
     * @return array
     */
    public function getRoutePointsUtilization(): array
    {
        return $this->routePointsUtilization;
    }

    /**
     * @param array $routePointsUtilization
     */
    public function setRoutePointsUtilization(array $routePointsUtilization): void
    {
        $this->routePointsUtilization = $routePointsUtilization;
    }

    /**
     * @return array
     */
    public function getRoutePointsLimitation(): array
    {
        return $this->routePointsLimitation;
    }

    /**
     * @param array $routePointsLimitation
     */
    public function setRoutePointsLimitation(array $routePointsLimitation): void
    {
        $this->routePointsLimitation = $routePointsLimitation;
    }

    /**
     * @return array
     */
    public function getRouteDocuments(): array
    {
        return $this->routeDocuments;
    }

    /**
     * @param array $routeDocuments
     */
    public function setRouteDocuments(array $routeDocuments): void
    {
        $this->routeDocuments = $routeDocuments;
    }

    /**
     * @return array
     */
    public function getRouteFreight(): array
    {
        return $this->routeFreight;
    }

    /**
     * @param array $routeFreight
     */
    public function setRouteFreight(array $routeFreight): void
    {
        $this->routeFreight = $routeFreight;
    }

    /**
     * @return string
     */
    public function getFreightSumm(): string
    {
        return $this->freightSumm;
    }

    /**
     * @param string $freightSumm
     */
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

    /**
     * @return bool
     */
    public function getDeleted(): bool
    {
        return $this->deleted;
    }

    /**
     * @param bool $deleted
     */
    public function setDeleted(bool $deleted): void
    {
        $this->deleted = $deleted;
    }
}

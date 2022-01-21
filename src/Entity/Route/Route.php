<?php

namespace App\Entity\Route;

use App\Entity\Agreement\Organization;
use App\Entity\Auction\Auction;
use App\Entity\Auction\AuctionRoute;
use App\Entity\Contractor;
use App\Entity\Customer;
use App\Entity\CustomerUser;
use App\Entity\Driver\Driver;
use App\Entity\Tender\RouteTemplate;
use App\Entity\Tender\Tender;
use App\Entity\Traits\CreatedTrait;
use App\Entity\Traits\DeletedTrait;
use App\Entity\Traits\GuidTrait;
use App\Entity\Traits\SyncedTrait;
use App\Entity\Traits\UpdatedTrait;
use App\Entity\Traits\WarningTrait;
use App\Entity\Vehicle\Vehicle;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Intl\Timezones;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Route\RouteRepository")
 * @ORM\Table(
 *     name="routes"
 * )
 * @Gedmo\SoftDeleteable(fieldName="deletedOn", timeAware=false, hardDelete=false)
 */
class Route
{
    use CreatedTrait;
    use UpdatedTrait;
    use DeletedTrait;
    use GuidTrait;
    use SyncedTrait;
    use WarningTrait;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"Default"})
     */
    private $id;

    /**
     * @ORM\Column(type="boolean", options={"default" : true})
     * @JMS\Groups({"Route_info", "Auction_route_info"})
     * @Groups({"Route_info", "Auction_route_info"})
     */
    private $isDraft = true;

    /**
     * @ORM\Column(type="boolean", options={"default" : false})
     * @JMS\Groups({"Route_info", "Auction_route_info"})
     * @Groups({"Route_info", "Auction_route_info"})
     */
    private $isLinked = false;

    /**
     * @ORM\Column(type="datetime")
     * @JMS\Groups({"Route_info", "Auction_route_info"})
     * @Groups({"Route_info", "Auction_route_info"})
     */
    private $orderDate;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime")
     * @JMS\AccessType("public_method")
     * @JMS\Type("DateTime")
     * @JMS\Groups({"Route_info", "Auction_route_info"})
     * @Groups({"Route_info", "Auction_route_info"})
     */
    private $routeDate;

    /**
     * @ORM\Column(type="string", options={"default" : "+00:00"})
     * @JMS\Exclude()
     */
    protected $routeDateTz = '+00:00';

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     * @Groups({"Default"})
     */
    private $routeCode;

    /**
     * @ORM\Column(type="boolean", options={"default" : false})
     * @Groups({"Default"})
     */
    private $boostFlag = false;

    /**
     * @ORM\Column(type="boolean", options={"default" : false})
     * @JMS\Groups({"Route_info", "Auction_route_info"})
     * @Groups({"Route_info", "Auction_route_info"})
     */
    private $cargoFlow = false;

    /**
     * @ORM\Column(type="boolean", options={"default" : false})
     * @JMS\Groups({"Route_info", "Auction_route_info"})
     * @Groups({"Route_info", "Auction_route_info"})
     */
    private $closed = false;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Route\Route", cascade={"persist", "remove"})
     * @JMS\Groups({"Route_info", "Auction_route_info"})
     * @Groups({"Route_info", "Auction_route_info"})
     */
    private $routeParent;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Route\VehicleOrder", inversedBy="route", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     * @JMS\Groups({"Route_info", "Auction_route_info"})
     * @Groups({"Route_info", "Auction_route_info"})
     */
    private $vehicleOrder;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Route\RouteWay", inversedBy="routes")
     * @ORM\JoinColumn(nullable=false)
     * @JMS\Groups({"Route_info", "Auction_route_info"})
     * @Groups({"Route_info", "Auction_route_info"})
     * @Assert\NotBlank(
     *     message="Не передан маршрут."
     * )
     */
    private $routeWay;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Vehicle\Vehicle")
     * @JMS\Groups({"Route_info", "Auction_route_info"})
     * @Groups({"Route_info", "Auction_route_info"})
     */
    private $transport;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Vehicle\Vehicle")
     * @JMS\Groups({"Route_info", "Auction_route_info"})
     * @Groups({"Route_info", "Auction_route_info"})
     */
    private $trailer;

    /**
     * @ORM\Column(type="string", options={"default" : "+00:00"})
     * @JMS\Exclude()
     */
    protected $planDateOfFirstPointArriveTz = '+00:00';

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     * @JMS\AccessType("public_method")
     * @JMS\Type("DateTime")
     * @Groups({"Default"})
     */
    private $planDateOfFirstPointArrive;

    /**
     * @ORM\Column(type="string", options={"default" : "+00:00"})
     * @JMS\Exclude()
     */
    protected $planDateOfFirstPointLoadingTz = '+00:00';

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     * @JMS\AccessType("public_method")
     * @JMS\Type("DateTime")
     * @Groups({"Default"})
     */
    private $planDateOfFirstPointLoading;

    /**
     * @ORM\Column(type="string", options={"default" : "+00:00"})
     * @JMS\Exclude()
     */
    protected $factDateOfFirstPointArriveTz = '+00:00';

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     * @JMS\AccessType("public_method")
     * @JMS\Type("DateTime")
     * @JMS\Groups({"Route_info", "Auction_route_info"})
     * @Groups({"Route_info", "Auction_route_info"})
     */
    private $factDateOfFirstPointArrive;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     * @JMS\Groups({"Route_info", "Auction_route_info"})
     * @Groups({"Route_info", "Auction_route_info"})
     */
    private $routeOwnerId;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     * @JMS\Groups({"Route_info", "Auction_route_info"})
     * @Groups({"Route_info", "Auction_route_info"})
     */
    private $userId;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Route\TransportationType")
     * @JMS\Groups({"Route_info", "Auction_route_info"})
     * @Groups({"Route_info", "Auction_route_info"})
     * @Assert\NotBlank(
     *     message="Передан неверный вид перевозки."
     * )
     */
    private $transportationType;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Route\Transportation")
     * @JMS\Groups({"Route_info", "Auction_route_info"})
     * @Groups({"Route_info", "Auction_route_info"})
     * @Assert\NotBlank(
     *     message="Передан неверный тип перевозки."
     * )
     */
    private $transportation;

    /**
     * @ORM\Column(type="json_array", nullable=true)
     * @JMS\Groups({"Route_details", "Auction_route_details"})
     * @Groups({"Route_details", "Auction_route_details"})
     */
    private $directionsOfLoading;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Contractor", inversedBy="routes")
     * @JMS\Groups({"Route_info", "Auction_route_info", "Auction_route_details"})
     * @Groups({"Route_info", "Auction_route_info", "Auction_route_details"})
     */
    private $contractor;

    /**
     * @ORM\Column(type="json_array", nullable=true)
     * @JMS\Groups({"Route_info", "Auction_route_details"})
     * @Groups({"Route_info", "Auction_route_details"})
     */
    private $transportRoute;

    /**
     * @ORM\Column(type="json_array", nullable=true)
     * @JMS\Groups({"Route_info", "Auction_route_details"})
     * @Groups({"Route_info", "Auction_route_details"})
     */
    private $routePointsUtilization;

    /**
     * @ORM\OneToMany(
     *     targetEntity="App\Entity\Route\RouteMovementPoint",
     *     mappedBy="route",
     *     cascade={"persist", "remove"}
     * )
     * @JMS\Groups({"Route_info", "Auction_route_details"})
     * @Groups({"Route_info", "Auction_route_details"})
     */
    private $movementPoints;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Driver\Driver")
     * @JMS\Groups({"Route_info", "Auction_route_details"})
     * @Groups({"Route_info", "Auction_route_details"})
     */
    private $driverOne;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Driver\Driver")
     * @JMS\Groups({"Route_info", "Auction_route_details"})
     * @Groups({"Route_info", "Auction_route_details"})
     */
    private $driverTwo;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Customer", inversedBy="routes")
     * @JMS\Groups({"Route_info", "Auction_route_details"})
     * @Groups({"Route_info", "Auction_route_details"})
     */
    private $customer;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Route\RouteContainer", mappedBy="route")
     * @JMS\Groups({"Route_info", "Auction_route_details"})
     * @Groups({"Route_info", "Auction_route_details"})
     */
    private $routeContainers;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Route\RouteDisclaimer", mappedBy="route")
     * @JMS\Groups({"Route_customer"})
     * @Groups({"Route_customer"})
     */
    private $routeDisclaimers;

    /**
     * @var RouteDisclaimer|null
     * @JMS\Type("App\Entity\Route\RouteDisclaimer")
     * @JMS\AccessType("public_method")
     * @JMS\Groups({"Route_info", "Auction_route_info"})
     * @Groups({"Route_info", "Auction_route_info"})
     */
    private $contractorDisclaimer = null;

    /**
     * @var int
     * @JMS\Type("int")
     * @JMS\AccessType("public_method")
     * @JMS\Groups({"Route_info", "Auction_route_info"})
     * @Groups({"Route_info", "Auction_route_info"})
     */
    private $disclaimer = 0;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Route\RouteOwner")
     * @JMS\Groups({"Route_info", "Auction_info"})
     * @Groups({"Route_info", "Auction_info"})
     */
    private $naRouteOwner;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\CustomerUser", inversedBy="managedRoutes")
     * @JMS\Type("App\Entity\CustomerUser")
     * @JMS\Groups({"Route_info", "Auction_route_info"})
     * @Groups({"Route_info", "Auction_route_info"})
     */
    private $naRouteUser;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     * @JMS\Groups({"Route_info", "Auction_route_info"})
     * @Groups({"Route_info", "Auction_route_info"})
     */
    private $isCancel = false;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     * @JMS\Groups({"Route_info", "Auction_route_info"})
     * @Groups({"Route_info", "Auction_route_info"})
     */
    private $isDirty = false;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     * @JMS\Groups({"Route_info", "Auction_route_info"})
     * @Groups({"Route_info", "Auction_route_info"})
     */
    private $haulerBlocked = false;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Auction\Auction", inversedBy="routes")
     * @JMS\Groups({"Route_info", "Auction_route_details"})
     * @Groups({"Route_info", "Auction_route_details"})
     */
    private $auction;

    /**
     * @ORM\Column(type="json_array", nullable=true)
     * @JMS\Groups({"Route_details", "Auction_route_details"})
     * @Groups({"Route_details", "Auction_route_details"})
     */
    private $routePointsLimitation;

    /**
     * @ORM\Column(type="json_array", nullable=true)
     * @JMS\Groups({"Route_details", "Auction_route_details"})
     * @Groups({"Route_details", "Auction_route_details"})
     */
    private $routeDocuments;

    /**
     * @ORM\Column(type="json_array", nullable=true)
     * @JMS\Groups({"Route_details", "Auction_route_details"})
     * @Groups({"Route_details", "Auction_route_details"})
     */
    private $routeFreight;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Tender\Tender", inversedBy="routes")
     * @JMS\Groups({"Route_info", "Auction_route_details"})
     * @Groups({"Route_info", "Auction_route_details"})
     */
    private $tender;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Tender\RouteTemplate", inversedBy="routes")
     * @Groups({"Route_info"})
     */
    private $routeTemplate;

    /**
     * @ORM\Column(type="decimal", precision=12, scale=2, nullable=true)
     * @JMS\Groups({"Route_info", "Auction_route_info"})
     * @Groups({"Route_info", "Auction_route_info"})
     */
    private $freightSumm;

    /**
     * @ORM\Column(type="decimal", precision=12, scale=2, nullable=true)
     * @Groups({"Route_info", "Auction_route_info"})
     */
    private $initialSumm;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     * @JMS\Groups({"Route_info", "Auction_route_info"})
     */
    private $lateness = false;

    /**
     * @ORM\OneToOne(targetEntity=AuctionRoute::class, mappedBy="route", cascade={"remove"}, fetch="EAGER")
     * @JMS\Groups({"Route_info", "Auction_route_info"})
     * @Groups({"Route_info", "Auction_route_info"})
     */
    private $auctionRoute;

    /**
     * @ORM\Column(type="boolean", nullable=true, options={"default":false})
     */
    private $editedOnEtp = false;

    /**
     * @ORM\OneToOne(targetEntity=RouteCurrentState::class, inversedBy="route", cascade={"persist", "remove"})
     * @JMS\Groups({"Route_info", "Auction_route_info"})
     * @Groups({"Route_info", "Auction_route_info"})
     */
    private $routeCurrentState;

    /**
     * @ORM\OneToMany(targetEntity=RouteCurrentState::class, mappedBy="route")
     * @JMS\Exclude()
     */
    private $routeStatesLog;

    /**
     * @ORM\OneToMany(
     *     targetEntity=CargoPipeline::class,
     *     mappedBy="route",
     *     cascade={"remove", "persist"},
     *     orphanRemoval=true
     * )
     * @Groups({"Default"})
     */
    private $cargoPipelines;

    /**
     * @ORM\ManyToOne(targetEntity=Organization::class)
     * @Groups({"Default"})
     */
    private $organization;
    // TODO не забыть добавить контейнеры для автомобиля и для прицепа

    public function __construct()
    {
        $this->guid = Uuid::uuid4();
        $this->movementPoints = new ArrayCollection();
        $this->routeContainers = new ArrayCollection();
        $this->routeDisclaimers = new ArrayCollection();
        $this->cargoPipelines = new ArrayCollection();
    }

    public function getContractorDisclaimer(): ?RouteDisclaimer
    {
        $disclaimer = null;
        if ($this->contractor) {
            $contractorId = $this->contractor->getId();
            $disclaimers = $this->routeDisclaimers->filter(
                function ($entry) use ($contractorId) {
                    if ($entry->getContractor()->getId() === $contractorId) {
                        return true;
                    } else {
                        return false;
                    }
                }
            );
            if ($disclaimers->count() > 0) {
                $disclaimer = $disclaimers->last();
            }
        }

        return $disclaimer;
    }

    /**
     * @param RouteDisclaimer $disclaimer
     *
     * @return Route
     */
    public function setContractorDisclaimer(?RouteDisclaimer $disclaimer): self
    {
        if ($disclaimer) {
            $this->contractorDisclaimer = $disclaimer;
        }

        return $this;
    }

    /**
     * @return int|mixed
     */
    public function getDisclaimer()
    {
        $disclaimer = 0;
        if ($this->routeDisclaimers) {
            $routeDisclaimers = $this->routeDisclaimers->filter(
                function ($entry) {
                    if (RouteDisclaimer::STATUS_NEW === $entry->getStatus()) {
                        return true;
                    } else {
                        return false;
                    }
                }
            );
            if ($routeDisclaimers && !$routeDisclaimers->isEmpty()) {
                $disclaimer = $routeDisclaimers->first()->getId();
            }
        }

        return $disclaimer;
    }

    public function setDisclaimer(int $disclaimer): self
    {
        $this->disclaimer = $disclaimer;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIsDraft(): ?bool
    {
        return $this->isDraft;
    }

    public function setIsDraft(bool $isDraft): self
    {
        $this->isDraft = $isDraft;

        return $this;
    }

    public function getIsLinked(): ?bool
    {
        return $this->isLinked;
    }

    public function setIsLinked(bool $isLinked): self
    {
        $this->isLinked = $isLinked;

        return $this;
    }

    public function getOrderDate(): ?\DateTimeInterface
    {
        return $this->orderDate;
    }

    public function setOrderDate(\DateTimeInterface $orderDate): self
    {
        $this->orderDate = $orderDate;

        return $this;
    }

    public function getRouteDate(): ?\DateTime
    {
        if ($this->routeDate) {
            $this->routeDate->setTimezone(new \DateTimeZone($this->routeDateTz));
        }

        return $this->routeDate;
    }

    public function getClearRouteDate(): ?\DateTime
    {
        return $this->routeDate;
    }

    public function setRouteDate(\DateTime $routeDate): self
    {
        $this->routeDate = $routeDate;
        if ($this->routeDate) {
            $this->setRouteDateTz($routeDate->getTimezone());
        }

        return $this;
    }

    public function getRouteDateTz(): \DateTimeZone
    {
        return new \DateTimeZone($this->routeDateTz);
    }

    public function setRouteDateTz(\DateTimeZone $timeZone): self
    {
        if (strpos($timeZone->getName(), ':')) {
            $this->routeDateTz = $timeZone->getName();
        } else {
            $this->routeDateTz = substr(Timezones::getGmtOffset($timeZone->getName()), 3);
        }

        return $this;
    }

    public function getRouteCode(): ?string
    {
        return $this->routeCode;
    }

    public function setRouteCode(?string $routeCode): self
    {
        $this->routeCode = $routeCode;

        return $this;
    }

    public function getBoostFlag(): ?bool
    {
        return $this->boostFlag;
    }

    public function setBoostFlag(bool $boostFlag): self
    {
        $this->boostFlag = $boostFlag;

        return $this;
    }

    public function getCargoFlow(): ?bool
    {
        return $this->cargoFlow;
    }

    public function setCargoFlow(bool $cargoFlow): self
    {
        $this->cargoFlow = $cargoFlow;

        return $this;
    }

    public function getClosed(): ?bool
    {
        return $this->closed;
    }

    public function setClosed(bool $closed): self
    {
        $this->closed = $closed;

        return $this;
    }

    public function getRouteParent(): ?self
    {
        return $this->routeParent;
    }

    public function setRouteParent(?self $routeParent): self
    {
        $this->routeParent = $routeParent;

        return $this;
    }

    public function getVehicleOrder(): ?VehicleOrder
    {
        return $this->vehicleOrder;
    }

    public function setVehicleOrder(VehicleOrder $vehicleOrder): self
    {
        $this->vehicleOrder = $vehicleOrder;

        return $this;
    }

    public function getRouteWay(): ?RouteWay
    {
        return $this->routeWay;
    }

    public function setRouteWay(?RouteWay $routeWay): self
    {
        $this->routeWay = $routeWay;

        return $this;
    }

    public function getTransport(): ?Vehicle
    {
        return $this->transport;
    }

    public function setTransport(?Vehicle $transport): self
    {
        $this->transport = $transport;

        return $this;
    }

    public function getTrailer(): ?Vehicle
    {
        return $this->trailer;
    }

    public function setTrailer(?Vehicle $trailer): self
    {
        $this->trailer = $trailer;

        return $this;
    }

    public function getRouteOwnerId(): ?string
    {
        return $this->routeOwnerId;
    }

    public function setRouteOwnerId(?string $routeOwnerId): self
    {
        $this->routeOwnerId = $routeOwnerId;

        return $this;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function setUserId(?string $userId): self
    {
        $this->userId = $userId;

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

    public function getTransportation(): ?Transportation
    {
        return $this->transportation;
    }

    public function setTransportation(?Transportation $transportation): self
    {
        $this->transportation = $transportation;

        return $this;
    }

    public function getDirectionsOfLoading()
    {
        return $this->directionsOfLoading;
    }

    public function setDirectionsOfLoading($directionsOfLoading): self
    {
        $this->directionsOfLoading = $directionsOfLoading;

        return $this;
    }

    public function getContractor(): ?Contractor
    {
        return $this->contractor;
    }

    public function setContractor(?Contractor $contractor): self
    {
        $this->contractor = $contractor;

        return $this;
    }

    public function getTransportRoute()
    {
        return $this->transportRoute;
    }

    public function setTransportRoute($transportRoute): self
    {
        $this->transportRoute = $transportRoute;

        return $this;
    }

    public function getRoutePointsUtilization()
    {
        return $this->routePointsUtilization;
    }

    public function setRoutePointsUtilization($routePointsUtilization): self
    {
        $this->routePointsUtilization = $routePointsUtilization;

        return $this;
    }

    /**
     * @return Collection|RouteMovementPoint[]
     */
    public function getMovementPoints(): Collection
    {
        return $this->movementPoints;
    }

    public function getFirstMovementPoint(): ?RouteMovementPoint
    {
        $movementPoints = $this->getMovementPoints()->toArray();
        $firstPoint = null;
        if (count($movementPoints)) {
            usort($movementPoints, function ($a, $b) {
                if ($a->getRowNumber() == $b->getRowNumber()) {
                    return 0;
                }

                return ($a->getRowNumber() < $b->getRowNumber()) ? -1 : 1;
            });
            $firstPoint = $movementPoints[0];
        }

        return $firstPoint;
    }

    public function getLastMovementPoint(): ?RouteMovementPoint
    {
        $movementPoints = $this->getMovementPoints()->toArray();
        $lastPoint = null;
        if (count($movementPoints)) {
            usort($movementPoints, function ($a, $b) {
                if ($a->getRowNumber() == $b->getRowNumber()) {
                    return 0;
                }

                return ($a->getRowNumber() > $b->getRowNumber()) ? -1 : 1;
            });
            $lastPoint = $movementPoints[0];
        }

        return $lastPoint;
    }

    public function setMovementPoints(Collection $movementPoins): self
    {
        $this->movementPoints = $movementPoins;

        return $this;
    }

    public function addMovementPoint(RouteMovementPoint $movementPoint): self
    {
        if (!$this->movementPoints->contains($movementPoint)) {
            $this->movementPoints[] = $movementPoint;
            $movementPoint->setRoute($this);
        }

        return $this;
    }

    public function removeMovementPoint(RouteMovementPoint $movementPoint): self
    {
        if ($this->movementPoints->contains($movementPoint)) {
            $this->movementPoints->removeElement($movementPoint);
            // set the owning side to null (unless already changed)
            if ($movementPoint->getRoute() === $this) {
                $movementPoint->setRoute(null);
            }
        }

        return $this;
    }

    /**
     * Получение точки движения рейса по отделению.
     */
    public function getMovementPointByDepartment(Department $department): ?RouteMovementPoint
    {
        $depGuid = $department->getGuid();
        $wayPoints = $this->getMovementPoints()->filter(
            function ($entry) use ($depGuid) {
                /* @var RouteMovementPoint $entry */
                return $entry->getDepartment()->getGuid() === $depGuid;
            }
        );
        if (0 !== $wayPoints->count()) {
            return $wayPoints->first();
        } else {
            return null;
        }
    }

    /**
     * Поиск отделения среди точек движения рейса.
     */
    public function hasDepartment(Department $department): bool
    {
        if ($this->getMovementPointByDepartment($department)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Проверка на то, является ли точка с переданным отделением последней активной.
     */
    public function isLastActiveMovementPoint(Department $department): bool
    {
        $output = false;
        if ($movementPoint = $this->getMovementPointByDepartment($department)) {
            $number = 0;
            foreach ($this->getMovementPoints() as $movPoint) {
                if ($movPoint->getRowNumber() > $number) {
                    $number = $movPoint->getRowNumber();
                }
            }
            if ($number === $movementPoint->getRowNumber()) {
                if (true === $movementPoint->getActive()) {
                    $output = true;
                }
            }
        }

        return $output;
    }

    public function getDriverOne(): ?Driver
    {
        return $this->driverOne;
    }

    public function setDriverOne(?Driver $driverOne): self
    {
        $this->driverOne = $driverOne;

        return $this;
    }

    public function getDriverTwo(): ?Driver
    {
        return $this->driverTwo;
    }

    public function setDriverTwo(?Driver $driverTwo): self
    {
        $this->driverTwo = $driverTwo;

        return $this;
    }

    public function getPlanDateOfFirstPointArrive(): ?\DateTime
    {
        if ($this->planDateOfFirstPointArrive) {
            $this->planDateOfFirstPointArrive->setTimezone(new \DateTimeZone($this->planDateOfFirstPointArriveTz));
        }

        return $this->planDateOfFirstPointArrive;
    }

    public function getClearPlanDateOfFirstPointArrive(): ?\DateTime
    {
        return $this->planDateOfFirstPointArrive;
    }

    public function setPlanDateOfFirstPointArrive(?\DateTime $planDateOfFirstPointArrive): self
    {
        $this->planDateOfFirstPointArrive = $planDateOfFirstPointArrive;
        if ($this->planDateOfFirstPointArrive) {
            $this->setPlanDateOfFirstPointArriveTz($planDateOfFirstPointArrive->getTimezone());
        }

        return $this;
    }

    public function getPlanDateOfFirstPointArriveTz(): \DateTimeZone
    {
        return new \DateTimeZone($this->planDateOfFirstPointArriveTz);
    }

    public function setPlanDateOfFirstPointArriveTz(\DateTimeZone $timeZone): self
    {
        if (strpos($timeZone->getName(), ':')) {
            $this->planDateOfFirstPointArriveTz = $timeZone->getName();
        } else {
            $this->planDateOfFirstPointArriveTz = substr(Timezones::getGmtOffset($timeZone->getName()), 3);
        }

        return $this;
    }

    public function getPlanDateOfFirstPointLoading(): ?\DateTime
    {
        if ($this->planDateOfFirstPointLoading) {
            $this->planDateOfFirstPointLoading->setTimezone(new \DateTimeZone($this->planDateOfFirstPointLoadingTz));
        }

        return $this->planDateOfFirstPointLoading;
    }

    public function getClearPlanDateOfFirstPointLoading(): ?\DateTime
    {
        return $this->planDateOfFirstPointLoading;
    }

    public function setPlanDateOfFirstPointLoading(?\DateTime $planDateOfFirstPointLoading): self
    {
        $this->planDateOfFirstPointLoading = $planDateOfFirstPointLoading;
        if ($this->planDateOfFirstPointLoading) {
            $this->setPlanDateOfFirstPointLoadingTz($planDateOfFirstPointLoading->getTimezone());
        }

        return $this;
    }

    public function getPlanDateOfFirstPointLoadingTz(): \DateTimeZone
    {
        return new \DateTimeZone($this->planDateOfFirstPointLoadingTz);
    }

    public function setPlanDateOfFirstPointLoadingTz(\DateTimeZone $timeZone): self
    {
        if (strpos($timeZone->getName(), ':')) {
            $this->planDateOfFirstPointLoadingTz = $timeZone->getName();
        } else {
            $this->planDateOfFirstPointLoadingTz = substr(Timezones::getGmtOffset($timeZone->getName()), 3);
        }

        return $this;
    }

    public function getFactDateOfFirstPointArrive(): ?\DateTime
    {
        if ($this->factDateOfFirstPointArrive) {
            $this->factDateOfFirstPointArrive->setTimezone(new \DateTimeZone($this->factDateOfFirstPointArriveTz));
        }

        return $this->factDateOfFirstPointArrive;
    }

    public function getClearFactDateOfFirstPointArrive(): ?\DateTime
    {
        return $this->factDateOfFirstPointArrive;
    }

    public function setFactDateOfFirstPointArrive(\DateTime $factDateOfFirstPointArrive): self
    {
        $this->factDateOfFirstPointArrive = $factDateOfFirstPointArrive;
        if ($this->factDateOfFirstPointArrive) {
            $this->setFactDateOfFirstPointArriveTz($factDateOfFirstPointArrive->getTimezone());
        }

        return $this;
    }

    public function getFactDateOfFirstPointArriveTz(): \DateTimeZone
    {
        return new \DateTimeZone($this->factDateOfFirstPointArriveTz);
    }

    public function setFactDateOfFirstPointArriveTz(\DateTimeZone $timeZone): self
    {
        if (strpos($timeZone->getName(), ':')) {
            $this->factDateOfFirstPointArriveTz = $timeZone->getName();
        } else {
            $this->factDateOfFirstPointArriveTz = substr(Timezones::getGmtOffset($timeZone->getName()), 3);
        }

        return $this;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * @return Collection|RouteContainer[]
     */
    public function getRouteContainers(): Collection
    {
        return $this->routeContainers;
    }

    public function addRouteContainer(RouteContainer $routeContainer): self
    {
        if (!$this->routeContainers->contains($routeContainer)) {
            $this->routeContainers[] = $routeContainer;
            $routeContainer->setRoute($this);
        }

        return $this;
    }

    public function removeRouteContainer(RouteContainer $routeContainer): self
    {
        if ($this->routeContainers->contains($routeContainer)) {
            $this->routeContainers->removeElement($routeContainer);
            // set the owning side to null (unless already changed)
            if ($routeContainer->getRoute() === $this) {
                $routeContainer->setRoute(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|RouteDisclaimer[]
     */
    public function getRouteDisclaimers(): Collection
    {
        return $this->routeDisclaimers;
    }

    public function addRouteDisclaimer(RouteDisclaimer $routeDisclaimer): self
    {
        if (!$this->routeDisclaimers->contains($routeDisclaimer)) {
            $this->routeDisclaimers[] = $routeDisclaimer;
            $routeDisclaimer->setRoute($this);
        }

        return $this;
    }

    public function removeRouteDisclaimer(RouteDisclaimer $routeDisclaimer): self
    {
        if ($this->routeDisclaimers->contains($routeDisclaimer)) {
            $this->routeDisclaimers->removeElement($routeDisclaimer);
            // set the owning side to null (unless already changed)
            if ($routeDisclaimer->getRoute() === $this) {
                $routeDisclaimer->setRoute(null);
            }
        }

        return $this;
    }

    public function getNaRouteOwner(): ?RouteOwner
    {
        return $this->naRouteOwner;
    }

    public function setNaRouteOwner(?RouteOwner $naRouteOwner): self
    {
        $this->naRouteOwner = $naRouteOwner;

        return $this;
    }

    public function getNaRouteUser(): ?CustomerUser
    {
        return $this->naRouteUser;
    }

    public function setNaRouteUser(?CustomerUser $naRouteUser): self
    {
        $this->naRouteUser = $naRouteUser;

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

    public function getIsDirty(): ?bool
    {
        return $this->isDirty;
    }

    public function setIsDirty(bool $isDirty): self
    {
        $this->isDirty = $isDirty;

        return $this;
    }

    public function getAuction(): ?Auction
    {
        return $this->auction;
    }

    public function setAuction(?Auction $auction): self
    {
        $this->auction = $auction;

        return $this;
    }

    public function getHaulerBlocked(): ?bool
    {
        return $this->haulerBlocked;
    }

    public function setHaulerBlocked(bool $haulerBlocked): self
    {
        $this->haulerBlocked = $haulerBlocked;

        return $this;
    }

    public function getRoutePointsLimitation()
    {
        return $this->routePointsLimitation;
    }

    public function setRoutePointsLimitation($routePointsLimitation): self
    {
        $this->routePointsLimitation = $routePointsLimitation;

        return $this;
    }

    public function getRouteDocuments()
    {
        return $this->routeDocuments;
    }

    public function setRouteDocuments($routeDocuments): self
    {
        $this->routeDocuments = $routeDocuments;

        return $this;
    }

    public function getRouteFreight()
    {
        return $this->routeFreight;
    }

    public function setRouteFreight($routeFreight): self
    {
        $this->routeFreight = $routeFreight;

        return $this;
    }

    public function getTender(): ?Tender
    {
        return $this->tender;
    }

    public function setTender(?Tender $tender): self
    {
        $this->tender = $tender;

        return $this;
    }

    public function getRouteTemplate(): ?RouteTemplate
    {
        return $this->routeTemplate;
    }

    public function setRouteTemplate(?RouteTemplate $routeTemplate): self
    {
        $this->routeTemplate = $routeTemplate;

        return $this;
    }

    public function getFreightSumm(): ?string
    {
        return $this->freightSumm;
    }

    public function setFreightSumm(?string $freightSumm): self
    {
        $this->freightSumm = $freightSumm;

        return $this;
    }

    public function getLateness(): ?bool
    {
        return $this->lateness;
    }

    public function setLateness(bool $lateness): self
    {
        $this->lateness = $lateness;

        return $this;
    }

    public function getInitialSumm(): ?string
    {
        return $this->initialSumm;
    }

    public function setInitialSumm(?string $initialSumm): self
    {
        $this->initialSumm = $initialSumm;

        return $this;
    }

    public function getAuctionRoute(): ?AuctionRoute
    {
        return $this->auctionRoute;
    }

    public function setAuctionRoute(AuctionRoute $auctionRoute): self
    {
        $this->auctionRoute = $auctionRoute;

        // set the owning side of the relation if necessary
        if ($auctionRoute->getRoute() !== $this) {
            $auctionRoute->setRoute($this);
        }

        return $this;
    }

    public function getEditedOnEtp(): ?bool
    {
        return $this->editedOnEtp;
    }

    public function setEditedOnEtp(?bool $editedOnEtp): self
    {
        $this->editedOnEtp = $editedOnEtp;

        return $this;
    }

    public function getRouteCurrentState(): ?RouteCurrentState
    {
        return $this->routeCurrentState;
    }

    public function setRouteCurrentState(?RouteCurrentState $routeCurrentState): self
    {
        $this->routeCurrentState = $routeCurrentState;

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
            $cargoPipeline->setRoute($this);
        }

        return $this;
    }

    public function removeCargoPipeline(CargoPipeline $cargoPipeline): self
    {
        if ($this->cargoPipelines->contains($cargoPipeline)) {
            $this->cargoPipelines->removeElement($cargoPipeline);
            // set the owning side to null (unless already changed)
            if ($cargoPipeline->getRoute() === $this) {
                $cargoPipeline->setRoute(null);
            }
        }

        return $this;
    }

        public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setOrganization(?Organization $organization): self
    {
        $this->organization = $organization;

        return $this;
    }
}

<?php

namespace App\Service\Route\Actions;

use App\Classes\StaticStorage\UpdatedFrom;
use App\Dto\Route\CommandRouteDto;
use App\Entity\Agreement\Organization;
use App\Entity\CustomerUser;
use App\Entity\Route\Route;
use App\Entity\Route\Transportation;
use App\Entity\Route\VehicleOrder;
use App\Entity\Tender\RouteTemplate;
use App\Exceptions\WrongObjectException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class ActionsCommandRouteService extends ActionsRouteService
{
    public function createRoute(CommandRouteDto $routeDto, RouteTemplate $routeTemplate): ?Route
    {
        $route = new Route();
        $routeDto->createFieldSet($route);
        if ($routeTemplate->getTransportation()) {
            $route->setTransportation($routeTemplate->getTransportation());
        }
        $route->setNaRouteOwner($routeTemplate->getRouteOwner());
        $route->setNaRouteUser($routeTemplate->getTender()->getNaTenderUser());
        $route->setFreightSumm($this->calcFreightSumm($routeTemplate));
        $routeFreight = [
            'tariffTypeGuid' => '00000000-0000-0000-0000-000000000000',
            'typeOfTariffGuid' => '00000000-0000-0000-0000-000000000000',
            'freightCost' => 0,
            'numberIntraCityTransitPoints' => 0,
            'numberLongDistanceTransitPoints' => 0,
            'costIntraCityTransit' => 0,
            'costLongDistanceTransit' => 0,
            'individualTariff' => true,
            'routeFreightOtherExpenses' => [
                'stringKey' => 'routeFreightReasonOtherExpenses',
                'items' => [
                    [
                        'reasonGuid' => '50795fb2-cf41-47ea-bea6-3bd70a3c804e',
                        'cost' => $route->getFreightSumm(),
                        'reasonDescription' => null,
                    ]
                ]
            ],
            'freightSum' => $route->getFreightSumm(),
            'userId' => $route->getUserId(),
        ];
        $directionsOfLoading = $this->generateDirectionsOfLoading($routeTemplate->getRouteWay());
        $route->setDirectionsOfLoading($directionsOfLoading);
        $route->setRouteFreight($routeFreight);
        $route->setRouteTemplate($routeTemplate);
        $route->setHaulerBlocked(true);
        $route->setIsLinked(true);
        $organization = $routeTemplate->getTender()->getOrganization();
        if (!$organization) {
            /** @var Organization $organization */
            $organization = $this->entityManager->getRepository(Organization::class)
                ->find(Organization::DRIVER_DEFAULT_ORGANIZATION);
        }
        $route->setOrganization($organization);
        $this->setLoadingDate($route, $routeDto);
        $this->setUserId($route);
        $this->setRouteOwner($route, $routeDto);
        $this->setVehicleOrder($route);
        $this->setRouteCustomer($route);
        if (!$route->getOrderDate()) {
            $route->setOrderDate($route->getVehicleOrder()->getCreatedOn());
        }
        $this->setRouteWay($route, $routeDto);
        $wayPoints = $route->getRouteWay()->getRouteWayPoints();
        if ($wayPoints) {
            $this->setArriveTimeZone($route, $wayPoints);
        } else {
            throw new WrongObjectException('В маршруте не указаны точки.');
        }
        // Установка путевых точек из маршрута
        if (0 === count($route->getMovementPoints())) {
            $this->setParentMovementPoints($route, $wayPoints);
        }
        $route->setRouteDate($route->getPlanDateOfFirstPointArrive());
        $this->setRouteCode($route, $routeDto);
        $this->setTransportationType($route);
        $this->setTransportation($route);
        $route->setOrderDate($routeDto->nowOrInit());
        $route->setCreatedOn($routeDto->nowOrInit());
        $route->setUpdatedOn($routeDto->nowOrInit());
        $this->updateVehicleOrder($route);
        $route->setInitialSumm($route->getFreightSumm());

        return $route;
    }

    /**
     * Пересчет суммы фрахта для рейса.
     */
    protected function calcFreightSumm(RouteTemplate $routeTemplate): string
    {
        if ($winner = $routeTemplate->getTender()->getWinner()) {
            $contractorVat = $winner->getVat();
            $tenderVat = $routeTemplate->getTender()->isWithNDS();
            if ($contractorVat === $tenderVat) {
                $freightSumm = round($routeTemplate->getWinRouteSumm());
            } else {
                if (true === $contractorVat) {
                    $freightSumm = bcmul($routeTemplate->getWinRouteSumm(), '1.2', 0);
                } else {
                    $freightSumm = bcdiv($routeTemplate->getWinRouteSumm(), '1.2', 0);
                }
            }
        } else {
            throw new WrongObjectException('Не установлен победитель у данного тендера');
        }

        return $freightSumm;
    }

    /**
     * Установка планируемой даты загрузки при необходимости.
     *
     * @param Route $route
     * @param CommandRouteDto $routeDto
     * @throws \Exception
     */
    protected function setLoadingDate(Route $route, CommandRouteDto $routeDto)
    {
        if (!$routeDto->getPlanDateOfFirstPointLoading()) {
            if ($route->getPlanDateOfFirstPointArrive()) {
                $loadingDate = new \DateTime($route->getPlanDateOfFirstPointArrive()->format('c'));
                $loadingDate->add(new \DateInterval('PT1H'));
                $route->setPlanDateOfFirstPointLoading($loadingDate);
            } else {
                throw new WrongObjectException('Плановая дата прибытия в первую точку маршрута не может быть пустой.');
            }
        }
    }

    /**
     * Добавление UserId.
     *
     * @param Route $route
     */
    protected function setUserId(Route $route)
    {
        if ($this->container->get('security.token_storage')->getToken()) {
            $user = $this->container->get('security.token_storage')->getToken()->getUser();
            if ($user instanceof CustomerUser) {
                $route->setNaRouteUser($user);
            }
        }
    }

    /**
     * Установка мэнеджера STL.
     *
     * @param Route $route
     * @param CommandRouteDto $routeDto
     */
    protected function setRouteOwner(Route $route, CommandRouteDto $routeDto)
    {
        if ($routeDto->getRouteOwner()) {
            $routeOwner = $this->finderHelper->findRouteOwner($routeDto->getRouteOwner());
            if ($routeOwner) {
                $route->setNaRouteOwner($routeOwner);
            }
        }
    }

    /**
     * Устанавливает Заказ ТС для рейса.
     *
     * @param Route $route
     *
     * @throws \Exception
     */
    protected function setVehicleOrder(Route $route)
    {
        $this->createVehicleOrder($route);
    }

    /**
     * Создание нового ЗТС.
     *
     * @param Route              $route
     * @param UuidInterface|null $guid
     *
     * @throws \Exception
     */
    protected function createVehicleOrder(Route $route, ?UuidInterface $guid = null)
    {
        $vehicleOrder = new VehicleOrder();
        if ($guid) {
            $vehicleOrder->setGuid($guid->toString());
        } else {
            $vehicleOrder->setGuid(Uuid::uuid4()->toString());
        }
        $vehicleOrder->setRoute($route);
        $vehicleOrder->setCreatedOn(new \DateTime());
        $vehicleOrder->setUpdatedOn(new \DateTime());
    }

    /**
     * Установка маршрута для рейса.
     *
     * @param Route $route
     * @param CommandRouteDto $routeDto
     */
    protected function setRouteWay(Route $route, CommandRouteDto $routeDto)
    {
        if ($routeWay = $this->finderHelper->findRouteWay($routeDto->getRouteWay())) {
            $route->setRouteWay($routeWay);
        } else {
            throw new WrongObjectException('Не удалось добавить маршрут к рейсу. Маршрут не найден.');
        }
    }

    /**
     * Установка кода рейса.
     *
     * @param Route $route
     * @param CommandRouteDto $routeDto
     */
    protected function setRouteCode(Route $route, CommandRouteDto $routeDto)
    {
        if (UpdatedFrom::UPDATED_FROM_ETP === $routeDto->getUpdatedFrom()) {
            if (!$route->getRouteCode()) {
                $route->setRouteCode(
                    $route->getRouteDate()->format('dm').$route->getRouteWay()->getCode()
                );
            }
        }
    }

    /**
     * Установка параметра вида перевозки.
     *
     * @param Route $route
     */
    protected function setTransportationType(Route $route)
    {
        $route->setTransportationType($route->getRouteWay()->getTransportationType());
    }

    /**
     * Установка типа перевозки.
     *
     * @param Route $route
     */
    protected function setTransportation(Route $route)
    {
        $defaultTransportation = $this->getDefaultTransportation();
        $route->setTransportation($defaultTransportation);
    }

    /**
     * Получение типа перевозки по умолчанию.
     *
     * @return Transportation
     */
    protected function getDefaultTransportation(): Transportation
    {
        //TODO - механизм поиска типа перевозки не выглядит надежным
        $defaultTransportation = $this->entityManager->getRepository(Transportation::class)
            ->findOneBy(['guid' => '4e84c200-31d7-11e8-80c9-00155d668927']);

        return $defaultTransportation;
    }
}

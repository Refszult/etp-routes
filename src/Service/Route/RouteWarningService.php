<?php

namespace App\Service\Route;

use App\Entity\Driver\ContractorDriver;
use App\Entity\Driver\Driver;
use App\Entity\Route\Route;
use App\Entity\Route\RouteContainer;
use App\Entity\Route\TransportationType;
use App\Entity\Vehicle\ContractorVehicle;
use App\Entity\Vehicle\Vehicle;
use Doctrine\ORM\EntityManagerInterface;

class RouteWarningService
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    /**
     * Проверка на предупреждения по рейсу.
     *
     * @param Route $route
     */
    public function checkRouteWarnings(Route $route)
    {
        if ($route->getContractor()) {
            if ($route->getDriverOne()) {
                $this->checkDriverRouteWarnings('driverOne', $route->getDriverOne(), $route);
            }
            if ($route->getDriverTwo()) {
                $this->checkDriverRouteWarnings('driverTwo', $route->getDriverTwo(), $route);
            }
            if ($route->getTransport()) {
                $this->checkWehicleRouteWarnings('transport', $route->getTransport(), $route);
                if (!$route->getTransport()->getModel()->getType()->getIsTruck()) {
                    $route->addWarning('transport', 'Данное ТС не является автомобилем.');
                }
            }
            if ($route->getTrailer()) {
                $this->checkWehicleRouteWarnings('trailer', $route->getTrailer(), $route);
                if ($route->getTrailer()->getModel()->getType()->getIsTruck()) {
                    $route->addWarning('trailer', 'Выбранное ТС не является прицепом.');
                }
                if (!$route->getTransport()) {
                    $route->addWarning('trailer', 'Добавлен прицеп без тягача.');
                }
            }
//            if ($route->getTransport() || $route->getTrailer()) {
//                $this->checkAuctionDimensionRouteWarnings('dimension', $route);
//                $this->checkTenderDimensionRouteWarnings('dimension', $route);
//            }
        }
    }

    /**
     * Обработка предупреждений по тонажу и объёму для аукциона.
     *
     * @param string $key
     * @param Route  $route
     */
    protected function checkAuctionDimensionRouteWarnings(string $key, Route $route)
    {
        if ($auction = $route->getAuction()) {
            $transport = $route->getTransport();
            $trailer = $route->getTrailer();
            if ($transport || $trailer) {
                /** @var RouteContainer[] $routeContainers */
                $routeContainers = $route->getRouteContainers();
                $loadCapacity = 0;
                $volume = 0;
                if ($transport) {
                    $loadCapacity += $transport->getModel()->getLoadCapacity();
                    $volume += $transport->getModel()->getLoadCapacity();
                }
                if ($trailer) {
                    $loadCapacity += $trailer->getModel()->getLoadCapacity();
                    $volume += $trailer->getModel()->getLoadCapacity();
                }
                if ($routeContainers) {
                    foreach ($routeContainers as $routeContainer) {
                        $loadCapacity += $routeContainer->getContainer()->getMaximumPayload();
                    }
                }
                if ($dimension = $auction->getDimension()) {
                    if (!$loadCapacity) {
                        $loadCapacity = 0;
                    }
                    if (!$volume) {
                        $volume = 0;
                    }
                    if ($dimension->getWeight() > $loadCapacity || $dimension->getVolume() > $volume) {
                        $route->addWarning($key, 'Данное ТС не соответствует требованиям по тонажу или объёму..');
                    }
                }
            }
        }
    }

    /**
     * Обработка предупреждений по тонажу и объёму.
     *
     * @param string $key
     * @param Route  $route
     */
    protected function checkTenderDimensionRouteWarnings(string $key, Route $route)
    {
        if ($routeTemplate = $route->getRouteTemplate()) {
            $transport = $route->getTransport();
            $trailer = $route->getTrailer();
            $loadCapacity = 0;
            $volume = 0;
            if ($transport) {
                $loadCapacity += $transport->getModel()->getLoadCapacity();
                $volume += $transport->getModel()->getLoadCapacity();
            }
            if ($trailer) {
                $loadCapacity += $trailer->getModel()->getLoadCapacity();
                $volume += $trailer->getModel()->getLoadCapacity();
            }
            if ($dimension = $routeTemplate->getDimension()) {
                if ($loadCapacity || $volume) {
                    if ($dimension->getWeight() > $loadCapacity || $dimension->getVolume() > $volume) {
                        $route->addWarning($key, 'Данное ТС не соответствует требованиям по тонажу или объёму..');
                    }
                }
            }
        }
    }

    /**
     * Обработка предупреждений по рейсу для ТС.
     *
     * @param string  $key
     * @param Vehicle $vehicle
     * @param Route   $route
     */
    protected function checkWehicleRouteWarnings(string $key, Vehicle $vehicle, Route $route)
    {
        if ($vehicle->getIsCancel()) {
            $route->addWarning($key, 'Данное ТС помечено на удаление.');
        }
        if ($vehicle->getBlockedSecurity()) {
            $route->addWarning($key, 'Данное ТС заблокировано ТС.');
        }
        $contractorVehicle = $this->entityManager->getRepository(ContractorVehicle::class)
            ->findOneBy(['vehicle' => $vehicle->getId(), 'contractor' => $route->getContractor()->getId()]);
        if (!$contractorVehicle) {
            $route->addWarning($key, 'Данное ТС не числится у подрядчика.');
        }
    }

    /**
     * Обработка предупреждений по рейсу для водителей.
     *
     * @param string $key
     * @param Driver $driver
     * @param Route  $route
     */
    protected function checkDriverRouteWarnings(string $key, Driver $driver, Route $route)
    {
        if ($driver->getIsCancel()) {
            $route->addWarning($key, 'Данный водитель помечен на удаление.');
        }
        if ($driver->getBlockedSecurity()) {
            $route->addWarning($key, 'Данный водитель заблокирован СБ.');
        }
        $contractorDriver = $this->entityManager->getRepository(ContractorDriver::class)
            ->findOneBy(['driver' => $driver->getId(), 'contractor' => $route->getContractor()->getId()]);
        if ($contractorDriver) {
            if (!$contractorDriver->getActiveAttorney()) {
                $route->addWarning($key, 'Данный водитель не имеет активной доверенности у подрядчика.');
            }
            if ($route->getCargoFlow()) {
                if (!$driver->getDriverAccessCargoFlow()) {
                    $route->addWarning($key, 'Данный водитель не имеет разрешения на работу с грузопроводом.');
                }
            }
            if ($route->getTransportationType()) {
                if (TransportationType::TRANSPORTATION_TYPE_AUTO === $route->getTransportationType()->getGuid()->toString()) {
                    if (!$driver->getDriverAccessAuto()) {
                        $route->addWarning($key, 'Данный водитель не имеет разрешения на работу с автоперевозками.');
                    }
                }
                if (TransportationType::TRANSPORTATION_TYPE_TRAIN === $route->getTransportationType()->getGuid()->toString()) {
                    if (!$driver->getDriverAccessContainer()) {
                        $route->addWarning($key, 'Данный водитель не имеет разрешения на работу с ЖД перевозками.');
                    }
                }
            }
        } else {
            $route->addWarning($key, 'Данный водитель не числится у выбранного подрядчика.');
        }
    }
}

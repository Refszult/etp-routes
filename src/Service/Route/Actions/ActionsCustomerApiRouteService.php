<?php

namespace App\Service\Route\Actions;

use App\Classes\StaticStorage\UpdatedFrom;
use App\Dto\Route\ApiCustomerRouteDto;
use App\Entity\Auction\Auction;
use App\Entity\Customer;
use App\Entity\CustomerContractor;
use App\Entity\CustomerUser;
use App\Entity\Route\Route;
use App\Entity\Route\Transportation;
use App\Entity\Route\TransportationType;
use App\Exceptions\NotRelativeMQException;
use App\Exceptions\WrongObjectException;
use Ramsey\Uuid\Uuid;

class ActionsCustomerApiRouteService extends ActionsRouteService
{
    /**
     * Создание рейса со стороны заказчика.
     *
     * @param ApiCustomerRouteDto $routeDto
     * @param Customer $customer
     * @return Route|null
     * @throws \Exception
     */
    public function createRoute(ApiCustomerRouteDto $routeDto, Customer $customer): ?Route
    {
        $route = new Route();
        $routeDto->createFieldSet($route);
        $this->createVehicleOrder($route);
        $route->setGuid(Uuid::uuid4()->toString());
        $this->setLoadingDate($route, $routeDto);
        $this->setUserId($route);
        $this->setNaRouteOwner($route, $routeDto);
        $wayPoints = $route->getRouteWay()->getRouteWayPoints();
        $this->setArriveTimeZone($route, $wayPoints);
        // Установка путевых точек из маршрута
        if (0 === count($route->getMovementPoints())) {
            $this->setParentMovementPoints($route, $wayPoints);
        }
        $this->setOrganization($route, $routeDto);
        $route->setCustomer($customer);
        $this->setTransportationType($route, $routeDto->getTransportationType());
        $this->setTransportation($route, $routeDto->getTransportation());
        // Установка пользовательских полей
        $this->setContractor($route, $routeDto);
        if ($route->getContractor()) {
            $this->setDriverOne($route, $routeDto);
            $this->setDriverTwo($route, $routeDto);
            $this->setCar($route, $routeDto);
            $this->setTrailer($route, $routeDto);
            $this->setRouteContainers($route, $routeDto);
        }
        $routeFreight = [
            'tariffTypeGuid' => '00000000-0000-0000-0000-000000000000',
            'typeOfTariffGuid' => '00000000-0000-0000-0000-000000000000',
            'freightCost' => 0,
            'numberIntraCityTransitPoints' => 0,
            'numberLongDistanceTransitPoints' => 0,
            'costIntraCityTransit' => 0,
            'costLongDistanceTransit' => 0,
            'individualTariff' => false,
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
        $route->setRouteFreight($routeFreight);
        $route->setInitialSumm($route->getFreightSumm());
        $directionsOfLoading = $this->generateDirectionsOfLoading($routeDto->getRouteWay());
        $route->setDirectionsOfLoading($directionsOfLoading);
        $route->setRouteDate($route->getPlanDateOfFirstPointArrive());
        $this->setRouteCode($route, $routeDto);
        $this->setCargoPipeline($route, $routeDto);
        $route->setCreatedOn($routeDto->nowOrInit());
        $route->setUpdatedOn($routeDto->nowOrInit());
        $this->updateVehicleOrder($route);
        $route->setOrderDate($routeDto->nowOrInit());

        return $route;
    }

    /**
     * Обновление рейса со стороны заказчика.
     *
     * @param Route $route
     * @param ApiCustomerRouteDto $routeDto
     * @return Route|null
     * @throws \Exception
     */
    public function updateRoute(Route $route, ApiCustomerRouteDto $routeDto): ?Route
    {
        if ($this->canEditExtend($route, $routeDto)) {
            $routeDto->updateFieldSet($route);
            $this->setLoadingDate($route, $routeDto);
            $this->setUserId($route);
            $this->setNaRouteOwner($route, $routeDto);
            $wayPoints = $route->getRouteWay()->getRouteWayPoints();
            $this->setArriveTimeZone($route, $wayPoints);
            // Установка путевых точек из маршрута
            if (0 === count($route->getMovementPoints())) {
                $this->setParentMovementPoints($route, $wayPoints);
            }
            $this->setRouteCode($route, $routeDto);

            // Установка пользовательских полей
            $this->setContractor($route, $routeDto);
            if ($route->getContractor()) {
                $this->setDriverOne($route, $routeDto);
                $this->setDriverTwo($route, $routeDto);
                $this->setCar($route, $routeDto);
                $this->setTrailer($route, $routeDto);
                $this->setRouteContainers($route, $routeDto);
            }

            $route->setUpdatedOn($routeDto->nowOrInit());
            $this->updateVehicleOrder($route);
            $this->setCargoPipeline($route, $routeDto);
            if ($route->getAuction() || $route->getTender()) {
                $this->checkRouteVolume($route, $routeDto);
            }
        } else {
            throw new WrongObjectException('Не все переданные поля можно редактировать в данном статусе.');
        }

        return $route;
    }

    /**
     * Установка параметра вида перевозки.
     *
     * @throws NotRelativeMQException
     */
    protected function setTransportationType(Route $route, ?TransportationType $transportationType)
    {
        if ($transportationType) {
            $transportationType = $this->finderHelper->findTransportationType($transportationType);
            $route->setTransportationType($transportationType);
        } else {
            $defaultTransportationType = $this->getDefaultTransportationType();
            $route->setTransportationType($defaultTransportationType);
        }
    }

    /**
     * Поиск типа перевозки.
     *
     * @throws NotRelativeMQException
     */
    protected function setTransportation(Route $route, ?Transportation $transportation)
    {
        if ($transportation) {
            $transportation = $this->finderHelper->findTransportation($transportation);
            $route->setTransportation($transportation);
        } else {
            $defaultTransportation = $this->getDefaultTransportation();
            $route->setTransportation($defaultTransportation);
        }
    }

    /**
     * Установка планируемой даты загрузки при необходимости.
     *
     * @param Route $route
     * @param ApiCustomerRouteDto $routeDto
     * @throws \Exception
     */
    protected function setLoadingDate(Route $route, ApiCustomerRouteDto $routeDto)
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
     * Проверка на возможность обновления полей исходя из статусов аукциона и тендера.
     *
     * @param Route $route
     * @param ApiCustomerRouteDto $routeDto
     * @return bool
     */
    protected function canEditExtend(Route $route, ApiCustomerRouteDto $routeDto)
    {
        $output = true;
        if ($auction = $route->getAuction()) {
            $output = false;
            if (Auction::STATUS_CLOSED === $auction->getStatus()) {
                $output = true;
            } elseif (Auction::STATUS_DRAFT === $auction->getStatus()) {
                $output = true;
                if ($routeDto->getContractor()) {
                    if ($route->getContractor()) {
                        if ($routeDto->getContractor() !== $route->getContractor()->getId()) {
                            $output = false;
                        }
                    } else {
                        $output = false;
                    }
                }
                if ($routeDto->getDriverOne()) {
                    if ($route->getDriverOne()) {
                        if ($routeDto->getDriverOne()->getId() !== $route->getDriverOne()->getId()) {
                            $output = false;
                        }
                    } else {
                        $output = false;
                    }
                }
                if ($routeDto->getDriverTwo()) {
                    if ($route->getDriverTwo()) {
                        if ($routeDto->getDriverTwo()->getId() !== $route->getDriverTwo()->getId()) {
                            $output = false;
                        }
                    } else {
                        $output = false;
                    }
                }
                if ($routeDto->getTransport()) {
                    if ($route->getTransport()) {
                        if ($routeDto->getTransport()->getId() !== $route->getTransport()->getId()) {
                            $output = false;
                        }
                    } else {
                        $output = false;
                    }
                }
                if ($routeDto->getTrailer()) {
                    if ($route->getTrailer()) {
                        if ($routeDto->getTrailer()->getId() !== $route->getTrailer()->getId()) {
                            $output = false;
                        }
                    } else {
                        $output = false;
                    }
                }
                if ($routeDto->getRouteContainers()) {
                    $output = false;
                }
            }
        }

        return $output;
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
     * @param ApiCustomerRouteDto $routeDto
     */
    protected function setNaRouteOwner(Route $route, ApiCustomerRouteDto $routeDto)
    {
        if ($routeDto->getNaRouteOwner()) {
            $routeOwner = $this->finderHelper->findRouteOwner($routeDto->getNaRouteOwner());
            if ($routeOwner) {
                $route->setNaRouteOwner($routeOwner);
            }
        }
    }

    /**
     * Установка кода рейса.
     *
     * @param Route $route
     */
    protected function setRouteCode(Route $route, ApiCustomerRouteDto $routeDto)
    {
        if (UpdatedFrom::UPDATED_FROM_ETP === $routeDto->getUpdatedFrom()) {
            if (!$route->getRouteCode()) {
                $route->setRouteCode(
                    $route->getRouteDate()->format('dm') . $route->getRouteWay()->getCode()
                );
            }
        }
    }

    /**
     * Установка перевозчика.
     *
     * @param Route $route
     * @param ApiCustomerRouteDto $routeDto
     */
    protected function setContractor(Route $route, ApiCustomerRouteDto $routeDto)
    {
        if ($routeDto->getContractor()) {
            if ($contractor = $this->finderHelper->findContractor($routeDto->getContractor())) {
                $customerContractor = $this->entityManager->getRepository(CustomerContractor::class)
                    ->findOneBy([
                        'customer' => $route->getCustomer()->getId(),
                        'contractor' => $contractor->getId(),
                        'verificationStatus' => CustomerContractor::VERIFICATION_ALLOWED,
                    ]);
                if ($customerContractor) {
                    $route->setContractor($contractor);
                } else {
                    throw new WrongObjectException('Переданный перевозчик не найден или не верифицирован.');
                }
            } else {
                throw new WrongObjectException('Переданного перевозчика не существует.');
            }
        } else {
            $route->setContractor(null);
            $route->setDriverOne(null);
            $route->setDriverTwo(null);
            $transport = $route->getTransport();
            if ($transport) {
                $route->setTransport(null);
                $this->clearContainers($route, $transport);
            }
            $trailer = $route->getTrailer();
            if ($trailer) {
                $route->setTrailer(null);
                $this->clearContainers($route, $trailer);
            }
        }
    }

    public function generateAuctionRoutesJson(Route $route)
    {
        $planDateOfFirstPointArrive = str_replace(" ", "T", $route->getPlanDateOfFirstPointArrive()->format('Y-m-d H:i:sP'));
        $planDateOfFirstPointLoading = str_replace(" ", "T", $route->getPlanDateOfFirstPointLoading()->format('Y-m-d H:i:sP'));

        return json_encode([
            'auctionRoutes' =>
                [[
                    'route' => [
                        'id' => $route->getId(),
                        'planDateOfFirstPointArrive' => $planDateOfFirstPointArrive,
                        'planDateOfFirstPointLoading' => $planDateOfFirstPointLoading,
                        'boostFlag' => $route->getBoostFlag(),
                    ]
                ]]

        ]);
    }
}

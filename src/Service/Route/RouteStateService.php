<?php

namespace App\Service\Route;

use App\Entity\Route\Department;
use App\Entity\Route\Route;
use App\Entity\Route\RouteCurrentState;
use App\Entity\Route\RouteMovementPoint;
use App\Entity\Route\RouteStateLog;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;

/**
 * Class RouteStateService
 */
class RouteStateService
{
    public function __construct(
        EntityManagerInterface $entityManager,
        ContainerInterface $container
    )
    {
        $this->entityManager = $entityManager;
        $this->container = $container;
    }

    /**
     * Проверка валидности обновления актуального состояния рейса по сообщению CUR.
     * Если если информация о текущем состоянии и кол-во точек в нем больше 1, сообщение не актуально.
     * Если точка 1 и она не равна первой точке, сообщение не актуально.
     *
     * @param RouteMovementPoint $movementPoint
     * @param RouteCurrentState $routeState
     * @return bool
     */
    private function checkStateCUR(RouteMovementPoint $movementPoint, RouteCurrentState $routeState): bool
    {
        $isValid = true;
        if ($routeState->getId()) {
            if (1 < $routeState->getCurrentPoints()->count()) {
                $isValid = false;
            }
            if (0 != $routeState->getCurrentPoints()->count() && $routeState->getCurrentPoints()->first() != $movementPoint) {
                $isValid = false;
            }
            if (1 < $routeState->getStatus()) {
                $isValid = false;
            }
        }

        return $isValid;
    }

    /**
     * Проверка валидности обновления актуального состояния рейса по сообщению RM и RWO.
     * Если точка из сообщения по порядковому номеру рейса больше или равна точки текущего состояния,
     * то считать сообщение актуальным.
     * Если не было записей о аткульном состоянии рейса, так же считать сообщение актуальным
     *
     * @param RouteMovementPoint $movementPoint
     * @param RouteCurrentState $routeState
     * @return mixed
     */
    private function checkStateRMnRWO(RouteMovementPoint $movementPoint, RouteCurrentState $routeState)
    {
        // TODO: скорее всего придется дорабатывать эту проверку, на проверку еще и текущего статуса.
        $isValid = false;
        if ($routeState->getId()) {
            foreach ($routeState->getCurrentPoints() as $currentPoint) {
                if ($currentPoint->getRowNumber() <= $movementPoint->getRowNumber()) {
                    $isValid = true;
                }
            }
        } else {
            $isValid = true;
        }

        return $isValid;
    }

    /**
     * Проверка валидности обновления актуального состояния рейса по сообщению RM.
     * Если рейс закрыт, либо находится в стусах соответствующим действиями активности на последней точки,
     * из сообщений RWO, то считать сообщение неактуальным.
     *
     * @param RouteCurrentState $routeState
     * @return mixed
     */
    private function checkStateRM(RouteCurrentState $routeState)
    {
        $isValid = true;
        if ($routeState->getId()) {
            if ($routeState->getRoute()->getClosed()) {
                $isValid = false;
            }
            if (RouteCurrentState::STATUS_ON_UNLOADING == $routeState->getStatus() || RouteCurrentState::STATUS_CLOSED == $routeState->getStatus()) {
                $isValid = false;
            }
        }

        return $isValid;
    }

    /**
     * Создание/обновление записи о состоянии рейса при работе с первой точкой.
     * Вызывается при чтении сообщения CUR.
     *
     * @param Route $route
     */
    public function createUpdateRouteStateFromCUR(Route $route)
    {
        if ($route->getPlanDateOfFirstPointArrive()) {
            $firstPoint = null;
            /** @var RouteMovementPoint $movementPoint */
            foreach ($route->getMovementPoints() as $movementPoint) {
                if (1 == $movementPoint->getRowNumber()) {
                    $firstPoint = $movementPoint;
                }
            }
            $routeState = $this->getRouteState($route);
            if (null == $firstPoint || false == $this->checkStateCUR($firstPoint, $routeState)) {
                if (null == $route->getFactDateOfFirstPointArrive()) {
                    $this->createRouteStateLogEntry($route, $route->getPlanDateOfFirstPointArrive(), [$firstPoint], RouteStateLog::MESSAGE_CUR);
                } else {
                    $this->createRouteStateLogEntry($route, $route->getFactDateOfFirstPointArrive(), [$firstPoint], RouteStateLog::MESSAGE_CUR);
                }
                return;
            }
            if ($routeState->getId()) {
                $this->clearRouteStatePoints($routeState);
            }
            if (null == $route->getFactDateOfFirstPointArrive()) {
                $routeState->setStatus(RouteCurrentState::STATUS_AWAITING_ARRIVAL);
                $routeState->setArrivalTime($route->getPlanDateOfFirstPointArrive());
                $routeState->setCounter($route->getPlanDateOfFirstPointArrive());
                $this->createRouteStateLogEntry($route, $route->getPlanDateOfFirstPointArrive(), [$firstPoint], RouteStateLog::MESSAGE_CUR);
            } else {
                if ($route->getFactDateOfFirstPointArrive() < $route->getPlanDateOfFirstPointArrive()) {
                    $counter = $route->getPlanDateOfFirstPointArrive();
                } else {
                    $counter = $route->getFactDateOfFirstPointArrive();
                }
                $routeState->setStatus(RouteCurrentState::STATUS_AWAITING_LOADING);
                $routeState->setArrivalTime($route->getFactDateOfFirstPointArrive());
                $routeState->setCounter($counter);
                $this->createRouteStateLogEntry($route, $route->getFactDateOfFirstPointArrive(), [$firstPoint], RouteStateLog::MESSAGE_CUR);
            }
            $updatedOn = new \DateTime();
            $routeState->setUpdatedOn($updatedOn);
            $routeState->addCurrentPoints($firstPoint);
            $routeState->setIsRWOAction(false);

            $this->entityManager->persist($routeState);
        }
    }

    /**
     * Создание/обновление записи о состоянии рейса при работе с последующими точками.
     * Вызывается при чтении сообщения RouteMovement.
     *
     * @param Route $route
     * @param RouteMovementPoint $movementPoint
     * @param string $sourceDepGuid
     */
    public function createUpdateRouteStateFromRM(Route $route, RouteMovementPoint $movementPoint, string $sourceDepGuid)
    {
        $updatedOn = new \DateTime();
        $lastMovementPoint = $this->entityManager->getRepository(RouteMovementPoint::class)->getLastPointByRoute($route);
        $dateArrival = $movementPoint->getFactDateArrival();

        $routeState = $this->getRouteState($route);
        if (false == $this->checkStateRMnRWO($movementPoint, $routeState) || false == $this->checkStateRM($routeState)) {
            if ($movementPoint->getFactDateArrival() && $lastMovementPoint == $movementPoint) {
                $points = [$movementPoint];
                $this->createRouteStateLogEntry($route, $dateArrival, $points, RouteStateLog::MESSAGE_RM);
            } elseif ($movementPoint->getFactDateArrival()) {
                $points = [$movementPoint];
                $this->createRouteStateLogEntry($route, $dateArrival, $points, RouteStateLog::MESSAGE_RM);
            } else {
                $sourceDep = $this->entityManager->getRepository(Department::class)->findOneBy([
                    'guid' => $sourceDepGuid
                ]);
                $sourcePoint = $this->entityManager->getRepository(RouteMovementPoint::class)->findOneBy([
                    'department' => $sourceDep,
                    'route' => $route,
                    'active' => true
                ]);
                $points = [$sourcePoint, $movementPoint];
                $dateArrival = $movementPoint->getPlanDateArrival();
                $this->createRouteStateLogEntry($route, $dateArrival, $points, RouteStateLog::MESSAGE_RM);
            }
            return;
        }

        if ($routeState->getId()) {
            $this->clearRouteStatePoints($routeState);
        }

        $routeState->addCurrentPoints($movementPoint);
        if ($movementPoint->getFactDateArrival() && $lastMovementPoint == $movementPoint) {
            $routeState->setStatus(RouteCurrentState::STATUS_AWAITING_UNLOADING);
            $routeState->setArrivalTime($movementPoint->getFactDateArrival());
            $routeState->setCounter($movementPoint->getFactDateArrival());
            $points = [$movementPoint];
            $this->createRouteStateLogEntry($route, $dateArrival, $points, RouteStateLog::MESSAGE_RM);
        } elseif ($movementPoint->getFactDateArrival()) {
            $routeState->setStatus(RouteCurrentState::STATUS_AWAITING_LOADING_OR_UNLOADING);
            $routeState->setArrivalTime($movementPoint->getFactDateArrival());
            $routeState->setCounter($movementPoint->getFactDateArrival());
            $points = [$movementPoint];
            $this->createRouteStateLogEntry($route, $dateArrival, $points, RouteStateLog::MESSAGE_RM);
        } else {
            $sourceDep = $this->entityManager->getRepository(Department::class)->findOneBy([
                'guid' => $sourceDepGuid
            ]);
            $sourcePoint = $this->entityManager->getRepository(RouteMovementPoint::class)->findOneBy([
                'department' => $sourceDep,
                'route' => $route,
                'active' => true
            ]);
            $routeState->setStatus(RouteCurrentState::STATUS_AWAITING_ARRIVAL);
            $routeState->setArrivalTime($movementPoint->getPlanDateArrival());
            $routeState->setCounter($movementPoint->getPlanDateArrival());
            $routeState->addCurrentPoints($sourcePoint);
            $points = [$sourcePoint, $movementPoint];
            $dateArrival = $movementPoint->getPlanDateArrival();
            $this->createRouteStateLogEntry($route, $dateArrival, $points, RouteStateLog::MESSAGE_RM);
        }
        $routeState->setUpdatedOn($updatedOn);
        $routeState->setIsRWOAction(false);

        $this->entityManager->persist($routeState);
    }

    /**
     * Обновление записи о состоянии рейса при действиях совершенных на точках.
     * Вызывается при чтении сообщения RouteWarehouseOperation.
     *
     * @param Route $route
     * @param array $data
     * @throws \Exception
     */
    public function updateRouteStateFromRWO(Route $route, array $data)
    {
        $actionDate = new \DateTime($data['dataTimeAction']);
        $updatedOn = new \DateTime();
        $routeState = $this->getRouteState($route);
        $movementPoint = $this->getMovementPoint($route, $data['departmentGuid']);
        if (null == $movementPoint) {
            return;
        }
        $dateArrival = $movementPoint->getFactDateArrival() ? $movementPoint->getFactDateArrival() : $route->getFactDateOfFirstPointArrive();
        $this->createRouteStateLogEntry($route, $dateArrival, [$movementPoint], RouteStateLog::MESSAGE_RWO);
        if (false == $this->checkStateRMnRWO($movementPoint, $routeState)) {
            return;
        }

        if ($routeState->getId()) {
            $this->clearRouteStatePoints($routeState);
        }
        $routeState->addCurrentPoints($movementPoint);
        $currentPointNumber = $movementPoint->getRowNumber();
        $lastPointNumber = $route->getLastMovementPoint()->getRowNumber();
        if (false == $route->getClosed()) {
            if (1 == $routeState->getCurrentPoints()->count() && 1 == $currentPointNumber) {
                if (false != stristr($data['typeWarehouseOperationRoute'], "Начало") && "Погрузка" == $data['actionWarehouseOperationRoute']) {
                    $routeState->setStatus(RouteCurrentState::STATUS_IN_LOADING);
                }
            }
            if (1 == $routeState->getCurrentPoints()->count() && $lastPointNumber == $currentPointNumber) {
                if (false != stristr($data['typeWarehouseOperationRoute'], "Начало") && "Разгрузка" == $data['actionWarehouseOperationRoute']) {
                    $routeState->setStatus(RouteCurrentState::STATUS_ON_UNLOADING);
                }
            }
            if (false != stristr($data['typeWarehouseOperationRoute'], "Окончание") && "Погрузка" == $data['actionWarehouseOperationRoute'] || "Разгрузка" == $data['actionWarehouseOperationRoute']) {
                $actionDate = $routeState->getCounter();
            }
            if (1 == $routeState->getCurrentPoints()->count() && ($lastPointNumber != $currentPointNumber && 1 != $currentPointNumber)) {
                if (false != stristr($data['typeWarehouseOperationRoute'], "Начало") && ("Погрузка" == $data['actionWarehouseOperationRoute'] || "Разгрузка" == $data['actionWarehouseOperationRoute'])) {
                    $actionDate = new \DateTime($data['dataTimeAction']);
                    $routeState->setStatus(RouteCurrentState::STATUS_IN_LOADING_OR_UNLOADING);
                }
            }
        }
        if (true == $route->getClosed() && 1 == $routeState->getCurrentPoints()->count()) {
            if (false != stristr($data['typeWarehouseOperationRoute'], "Начало") && "Разгрузка" == $data['actionWarehouseOperationRoute']) {
                $routeState->setStatus(RouteCurrentState::STATUS_ON_UNLOADING);
            }
            if (false != stristr($data['typeWarehouseOperationRoute'], "Окончание") && "Разгрузка" == $data['actionWarehouseOperationRoute']) {
                $routeState->setStatus(RouteCurrentState::STATUS_CLOSED);
            }
        }
        $routeState->setArrivalTime($dateArrival);
        if (false == $routeState->getIsRWOAction()) {
            $routeState->setCounter($actionDate);
        }
        $routeState->setIsRWOAction(true);
        $routeState->setUpdatedOn($updatedOn);

        $this->entityManager->persist($routeState);
        $this->entityManager->flush();
    }

    /**
     * Поиск состояния рейса, если не найден - создается объект нового состояния.
     *
     * @param Route $route
     * @return mixed
     */
    private function getRouteState(Route $route): RouteCurrentState
    {
        $routeState = $this->entityManager->getRepository(RouteCurrentState::class)->findOneBy([
            'route' => $route
        ]);
        if (null == $routeState) {
            $createdOn = new \DateTime();
            $routeState = new RouteCurrentState();
            $routeState->setRoute($route);
            $routeState->setCreatedOn($createdOn);
        }

        return $routeState;
    }

    /**
     * Удалениче текущей точки в состоянии рейса.
     *
     * @param RouteCurrentState $routeCurrentState
     */
    private function clearRouteStatePoints(RouteCurrentState $routeCurrentState)
    {
        foreach ($routeCurrentState->getCurrentPoints() as $point) {
            $routeCurrentState->removeCurrentPoints($point);
        }
    }

    /**
     * Создание записи для логирования изменения состояния рейса.
     *
     * @param Route $route
     * @param \DateTime $arrivalTime
     * @param array $points
     * @param string $messageType
     */
    private function createRouteStateLogEntry(
        Route $route,
        \DateTime $arrivalTime,
        array $points,
        string $messageType
    )
    {
        $createdOn = new \DateTime();
        $routeStateLog = new RouteStateLog();
        $routeStateLog->setRoute($route);
        $routeStateLog->setCreatedOn($createdOn);
        $routeStateLog->setArrivalTime($arrivalTime);
        $routeStateLog->setMessageType($messageType);
        /** @var RouteMovementPoint $point */
        foreach ($points as $point) {
            $routeStateLog->addCurrentPoints($point);
        }
        $this->entityManager->persist($routeStateLog);
    }

    /**
     * Получение movementPoint.
     *
     * @param Route $route
     * @param string $depGuid
     * @return mixed
     */
    private function getMovementPoint(Route $route, string $depGuid): ?RouteMovementPoint
    {
        $department = $this->entityManager->getRepository(Department::class)->findOneBy([
            'guid' => $depGuid
        ]);

        return $this->entityManager->getRepository(RouteMovementPoint::class)->findOneBy([
            'route' => $route,
            'department' => $department
        ]);
    }
}

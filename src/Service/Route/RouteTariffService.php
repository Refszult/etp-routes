<?php

namespace App\Service\Route;

use App\Entity\Route\Route;
use App\Entity\Route\RouteTariff;
use App\Entity\Route\RouteWay;
use App\Entity\Tender\RouteDateTemplate;
use App\Entity\Tender\RouteTemplate;
use App\Service\Base\BaseDtoService;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class RouteTariffService.
 */
class RouteTariffService extends BaseDtoService
{
    private RouteService $routeService;

    protected function onConstruct()
    {
        $this->routeService = $this->container->get(RouteService::class);
    }

    /**
     * Получение тарифа для выбранного маршрута, для аукциона.
     *
     * @return mixed
     */
    public function getAuctionRouteTariff(RouteWay $shortcutRouteWay, Route $route)
    {
        return $this->entityManager->getRepository(RouteTariff::class)->findAuctionRouteTariff($shortcutRouteWay, $route);
    }

    /**
     * Получение тарифа для выбранного маршрута, для тендера.
     *
     * @return mixed
     */
    public function getTenderRouteTariff(RouteTemplate $routeTemplate)
    {
        $dateStart = $this->getDateOfFirstRoute($routeTemplate);
        $dateEnd = $this->getDateOfLastRoute($routeTemplate);

        return $this->entityManager->getRepository(RouteTariff::class)->findTenderRouteTariff($routeTemplate, $dateStart, $dateEnd);
    }

    /**
     * Получение тарифа для выбранного маршрута, для тендера.
     *
     * @return mixed
     */
    public function getApiTenderRouteTariff(int $routeWayId)
    {
        $fraht = 0;
        $isShortcut = false;
        $this->denyAccessUnlessGranted('ROUTE_TARIFF');
        /** @var RouteWay $routeWay */
        $routeWay = $this->entityManager->getRepository(RouteWay::class)->findOneBy(
            [
                'id' => $routeWayId,
                'isCancel' => false
            ]
        );
        if (!$routeWay) {
            throw new NotFoundHttpException('Не найден маршрут');
        }
        $routeTariff = $this->entityManager->getRepository(RouteTariff::class)->findTenderRouteTariff($routeWay, new \DateTime(), null);
        if (!$routeTariff) {
            $shortcutRouteWays = $this->routeService->getShortcutRoute($routeWay);
            /* @var RouteWay $route */
            foreach ($shortcutRouteWays as $routeWay) {
                $routeTariff = $this->entityManager->getRepository(RouteTariff::class)->findTenderRouteTariff($routeWay, new \DateTime(), null);
                if ($routeTariff) {
                    $isShortcut = true;
                    break;
                }
            }
        }
        if ($routeTariff) {
            if ($isShortcut) {
                $fraht = $this->calculateFrahtForShortcutRoute($routeTariff, $routeWay->getRouteWayPoints());
            } else {
                $fraht = $routeTariff->getFraht();
            }
        }

        return $this->prepareResponse($this->response, $fraht);
    }

    /**
     * Получение тарифа для выбранного маршрута, для аукциона.
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function getApiAuctionRouteTariff(int $routeId)
    {
        $this->denyAccessUnlessGranted('ROUTE_TARIFF');
        /** @var Route $route */
        $route = $this->entityManager->getRepository(Route::class)->find($routeId);
        if (!$route) {
            throw new NotFoundHttpException('Не найден рейс');
        }
        $routeTariff = $this->getRouteTariff($route);

        return $this->prepareResponse($this->response, $routeTariff ? $routeTariff->getFraht() : 0);
    }

    public function getRouteTariff($route)
    {
        $routeTariff = $this->getAuctionRouteTariff($route->getRouteWay(), $route);
        if (!$routeTariff) {
            $shortcutRouteWays = $this->routeService->getShortcutRoute($route->getRouteWay());
            if (!empty($shortcutRouteWays)) {
                $shortcutRouteWay = null;
                /** @var RouteWay $shortcutRouteWayElem */
                foreach ($shortcutRouteWays as $key => $shortcutRouteWayElem) {
                    if ($shortcutRouteWayElem->getTransportationType() === $route->getTransportationType()) {
                        $shortcutRouteWay = $shortcutRouteWayElem;
                        unset($shortcutRouteWays[$key]);
                    }
                }
                if (null == $shortcutRouteWay) {
                    $shortcutRouteWay = $shortcutRouteWays[0];
                }
                $routeTariff = $this->getAuctionRouteTariff($shortcutRouteWay, $route);
                if (null == $routeTariff) {
                    /** @var RouteWay $shortcutRouteWayElem */
                    foreach ($shortcutRouteWays as $key => $shortcutRouteWayElem) {
                        $shortcutRouteWay = $shortcutRouteWayElem;
                        $routeTariff = $this->getAuctionRouteTariff($shortcutRouteWay, $route);
                        unset($shortcutRouteWays[$key]);
                        if (null != $routeTariff) {
                            break;
                        }
                    }
                }
            }
        }

        return $routeTariff;
    }

    /**
     * Расчет фрахта для укороченного маршрута.
     *
     * @return mixed
     */
    public function calculateFrahtForShortcutRoute(?RouteTariff $routeTariff, Collection $routeWayPoints)
    {
        if (null == $routeTariff) {
            return 0;
        }
        $routeWayPointsCount = $routeWayPoints->count();
        if (2 < $routeWayPoints->count()) {
            $i = 1;
            $incityCount = 0;
            $outcityCount = 0;
            foreach ($routeWayPoints as $routeWayPoint) {
                $prevRouteWayPoint = $routeWayPoint;
                if (1 == $i || $routeWayPointsCount == $i) continue;
                if ($routeWayPoint->getDepartment()->getBranchGuid() === $prevRouteWayPoint->getDepartment()->getBranchGuid()) {
                    $incityCount++;
                } else {
                    ++$outcityCount;
                }
            }
            $fraht = $routeTariff->getFraht() + ($routeTariff->getFrahtIncity() * $incityCount) + ($routeTariff->getFrahtOutcity() * $outcityCount);
        } else {
            $fraht = $routeTariff->getFraht();
        }

        return $fraht;
    }

    protected function getDateOfFirstRoute(RouteTemplate $routeTemplate)
    {
        $minDate = null;
        /** @var \DateTime $periodStart */
        $periodStart = $routeTemplate->getPeriodStart();
        /** @var RouteDateTemplate $dateTemplate */
        foreach ($routeTemplate->getRouteDateTemplates() as $dateTemplate) {
            $format = $dateTemplate->getDateTemplate();
            $step = clone $periodStart;
            $step->modify($format);
            if (!$minDate) {
                $minDate = clone $step;
            } else {
                if ($step < $minDate) {
                    $minDate = clone $step;
                }
            }
        }

        return $minDate;
    }

    protected function getDateOfLastRoute(RouteTemplate $routeTemplate)
    {
        $maxDate = null;
        /** @var \DateTime $periodStop */
        $periodStop = $routeTemplate->getPeriodStop();
        /** @var RouteDateTemplate $dateTemplate */
        foreach ($routeTemplate->getRouteDateTemplates() as $dateTemplate) {
            $format = $dateTemplate->getDateTemplate();
            $step = clone $periodStop;
            $step->modify('monday this week');
            $step->modify($format);
            if ($periodStop > $step) {
                if (!$maxDate) {
                    $maxDate = clone $step;
                } else {
                    if ($step > $maxDate) {
                        $maxDate = clone $step;
                    }
                }
            }
        }

        return $maxDate;
    }
}

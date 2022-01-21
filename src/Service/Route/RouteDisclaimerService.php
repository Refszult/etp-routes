<?php

namespace App\Service\Route;

use App\Classes\Api\Pagination;
use App\Classes\Api\RestResponse;
use App\Entity\Contractor;
use App\Entity\Customer;
use App\Entity\Route\Route;
use App\Entity\Route\RouteDisclaimer;
use App\Exceptions\WrongObjectException;
use App\Model\Route\RouteModel;
use App\Service\Base\BaseModelService;
use mysql_xdevapi\Exception;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class RouteDisclaimerService
 * Сервис CRUD заявок на отказ от рейсов.
 */
class RouteDisclaimerService extends BaseModelService
{
    /**
     * Создание новой заявки на отказ от рейса.
     *
     * @param int    $contractorId
     * @param int    $routeId
     * @param string $params
     * @param bool   $ignoreGrants
     *
     * @return RestResponse
     */
    public function createRouteDisclaimer(
        int $contractorId,
        int $routeId,
        string $params,
        bool $ignoreGrants = false
    ): RestResponse {
        $this->setGrantsCheck($ignoreGrants);
        // TODO добавить проверку прав
        /** @var Contractor $contractor */
        $contractor = $this->entityManager->getRepository('App\Entity\Contractor')
            ->findOneBy(['id' => $contractorId]);
        /** @var Route $route */
        $route = $this->entityManager->getRepository('App\Entity\Route\Route')
            ->findOneBy(['id' => $routeId, 'contractor' => $contractorId]);
        if ($contractor && $route) {
            $oldDisclaimer = $this->entityManager->getRepository('App\Entity\Route\RouteDisclaimer')
                ->findOneBy(['route' => $routeId, 'contractor' => $contractorId]);
            if ($oldDisclaimer) {
                throw new WrongObjectException('Подать заявку на отказ от рейса можно лишь один раз.');
            }
            /** @var RouteDisclaimer $routeDisclaimer */
            $routeDisclaimer = $this->serializer->deserialize($params, RouteDisclaimer::class, 'json', $this->context);
            if ($routeDisclaimer) {
                $routeDisclaimer->setContractor($contractor);
                $routeDisclaimer->setRoute($route);
                if($route->getTender()) {
                    $routeDisclaimer->setType(RouteDisclaimer::TYPE_TENDER);
                } elseif ($route->getAuction()) {
                    $routeDisclaimer->setType(RouteDisclaimer::TYPE_AUCTION);
                }
            } else {
                throw new WrongObjectException('Передан неверный объект');
            }
            $this->errors->addAll($this->validator->validate($routeDisclaimer));
        } else {
            throw new NotFoundHttpException('Не найден рейс.');
        }

        if (count($this->errors) > 0) {
            return new RestResponse($this->prepareErrors(), 422);
        } else {
            $this->checkRouteWays($route);
            $this->entityManager->persist($routeDisclaimer);
            $this->entityManager->flush();

            return new RestResponse($route, 201);
        }
    }

    /**
     * Проверка маршрута рейса на удаление.
     *
     * @param Route $route
     */
    protected function checkRouteWays(Route $route)
    {
        if ($routeWay = $route->getRouteWay()) {
            if (true === $routeWay->getIsCancel()) {
                throw new WrongObjectException('В используемом рейсе указан маршрут, помеченный на удаление.');
            }
        }
    }

    /**
     * Прием заявки на отказ от рейса.
     *
     * @param int  $customerId
     * @param int  $routeId
     * @param bool $ignoreGrants
     *
     * @return RestResponse
     */
    public function acceptRouteDisclaimer(
        int $customerId,
        int $routeId,
        bool $ignoreGrants = false
    ): RestResponse {
        $this->setGrantsCheck($ignoreGrants);
        // TODO добавить проверку прав
        /** @var Customer $customer */
        $customer = $this->entityManager->getRepository('App\Entity\Customer')
            ->findOneBy(['id' => $customerId]);
        /** @var Route $route */
        $route = $this->entityManager->getRepository('App\Entity\Route\Route')
            ->findOneBy(['id' => $routeId, 'customer' => $customerId]);
        if ($customer && $route) {
            /** @var RouteDisclaimer $routeDisclaimer */
            $routeDisclaimer = $this->entityManager->getRepository('App\Entity\Route\RouteDisclaimer')
                ->findOneBy(['route' => $routeId, 'status' => RouteDisclaimer::STATUS_NEW]);
            if ($routeDisclaimer) {
                $routeDisclaimer->setStatus(RouteDisclaimer::STATUS_APPROVED);
                $route->setContractor(null);
                $route->setDriverOne(null);
                $route->setDriverTwo(null);
                $route->setTransport(null);
                $route->setTrailer(null);
                $routeContainers = $route->getRouteContainers();
                if ($routeContainers->count() > 0) {
                    foreach ($routeContainers as $routeContainer) {
                        $route->removeRouteContainer($routeContainer);
                        $this->entityManager->remove($routeContainer);
                    }
                }
                $this->checkRouteWays($route);
                $this->entityManager->persist($route);
                $this->entityManager->persist($routeDisclaimer);
            } else {
                throw new NotFoundHttpException('Не найдено подходящей заявки на отказ');
            }
        } else {
            throw new NotFoundHttpException('Не найден рейс.');
        }

        if (count($this->errors) > 0) {
            return new RestResponse($this->prepareErrors(), 422);
        } else {
            $this->entityManager->flush();

            return new RestResponse($route, 200);
        }
    }

    /**
     * Отмена заявки на отказ от рейса.
     *
     * @param int  $customerId
     * @param int  $routeId
     * @param bool $ignoreGrants
     *
     * @return RestResponse
     */
    public function declineRouteDisclaimer(
        int $customerId,
        int $routeId,
        bool $ignoreGrants = false
    ): RestResponse {
        $this->setGrantsCheck($ignoreGrants);
        // TODO добавить проверку прав
        /** @var Customer $customer */
        $customer = $this->entityManager->getRepository('App\Entity\Customer')
            ->findOneBy(['id' => $customerId]);
        /** @var Route $route */
        $route = $this->entityManager->getRepository('App\Entity\Route\Route')
            ->findOneBy(['id' => $routeId, 'customer' => $customerId]);
        if ($customer && $route) {
            $routeDisclaimer = $this->entityManager->getRepository('App\Entity\Route\RouteDisclaimer')
                ->findOneBy(['route' => $routeId, 'status' => RouteDisclaimer::STATUS_NEW]);
            if ($routeDisclaimer) {
                $routeDisclaimer->setStatus(RouteDisclaimer::STATUS_CANCELED);
                $this->checkRouteWays($route);
                $this->entityManager->persist($routeDisclaimer);
            } else {
                throw new NotFoundHttpException('Не найдено подходящей заявки на отказ');
            }
        } else {
            throw new NotFoundHttpException('Не найден рейс.');
        }

        if (count($this->errors) > 0) {
            return new RestResponse($this->prepareErrors(), 422);
        } else {
            $this->entityManager->flush();

            return new RestResponse($route, 200);
        }
    }
}

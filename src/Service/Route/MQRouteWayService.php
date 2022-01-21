<?php

namespace App\Service\Route;

use App\Classes\Api\RestResponse;
use App\Classes\StaticStorage\UpdatedFrom;
use App\Entity\Route\RouteWay;
use App\Exceptions\WrongObjectException;
use App\Model\Route\RouteWayModel;
use App\Service\Base\BaseModelService;

class MQRouteWayService extends BaseModelService
{
    /**
     * Создание нового маршрута.
     *
     * @param RouteWayModel $routeWayModel
     * @param bool          $ignoreGrants
     *
     * @return RestResponse
     */
    public function createRouteWay(RouteWayModel $routeWayModel, $ignoreGrants = false): RestResponse
    {
        $this->setGrantsCheck($ignoreGrants);
        $this->errors->addAll($this->validator->validate($routeWayModel));
        if ($routeWay = $routeWayModel->createRouteWayFromMQ()) {
            $this->errors->addAll($this->validator->validate($routeWay));
        } else {
            throw new WrongObjectException('Не удалось создать маршрут');
        }


        if (count($this->errors) > 0) {
            return new RestResponse($this->prepareErrors(), 422);
        } else {
            $this->entityManager->persist($routeWay);
            $this->entityManager->flush();

            return new RestResponse($routeWay, 201);
        }
    }

    /**
     * Удаление маршрута.
     *
     * @param RouteWay      $routeWay
     * @param RouteWayModel $routeWayModel
     * @param bool          $ignoreGrants
     *
     * @return RestResponse
     *
     * @throws \Exception
     */
    public function updateRouteWay(
        RouteWay $routeWay,
        RouteWayModel $routeWayModel,
        bool $ignoreGrants = false
    ): RestResponse {
        $this->setGrantsCheck($ignoreGrants);
        $this->errors->addAll($this->validator->validate($routeWayModel));
        if ($routeWay = $routeWayModel->updateRouteWayFromMQ($routeWay)) {
            $this->errors->addAll($this->validator->validate($routeWay));
        } else {
            throw new WrongObjectException('Не удалось обновить маршрут');
        }

        if (count($this->errors) > 0) {
            return new RestResponse($this->prepareErrors(), 422);
        } else {
            $this->entityManager->persist($routeWay);
            $this->entityManager->flush();

            return new RestResponse($routeWay, 200);
        }
    }

    /**
     * Удаление Маршрута.
     *
     * @param RouteWay $routeWay
     * @param bool     $ignoreGrants
     * @param int      $updatedFrom
     *
     * @return RestResponse
     */
    public function deleteRouteWay(
        RouteWay $routeWay,
        bool $ignoreGrants = false,
        $updatedFrom = UpdatedFrom::UPDATED_FROM_IBMMQ
    ): RestResponse {
        $this->setGrantsCheck($ignoreGrants);
        try {
            $routeWay->setUpdatedFrom($updatedFrom);
            $this->entityManager->persist($routeWay);
            $this->entityManager->flush();
            $this->entityManager->remove($routeWay);
            $this->entityManager->flush();
        } catch (\Throwable $exception) {
            throw new WrongObjectException('Не удалось удалить маршрут', $exception);
        }

        return new RestResponse('Маршрут успешно удален.', 200);
    }
}

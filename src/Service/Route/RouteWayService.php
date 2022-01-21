<?php

namespace App\Service\Route;

use App\Classes\Api\Pagination;
use App\Classes\Api\RestResponse;
use App\Classes\StaticStorage\UpdatedFrom;
use App\Entity\Route\Department;
use App\Entity\Route\RouteWay;
use App\Model\Route\RouteWayModel;
use App\Service\Base\BaseModelService;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class RouteWayService
 * Сервис CRUD Маршрутов через RouteWayModel.
 */
class RouteWayService extends BaseModelService
{
    //TODO проверить все права
    /**
     * Создание нового маршрута.
     *
     * @param RouteWayModel $routeWayModel
     * @param bool          $ignoreGrants
     *
     * @return RestResponse
     *
     * @throws \Exception
     */
    public function createRouteWay(RouteWayModel $routeWayModel, $ignoreGrants = false): RestResponse
    {
        $this->setGrantsCheck($ignoreGrants);
        $this->errors->addAll($this->validator->validate($routeWayModel));
        $routeWay = $routeWayModel->createRouteWay();
        $this->errors->addAll($this->validator->validate($routeWay));

        if (count($this->errors) > 0) {
            return new RestResponse($this->prepareErrors(), 422);
        } else {
            $this->entityManager->persist($routeWay);
            $this->entityManager->flush();

            return new RestResponse($routeWay, 201);
        }
    }

    /**
     * Обновление маршрута.
     *
     * @param int           $routeWayId
     * @param RouteWayModel $routeWayModel
     * @param bool          $ignoreGrants
     *
     * @return RestResponse
     *
     * @throws \Exception
     */
    public function updateRouteWay(
        int $routeWayId,
        RouteWayModel $routeWayModel,
        bool $ignoreGrants = false
    ): RestResponse {
        $this->setGrantsCheck($ignoreGrants);
        $this->errors->addAll($this->validator->validate($routeWayModel));
        $routeWay = $routeWayModel->updateRouteWay($routeWayId);
        $this->errors->addAll($this->validator->validate($routeWay));

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
     * @param int  $routeWayId
     * @param bool $ignoreGrants
     * @param int  $updatedFrom
     *
     * @return RestResponse
     */
    public function deleteRouteWay(
        int $routeWayId,
        bool $ignoreGrants = false,
        $updatedFrom = UpdatedFrom::UPDATED_FROM_ETP
    ): RestResponse {
        $this->setGrantsCheck($ignoreGrants);
        $routeWay = $this->entityManager->getRepository('App\Entity\Route\RouteWay')
            ->findOneBy(['id' => $routeWayId]);
        if (!$routeWay) {
            throw new NotFoundHttpException('Не найден маршрут.');
        } else {
            $routeWay->setUpdatedFrom($updatedFrom);
            $this->entityManager->persist($routeWay);
            $this->entityManager->flush();
            $this->entityManager->remove($routeWay);
            $this->entityManager->flush();
        }

        return new RestResponse('Маршрут успешно удален.', 200);
    }

    /**
     * Формируем список маршрутов в рейсах заказчика.
     *
     * @param int $customerId
     *
     * @return RestResponse
     *
     * @throws \Exception
     */
    public function findByCustomerId($customerId, $params)
    {
        $queryBuilder = $this->entityManager->getRepository('App\Entity\Route\RouteWay')
            ->findByCustomerId($params);
        $pagination = new Pagination($this->paginator->paginate(
            $queryBuilder,
            $params->getInt('page', 1),
            $params->getInt('limit', 100000)
        ));

        return new RestResponse($pagination, 200);
    }

    /**
     * Возвращаем список маршрутов в тендерах заказчика.
     *
     * @param $customerId
     * @param $params
     *
     * @return RestResponse
     */
    public function findByCustomerIdInTenders($customerId, $params)
    {
        $queryBuilder = $this->entityManager->getRepository(RouteWay::class)
            ->findByCustomerIdInTenders($customerId, $params);
        $pagination = new Pagination($this->paginator->paginate(
            $queryBuilder,
            $params->getInt('page', 1),
            $params->getInt('limit', 100000)
        ));

        return new RestResponse($pagination, 200);
    }

    /**
     * Формируем  список маршрутов в рейсах подрядчика.
     *
     * @param int $contractorId
     *
     * @return RestResponse
     *
     * @throws \Exception
     */
    public function findByContractorId($contractorId, $params)
    {
        $queryBuilder = $this->entityManager->getRepository('App\Entity\Route\RouteWay')
            ->findByContractorId($contractorId, $params);
        $pagination = new Pagination($this->paginator->paginate(
            $queryBuilder,
            $params->getInt('page', 1),
            $params->getInt('limit', 100000)
        ));

        return new RestResponse($pagination, 200);
    }

    /**
     * Возвращаем список маршрутов в тендерах подрядчика.
     *
     * @param $contractorId
     * @param $params
     *
     * @return RestResponse
     */
    public function findByContractorIdInTenders($contractorId, $params)
    {
        $queryBuilder = $this->entityManager->getRepository(RouteWay::class)
            ->findByContractorIdInTenders($contractorId, $params);
        $pagination = new Pagination($this->paginator->paginate(
            $queryBuilder,
            $params->getInt('page', 1),
            $params->getInt('limit', 100000)
        ));

        return new RestResponse($pagination, 200);
    }

    /**
     * Получения списка отделений.
     *
     * @return RestResponse
     */
    public function getDepartmentsList(ParameterBag $params)
    {
        $this->denyAccessUnlessGranted('DEPARTMENT_LIST');
        $queryBuilder = $this->entityManager->getRepository(Department::class)
            ->findByParams($params);
        $pagination = new Pagination($this->paginator->paginate(
            $queryBuilder,
            $params->getInt('page', 1),
            $params->getInt('limit', 10)
        ));

        return new RestResponse($pagination, 200);
    }
}

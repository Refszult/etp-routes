<?php

namespace App\Service\Route;

use App\Classes\Api\Pagination;
use App\Classes\Api\RestResponse;
use App\Classes\StaticStorage\UpdatedFrom;
use App\Entity\Auction\Auction;
use App\Entity\Route\Branch;
use App\Entity\Route\Department;
use App\Entity\Route\Route;
use App\Entity\Route\RouteOwner;
use App\Entity\Route\RouteWay;
use App\Entity\Route\RouteWayPoint;
use App\Entity\Route\Transportation;
use App\Exceptions\WrongObjectException;
use App\Model\Route\ContractorRouteModel;
use App\Model\Route\CustomerRouteModel;
use App\Model\Route\RouteModel;
use App\Model\Route\RouteModelInterface;
use App\Service\Base\BaseModelService;
use App\Service\Helper\FinderHelper;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Class RouteService
 * Сервис CRUD Рейсов через RouteModel.
 */
class RouteService extends BaseModelService
{
    /**
     * @var RouteWarningService
     */
    protected $routeWarningService;

    public function __construct(
        ValidatorInterface $validator,
        PaginatorInterface $paginator,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        AuthorizationCheckerInterface $authorizationChecker,
        FormFactoryInterface $formFactory,
        ParameterBagInterface $params,
        FinderHelper $finderHelper,
        ContainerInterface $container
    ) {
        $this->validator = $validator;
        $this->paginator = $paginator;
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;
        $this->authorizationChecker = $authorizationChecker;
        $this->formFactory = $formFactory;
        $this->params = $params;
        $this->finderHelper = $finderHelper;
        $this->container = $container;
        $this->errors = new ConstraintViolationList();
        $this->context = new DeserializationContext();
        $this->context->setGroups(['Default']);
        $this->context->enableMaxDepthChecks();
        $this->routeWarningService = $container->get(RouteWarningService::class);
    }

    /**
     * Создание рейса заказчиком.
     *
     * @throws \Exception
     */
    public function createCustomerRoute(int $customerId, CustomerRouteModel $routeModel): RestResponse
    {
        $this->denyAccessUnlessGranted('ROUTE_CREATE', $customerId);

        return $this->createRoute($routeModel);
    }

    /**
     * Создание нового рейса.
     *
     * @param bool $ignoreGrants
     *
     * @throws \Exception
     */
    public function createRoute(RouteModelInterface $routeModel, $ignoreGrants = false): RestResponse
    {
        $this->setGrantsCheck($ignoreGrants);
        $this->errors->addAll($this->validator->validate($routeModel));
        $route = $routeModel->createRoute();
        $this->checkRouteWays($route);
        $this->errors->addAll($this->validator->validate($route));
        if (count($this->errors) > 0) {
            return new RestResponse($this->prepareErrors(), 422);
        } else {
            $this->entityManager->persist($route);
            $this->entityManager->flush();

            return new RestResponse($route, 201);
        }
    }

    /**
     * Обновление рейса заказчиком.
     *
     * @param bool $ignoreGrants
     *
     * @throws \Exception
     */
    public function updateCustomerRoute(
        int $customerId,
        int $routeId,
        CustomerRouteModel $routeModel,
        $ignoreGrants = false
    ): RestResponse {
        $this->setGrantsCheck($ignoreGrants);
        /** @var Route $route */
        $route = $this->entityManager->getRepository(Route::class)
            ->findOneBy(['id' => $routeId, 'customer' => $customerId]);
        $this->denyAccessUnlessGranted('ROUTE_CUSTOMER_UPDATE', $route);
        if (!$route) {
            throw new NotFoundHttpException('Рейс не найден');
        }

        return $this->updateRoute($route, $routeModel);
    }

    /**
     * Обновление рейса подрядчика.
     *
     * @param bool $ignoreGrants
     *
     * @throws \Exception
     */
    public function updateContractorRoute(
        int $contractorId,
        int $routeId,
        ContractorRouteModel $routeModel,
        $ignoreGrants = false
    ): RestResponse {
        $this->setGrantsCheck($ignoreGrants);
        $route = $this->entityManager->getRepository(Route::class)
            ->findOneBy(['id' => $routeId, 'contractor' => $contractorId, 'isCancel' => false]);
        $this->denyAccessUnlessGranted('ROUTE_CONTRACTOR_UPDATE', $route);
        if (!$route) {
            throw new NotFoundHttpException('Рейс не найден');
        }

        return $this->updateRoute($route, $routeModel);
    }

    /**
     * Обновление рейса.
     *
     * @return RestResponse
     *
     * @throws \Exception
     */
    public function updateRoute(Route $route, RouteModelInterface $routeModel, bool $ignoreGrants = false)
    {
        $this->setGrantsCheck($ignoreGrants);
        if ($this->canEditExtend($route)) {
            $this->errors->addAll($this->validator->validate($routeModel));
            if ($route = $routeModel->updateRoute($route)) {
                $this->checkRouteWays($route);
                $this->errors->addAll($this->validator->validate($route));
            } else {
                throw new WrongObjectException('Не удалось обновить рейс');
            }
            if (count($this->errors) > 0) {
                return new RestResponse($this->prepareErrors(), 422);
            } else {
                $this->entityManager->persist($route);
                $this->entityManager->flush();

                return new RestResponse($route, 200);
            }
        } else {
            throw new WrongObjectException(
                'Данный рейс не разрешено редактировать, так как он принадлежит аукциону или тендеру'
            );
        }
    }

    /**
     * Проверяет возможность редактирования рейса, зависящего от внешних объектов.
     */
    protected function canEditExtend(Route $route): bool
    {
        $output = true;
        if ($auction = $route->getAuction()) {
            if (Auction::STATUS_AWAIT === $auction->getStatus()
            || Auction::STATUS_ACTIVE === $auction->getStatus()
            || Auction::STATUS_NO_WINNER === $auction->getStatus()
            || Auction::STATUS_HAS_WINNER === $auction->getStatus()) {
                $output = false;
            }
        }

        return $output;
    }

    /**
     * Получение объекта рейса для заказчика.
     */
    public function getCustomerRoute(int $customerId, int $routeId, bool $ignoreGrants = false): RestResponse
    {
        $this->setGrantsCheck($ignoreGrants);
        /** @var Route $route */
        $route = $this->entityManager->getRepository(Route::class)
            ->findOneBy(['id' => $routeId, 'customer' => $customerId]);
        if (!$route) {
            throw new NotFoundHttpException('Рейс не найден');
        } else {
            $this->routeWarningService->checkRouteWarnings($route);
            $this->denyAccessUnlessGranted('GET_CUSTOMER_ROUTE', $route);

            return new RestResponse($route, 200);
        }
    }

    /**
     * Получение объекта рейса для подрядчика.
     */
    public function getContractorRoute(int $contractorId, int $routeId, bool $ignoreGrants = false): RestResponse
    {
        $this->setGrantsCheck($ignoreGrants);
        /** @var Route $route */
        $route = $this->entityManager->getRepository(Route::class)
//            ->findOneBy(['id' => $routeId, 'contractor' => $contractorId]);
            ->findOneBy(['id' => $routeId]);
//        $this->denyAccessUnlessGranted('GET_CONTRACTOR_ROUTE', $route);
        if (!$route) {
            throw new NotFoundHttpException('Рейс не найден');
        } else {
            $this->routeWarningService->checkRouteWarnings($route);

            return new RestResponse($route, 200);
        }
    }

    /**
     * Получение списка рейсов для заказчика.
     *
     * @throws \Exception
     */
    public function getCustomerRouteList(
        int $customerId,
        ParameterBag $params,
        bool $ignoreGrants = false
    ): RestResponse {
        $this->setGrantsCheck($ignoreGrants);
        $this->denyAccessUnlessGranted('CUSTOMER_ROUTE_LIST', $customerId);
        $queryBuilder = $this->entityManager
            ->getRepository(Route::class)
            ->findByParams($params, $customerId);
        $pagination = new Pagination($this->paginator->paginate(
            $queryBuilder,
            $params->getInt('page', 1),
            $params->getInt('limit', 10)
        ));

        return new RestResponse($pagination, 200);
    }

    /**
     * Получение списка рейсов для аукциона.
     *
     * @return RestResponse
     */
    public function getRoutesForAuctionList(int $customerId, ParameterBag $params): RestResponse
    {
        $this->denyAccessUnlessGranted('CUSTOMER_ROUTE_LIST', $customerId);
        $queryBuilder = $this->entityManager
            ->getRepository(Route::class)
            ->findForAuction($params);
        $pagination = new Pagination($this->paginator->paginate(
            $queryBuilder,
            $params->getInt('page', 1),
            $params->getInt('limit', 10)
        ));

        return new RestResponse($pagination, 200);
    }

    /**
     * Получение списка рейсов для подрядчика.
     *
     * @throws \Exception
     */
    public function getContractorRouteList(
        int $contractorId,
        ParameterBag $params,
        bool $ignoreGrants = false
    ): RestResponse {
        $this->setGrantsCheck($ignoreGrants);
        $this->denyAccessUnlessGranted('GET_CONTRACTOR_ROUTE_LIST', $contractorId);
        $queryBuilder = $this->entityManager
            ->getRepository(Route::class)
            ->findContractorsByParamsNoCancel($params, 0, $contractorId);
        $pagination = new Pagination($this->paginator->paginate(
            $queryBuilder,
            $params->getInt('page', 1),
            $params->getInt('limit', 10)
        ));

        return new RestResponse($pagination, 200);
    }

    /**
     * Получение объекта маршрута.
     */
    public function getRouteWay(int $routeWayId, bool $ignoreGrants = false): RestResponse
    {
        $this->setGrantsCheck($ignoreGrants);
        $this->denyAccessUnlessGranted('GET_ROUTE_WAY', $routeWayId);
        $routeWay = $this->entityManager->getRepository('App\Entity\Route\RouteWay')
            ->findOneBy(['id' => $routeWayId]);

        if (!$routeWay) {
            throw new NotFoundHttpException('Маршрут не найден');
        } else {
            return new RestResponse($routeWay, 200);
        }
    }

    /**
     * Получение списка маршрутов.
     */
    public function getRouteWaysList(ParameterBag $params, bool $ignoreGrants = false): RestResponse
    {
        $this->setGrantsCheck($ignoreGrants);
        $this->denyAccessUnlessGranted('GET_ROUTE_WAYS');
        $queryBuilder = $this->entityManager
            ->getRepository(RouteWay::class)
            ->findByParams($params);
        $pagination = new Pagination($this->paginator->paginate(
            $queryBuilder,
            $params->getInt('page', 1),
            $params->getInt('limit', 10)
        ));

        return new RestResponse($pagination, 200);
    }

    /**
     * Получение списка маршрутов.
     */
    public function getRouteOwnersList(ParameterBag $params, bool $ignoreGrants = false): RestResponse
    {
        $this->setGrantsCheck($ignoreGrants);
        $this->denyAccessUnlessGranted('GET_ROUTE_WAYS');
        $queryBuilder = $this->entityManager
            ->getRepository(RouteOwner::class)
            ->findByParams($params);
        $pagination = new Pagination($this->paginator->paginate(
            $queryBuilder,
            $params->getInt('page', 1),
            $params->getInt('limit', 10)
        ));

        return new RestResponse($pagination, 200);
    }

    /**
     * Отписка рейса.
     */
    public function setRouteDraft(int $customerId, int $routeId, bool $ignoreGrants = false): RestResponse
    {
        $this->setGrantsCheck($ignoreGrants);
        /** @var Route $route */
        $route = $this->entityManager->getRepository(Route::class)
            ->findOneBy(['id' => $routeId, 'customer' => $customerId]);
        if ($route) {
            $this->denyAccessUnlessGranted('SET_ROUTE_DRAFT', $route);
            $canReady = true;
            if ($route->getIsCancel()) {
                $canReady = false;
            }
            if ($route->getClosed()) {
                $canReady = false;
            }
            if (!$route->getIsDraft()) {
                $canReady = false;
            }
            if (!$route->getTransport()) {
                $canReady = false;
            }
            if (!$route->getDriverOne()) {
                $canReady = false;
            }
            if (!$route->getBoostFlag() && $route->getDriverTwo()) {
                $canReady = false;
            }
            if ($canReady) {
                $route->setUpdatedFrom(UpdatedFrom::UPDATED_FROM_ETP);
                $this->checkRouteWays($route);
                $route->setIsDraft(false);
                $this->entityManager->persist($route);
            } else {
                throw new WrongObjectException('Невозможно отписать рейс.');
            }
        } else {
            throw new NotFoundHttpException('Не найден рейс');
        }

        $this->entityManager->flush();

        return new RestResponse($route, 200);
    }

    /**
     * Отмена рейса.
     *
     * @throws \Exception
     */
    public function cancelRoute(int $customerId, int $routeId, bool $ignoreGrants = false): RestResponse
    {
        $this->setGrantsCheck($ignoreGrants);
        /** @var Route $route */
        $route = $this->entityManager->getRepository(Route::class)
            ->find($routeId);
        if ($route) {
            $this->denyAccessUnlessGranted('CUSTOMER_ROUTE_CANCEL', $route);
            $this->checkRouteWays($route);
            $route->setIsCancel(true);
            $route->setUpdatedFrom(UpdatedFrom::UPDATED_FROM_ETP);
            $route->setUpdatedOn(new \DateTime());
            $this->entityManager->persist($route);
            $this->entityManager->flush();
        } else {
            throw new NotFoundHttpException('Рейс не найден');
        }

        return new RestResponse($route, 200);
    }

    /**
     * Восстановление рейса рейса.
     *
     * @throws \Exception
     */
    public function uncancelRoute(int $customerId, int $routeId, bool $ignoreGrants = false): RestResponse
    {
        $this->setGrantsCheck($ignoreGrants);
        /** @var Route $route */
        $route = $this->entityManager->getRepository(Route::class)
            ->find($routeId);
        if ($route) {
            $this->denyAccessUnlessGranted('CUSTOMER_ROUTE_CANCEL', $route);
            $this->checkRouteWays($route);
            $route->setIsCancel(false);
            $route->setUpdatedFrom(UpdatedFrom::UPDATED_FROM_ETP);
            $route->setUpdatedOn(new \DateTime());
            $this->entityManager->persist($route);
            $this->entityManager->flush();
        } else {
            throw new NotFoundHttpException('Рейс не найден');
        }

        return new RestResponse($route, 200);
    }

    /**
     * Удаление Рейса.
     *
     * @param int $updatedFrom
     *
     * @return RestResponse
     */
    public function deleteRoute(int $routeId, bool $ignoreGrants = false, $updatedFrom = UpdatedFrom::UPDATED_FROM_ETP)
    {
        $this->setGrantsCheck($ignoreGrants);
        /** @var Route $route */
        $route = $this->entityManager
            ->getRepository(Route::class)
            ->findOneBy(['id' => $routeId]);
        if ($this->canEditExtend($route)) {
            $this->denyAccessUnlessGranted('DELETE_ROUTE', $route);
            if (!$route) {
                throw new NotFoundHttpException('Не найден рейс.');
            } else {
                $route->setUpdatedFrom($updatedFrom);
                $this->checkRouteWays($route);
                $this->entityManager->persist($route);
                $this->entityManager->flush();
                $this->entityManager->remove($route);
                $this->entityManager->flush();
            }
        } else {
            throw new WrongObjectException('Данный рейс не разрешено удалять, так как он принадлежит аукциону или тендеру');
        }

        return new RestResponse('Рейс успешно удален.', 200);
    }

    /**
     * Получение перевозок.
     *
     * @return RestResponse
     */
    public function getTransportations(ParameterBag $params, bool $ignoreGrants = false)
    {
        $this->setGrantsCheck($ignoreGrants);
        $queryBuilder = $this->entityManager
        ->getRepository(Transportation::class)
        ->findAll();
        $pagination = new Pagination(
            $this->paginator->paginate(
                $queryBuilder,
                $params->getInt('page', 1),
                $params->getInt('limit', 10)
            )
        );

        return new RestResponse($pagination, 200);
    }

    /**
     * Формируем  список маршрутов в аукционах заказчика.
     *
     * @param int $customerId
     *
     * @return RestResponse
     *
     * @throws \Exception
     */
    public function findRoutesInCustomerAuctions($customerId, $params)
    {
        $this->denyAccessUnlessGranted('CUSTOMER_ROUTE_LIST', $customerId);
        $queryBuilder = $this->entityManager->getRepository(Route::class)
           ->findInCustomerAuctions($customerId, $params);
        $pagination = new Pagination($this->paginator->paginate(
          $queryBuilder,
          $params->getInt('page', 1),
          $params->getInt('limit', 100000)
        ));

        return new RestResponse($pagination, 200);
    }

    /**
     * Формируем  список маршрутов в аукционах подрядчика.
     *
     * @param int $contractorId
     *
     * @return RestResponse
     *
     * @throws \Exception
     */
    public function findRoutesInContractorAuctions($contractorId, $params)
    {
        $this->denyAccessUnlessGranted('GET_CONTRACTOR_ROUTE_LIST', $contractorId);
        $queryBuilder = $this->entityManager->getRepository(Route::class)
            ->findInContractorAuctions($contractorId, $params);
        $pagination = new Pagination($this->paginator->paginate(
            $queryBuilder,
            $params->getInt('page', 1),
            $params->getInt('limit', 100000)
        ));

        return new RestResponse($pagination, 200);
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
     * Поиск укороченного маршрута.
     *
     * @param RouteWay $routeWay
     */
    public function getShortcutRoute(RouteWay $routeWay)
    {
        $routeWayPoints = $this->entityManager->getRepository(RouteWayPoint::class)->findByRouteWay($routeWay);
        $firstRouteWayPoint = $routeWayPoints[array_key_first($routeWayPoints)];
        $lastRouteWayPoint = $routeWayPoints[array_key_last($routeWayPoints)];
        $firstBranch = $this->entityManager->getRepository(Branch::class)->findOneBy([
            'guid' => $firstRouteWayPoint->getDepartment()->getBranchGuid()
        ]);
        $lastBranch = $this->entityManager->getRepository(Branch::class)->findOneBy([
            'guid' => $lastRouteWayPoint->getDepartment()->getBranchGuid()
        ]);
        if (!$firstBranch || !$lastBranch) {
            return [];
        }
        $firstDep = $this->entityManager->getRepository(Department::class)->findOneBy([
            'guid' => $firstBranch->getMainDepartmentGuid()
        ]);
        $lastDep = $this->entityManager->getRepository(Department::class)->findOneBy([
            'guid' => $lastBranch->getMainDepartmentGuid()
        ]);

        return $this->entityManager->getRepository(RouteWay::class)->findShortRouteWay($firstDep, $lastDep);
    }
}

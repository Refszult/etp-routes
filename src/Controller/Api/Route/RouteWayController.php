<?php

namespace App\Controller\Api\Route;

use App\Controller\Api\BaseApiController;
use App\Entity\Route\RouteWay;
use App\Entity\Route\RouteWayDimension;
use App\Service\RequestResponse\RequestService;
use App\Service\Route\RouteService;
use App\Service\Route\RouteWayDimensionDeserializer;
use App\Service\Route\RouteWayDimensionService;
use FOS\RestBundle\Controller\Annotations\View as ViewAnnotation;
use App\Annotations\SerializerGroups;
use \Symfony\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class RouteWayController extends BaseApiController
{
    /**
     * Получение краткого списка маршрутов средств c пагинацией.
     *
     * @Route(
     *     "/route_ways_short",
     *     name="get_route_ways_short",
     *     methods={"GET"}
     * )
     *
     * @SWG\Parameter(
     *     name="page",
     *     in="query",
     *     type="integer",
     *     description="Номер страницы с результатами",
     *     default=1
     * )
     *
     * @SWG\Parameter(
     *     name="limit",
     *     in="query",
     *     type="integer",
     *     description="Число записей на странице",
     *     default=10
     * )
     *
     * @SWG\Parameter(
     *     name="query",
     *     in="query",
     *     type="string",
     *     description="Строка поиска по коду или названию",
     * )
     *
     * @SWG\Parameter(
     *     name="transportation_type",
     *     in="query",
     *     type="integer",
     *     description="Тип перевозки (1-Авиа, 2-Авто, 3-ЖД, 4-Без ТС)",
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Отдает список маршрутов.",
     *     @SWG\Schema(
     *         type="object",
     *         @SWG\Property(
     *              property="items",
     *              type="array",
     *              @SWG\Items(
     *                  ref=@Model(type=RouteWay::class, groups={"Default", "Doc"})
     *              ),
     *         ),
     *         @SWG\Property(property="current_page_number", type="integer"),
     *         @SWG\Property(property="num_items_per_page", type="integer"),
     *         @SWG\Property(property="total_count", type="integer"),
     *     )
     * )
     * @SWG\Tag(name="Рейсы")
     *
     * @ViewAnnotation(serializerGroups={"Default"})
     * @SerializerGroups({"Default"})
     *
     * @return JsonResponse
     */
    public function getRouteShortWaysList(
        Request $request,
        RouteService $routeService,
        RequestService $requestService
    )
    {
        $params = $requestService->prepareGetRequest($request);
        $response = $routeService->getRouteWaysList($params);

        return $this->view($response->body, $response->code, $response->headers);
    }

    /**
     * Получение деталей маршрута.
     *
     * @Route(
     *     "/route_ways/{route_way_id}",
     *     name="get_route_way",
     *     methods={"GET"},
     *     requirements={"route_way_id": "\d+"}
     * )
     *
     * @SWG\Parameter(
     *     name="route_way_id",
     *     in="path",
     *     type="integer",
     *     description="ID маршрута",
     *     required=true
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Возвращает данные маршрута.",
     *     @Model(type=RouteWay::class, groups={"Default", "RouteWay_info", "Doc", "RouteWay_base"})
     * )
     * @SWG\Tag(name="Рейсы")
     *
     * @ViewAnnotation(serializerGroups={"Default", "RouteWay_info", "RouteWay_base"})
     * @SerializerGroups({"Default", "RouteWay_info", "RouteWay_base"})
     *
     * @return JsonResponse
     */
    public function getRouteInfo(
        int $route_way_id,
        RouteService $routeService,
        Request $request
    )
    {
        $response = $routeService->getRouteWay($route_way_id);

        return $this->view($response->body, $response->code, $response->headers);
    }

    /**
     * Получение списка маршрутов c пагинацией.
     *
     * @Route(
     *     "/route_ways",
     *     name="get_route_ways",
     *     methods={"GET"}
     * )
     *
     * @SWG\Parameter(
     *     name="page",
     *     in="query",
     *     type="integer",
     *     description="Номер страницы с результатами",
     *     default=1
     * )
     *
     * @SWG\Parameter(
     *     name="limit",
     *     in="query",
     *     type="integer",
     *     description="Число записей на странице",
     *     default=10
     * )
     *
     * @SWG\Parameter(
     *     name="query",
     *     in="query",
     *     type="string",
     *     description="Строка поиска по коду или названию",
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Отдает список маршрутов.",
     *     @SWG\Schema(
     *         type="object",
     *         @SWG\Property(
     *              property="items",
     *              type="array",
     *              @SWG\Items(
     *                  ref=@Model(type=RouteWay::class, groups={"Default", "Doc", "RouteWay_info", "Route_base"})
     *              ),
     *         ),
     *         @SWG\Property(property="current_page_number", type="integer"),
     *         @SWG\Property(property="num_items_per_page", type="integer"),
     *         @SWG\Property(property="total_count", type="integer"),
     *     )
     * )
     * @SWG\Tag(name="Рейсы")
     *
     * @ViewAnnotation(serializerGroups={"Default", "RouteWay_info", "Route_base"})
     * @SerializerGroups({"Default", "RouteWay_info", "Route_base"})
     *
     * @return JsonResponse
     */
    public function getRouteWaysList(
        Request $request,
        RouteService $routeService,
        RequestService $requestService
    )
    {
        $params = $requestService->prepareGetRequest($request);
        $response = $routeService->getRouteWaysList($params);

        return $this->view($response->body, $response->code, $response->headers);
    }

    /**
     * Создание связи точки маршрута-габарит.
     *
     * @Route(
     *     "/customers/{customer_id}/route-way-dimension",
     *     name="create-route-way-dimension",
     *     methods={"POST"},
     *     requirements={"customer_id": "\d+"}
     * )
     *
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     description="Объект связки",
     *     required=true,
     *     format="application/json",
     *     @SWG\Schema(
     *         type="object",
     *         ref=@Model(type=RouteWayDimension::class, groups={"Default"})
     *     )
     * )
     *
     * @SWG\Response(
     *     response=201,
     *     description="Создает новую связку.",
     *     @Model(type=RouteWayDimension::class, groups={"Default"})
     * )
     * @SWG\Tag(name="Габариты")
     * @SerializerGroups({"Default"})
     *
     * @return JsonResponse
     */
    public function createRouteWayDimension(
        int $customer_id,
        Request $request,
        RouteWayDimensionService $routeWayDimensionService,
        RouteWayDimensionDeserializer $deserializer
    ): JsonResponse
    {
        $params = $request->getContent();
        $routeWayDimensionDto = $deserializer->deserializeToApiDto($params);
        $response = $this->prepareResponse($routeWayDimensionService->createRouteWayDimension($customer_id, $routeWayDimensionDto), $request);

        return $this->view($response->body, $response->code, $response->headers);
    }

    /**
     * Обновление связи точки маршрута-габарит.
     *
     * @Route(
     *     "/customers/{customer_id}/route-way-dimension/{route_way_dimension_id}",
     *     name="update-route-way-dimension",
     *     methods={"PUT"},
     *     requirements={"customer_id": "\d+", "route_way_dimension_id": "\d+"}
     * )
     *
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     description="Объект связки",
     *     required=true,
     *     format="application/json",
     *     @SWG\Schema(
     *         type="object",
     *         ref=@Model(type=RouteWayDimension::class, groups={"Default"})
     *     )
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Обновляет существующую связку.",
     *     @Model(type=RouteWayDimension::class, groups={"Default"})
     * )
     * @SWG\Tag(name="Габариты")
     * @SerializerGroups({"Default"})
     *
     * @return JsonResponse
     */
    public function updateRouteWayDimension(
        int $customer_id,
        int $route_way_dimension_id,
        Request $request,
        RouteWayDimensionService $routeWayDimensionService,
        RouteWayDimensionDeserializer $deserializer
    ): JsonResponse
    {
        $params = $request->getContent();
        $routeWayDimensionDto = $deserializer->deserializeToApiDto($params);
        $response = $this->prepareResponse($routeWayDimensionService->updateRouteWayDimension($customer_id, $route_way_dimension_id, $routeWayDimensionDto), $request);

        return $this->view($response->body, $response->code, $response->headers);
    }

    /**
     * Удаление связи точки маршрута-габарит.
     *
     * @Route(
     *     "/customers/{customer_id}/route-way-dimension/{route_way_dimension_id}",
     *     name="delete-route-way-dimension",
     *     methods={"DELETE"},
     *     requirements={"customer_id": "\d+", "route_way_dimension_id": "\d+"}
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="В случае успеха связка точки маршрута-габарит удаляется.",
     * )
     * @SWG\Tag(name="Габариты")
     *
     * @return JsonResponse
     */
    public function deleteRouteWayDimension(
        int $customer_id,
        int $route_way_dimension_id,
        Request $request,
        RouteWayDimensionService $routeWayDimensionService
    ): JsonResponse
    {
        $response = $this->prepareResponse($routeWayDimensionService->deleteRouteWayDimension($customer_id, $route_way_dimension_id), $request);

        return $this->view($response->body, $response->code, $response->headers);
    }

    /**
     * Получение связки точки маршрута-габарит, либо получение дефолтных габаритов.
     *
     * @Route(
     *     "/customers/{customer_id}/route-way-dimension/{route_way_id}",
     *     name="get-route-way-dimension",
     *     methods={"GET"},
     *     requirements={"customer_id": "\d+", "route_way_id": "\d+"}
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Возвращает габарит и опционально возможные.",
     *     @Model(type=RouteWayDimension::class, groups={"Default"})
     * )
     * @SWG\Tag(name="Габариты")
     *
     *
     * @ViewAnnotation(serializerGroups={"Default"})
     * @SerializerGroups({"Default"})
     *
     * @return JsonResponse
     */
    public function getRouteWayDimension(
        int $customer_id,
        int $route_way_id,
        Request $request,
        RouteWayDimensionService $routeWayDimensionService
    ): JsonResponse
    {
        $response = $this->prepareResponse($routeWayDimensionService->getRouteWayDimension($customer_id, $route_way_id), $request);

        return $this->view($response->body, $response->code, $response->headers);
    }

    /**
     * Получение связки точки маршрута-габарит.
     *
     * @Route(
     *     "/customers/{customer_id}/route-way-dimension-bunch/{route_way_dimension_id}",
     *     name="get-route-way-dimension_bunch",
     *     methods={"GET"},
     *     requirements={"customer_id": "\d+", "route_way_dimension_id": "\d+"}
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Возвращает существующую связку точек маршрута - габарит.",
     *     @Model(type=RouteWayDimension::class, groups={"Default"})
     * )
     * @SWG\Tag(name="Габариты")
     *
     * @ViewAnnotation(serializerGroups={"Default"})
     * @SerializerGroups({"Default"})
     *
     * @return JsonResponse
     */
    public function getRouteWayDimensionBunch(
        int $customer_id,
        int $route_way_dimension_id,
        Request $request,
        RouteWayDimensionService $routeWayDimensionService
    ): JsonResponse
    {
        $response = $this->prepareResponse($routeWayDimensionService->getRouteWayDimensionById($customer_id, $route_way_dimension_id), $request);

        return $this->view($response->body, $response->code, $response->headers);
    }

    /**
     * Получение списка связок точки маршрута - габарит c пагинацией.
     *
     * @Route(
     *     "/customers/{customer_id}/route-way-dimensions",
     *     name="get-route-way-dimension-list",
     *     methods={"GET"}
     * )
     *
     * @SWG\Parameter(
     *     name="page",
     *     in="query",
     *     type="integer",
     *     description="Номер страницы с результатами",
     *     default=1
     * )
     *
     * @SWG\Parameter(
     *     name="limit",
     *     in="query",
     *     type="integer",
     *     description="Число записей на странице",
     *     default=10
     * )
     *
     * @SWG\Parameter(
     *     name="firstPoint",
     *     in="query",
     *     type="string",
     *     description="Строка поиска по коду или названию первой точки",
     * )
     *
     * @SWG\Parameter(
     *     name="lastPoint",
     *     in="query",
     *     type="string",
     *     description="Строка поиска по коду или названию последней точки",
     * )
     *
     * @SWG\Parameter(
     *     name="dimensionId",
     *     in="query",
     *     type="integer",
     *     description="Id габарита ТС",
     * )
     *
     * @SWG\Parameter(
     *     name="optionalDimensionIds",
     *     items=@SWG\Schema(
     *         type="integer"
     *     ),
     *     in="query",
     *     type="array",
     *     description="Массив ID габаритов"
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Отдает список связок маршрут-габарит.",
     *     @SWG\Schema(
     *         type="object",
     *         @SWG\Property(
     *              property="items",
     *              type="array",
     *              @SWG\Items(
     *                  ref=@Model(type=RouteWayDimension::class, groups={"Default", "Doc"})
     *              ),
     *         ),
     *         @SWG\Property(property="current_page_number", type="integer"),
     *         @SWG\Property(property="num_items_per_page", type="integer"),
     *         @SWG\Property(property="total_count", type="integer"),
     *     )
     * )
     * @SWG\Tag(name="Габариты")
     *
     * @ViewAnnotation(serializerGroups={"Default"})
     * @SerializerGroups({"Default"})
     *
     * @return JsonResponse
     */
    public function getRouteWayDimensionList(
        int $customer_id,
        Request $request,
        RouteWayDimensionService $routeWayDimensionService,
        RequestService $requestService
    )
    {
        $params = $requestService->prepareGetRequest($request);
        $response = $this->prepareResponse($routeWayDimensionService->getRouteWayDimensionList($customer_id, $params), $request);

        return $this->view($response->body, $response->code, $response->headers);

    }
}

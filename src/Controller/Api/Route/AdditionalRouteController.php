<?php

namespace App\Controller\Api\Route;

use App\Annotations\SerializerGroups;
use App\Controller\Api\BaseApiController;
use App\Entity\Agreement\Organization;
use App\Entity\Route\CargoPipelineEvent;
use App\Entity\Route\CargoPipelinePlacesOfEvent;
use App\Entity\Route\Department;
use App\Entity\Route\RouteOwner;
use App\Entity\Route\Transportation;
use App\Entity\Route\TransportationType;
use App\Service\Agreement\AgreementService;
use App\Service\RequestResponse\RequestService;
use App\Service\Route\AdditionalRouteService;
use App\Service\Route\RouteService;
use App\Service\Route\RouteTariffService;
use App\Service\Route\RouteWayService;
use App\Service\Route\TransportationSettingsDeserializer;
use App\Entity\Route\TransportationSettings;
use FOS\RestBundle\Controller\Annotations\View as ViewAnnotation;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AdditionalRouteController extends BaseApiController
{
    /**
     * Получение списка видов перевозки.
     *
     * @Route("/transportation-type", name="get_transportation_type_list", methods={"GET"})
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
     * @SWG\Response(
     *     response=200,
     *     description="Отдает список видов перевозки.",
     *     @SWG\Schema(
     *         type="object",
     *         @SWG\Property(
     *              property="items",
     *              type="array",
     *              @SWG\Items(
     *                  ref=@Model(type=TransportationType::class, groups={"Default", "Doc"})
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
    public function getTransportationTypes(
        Request $request,
        AdditionalRouteService $additionalRouteService,
        RequestService $requestService
    ) {
        $params = $requestService->prepareGetRequest($request);
        $response = $this->prepareResponse(
            $additionalRouteService->getTransportationTypeList($params),
            $request
        );

        return $this->view($response->body, $response->code, $response->headers);
    }

    /**
     * Получение списка типов перевозки.
     *
     * @Route(
     *     "/transportations",
     *     name="get_transportations",
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
     * @SWG\Response(
     *     response=200,
     *     description="Отдает список типов.",
     *     @SWG\Schema(
     *         type="object",
     *         @SWG\Property(
     *              property="items",
     *              type="array",
     *              @SWG\Items(
     *                  ref=@Model(type=Transportation::class, groups={"Default", "Doc"})
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
    public function getTransportationsList(
        Request $request,
        RouteService $routeService,
        RequestService $requestService
    ) {
        $params = $requestService->prepareGetRequest($request);
        $response = $routeService->getTransportations($params);

        return $this->view($response->body, $response->code, $response->headers);
    }

    /**
     * Получение тарифа для маршрута тендера.
     *
     * @Route(
     *     "/route-tariff/tender/route-way/{route_way_id}",
     *     name="get_route_tariff_to_tender",
     *     methods={"GET"}
     * )
     *
     * @SWG\Parameter(
     *     name="route_way_id",
     *     in="path",
     *     type="integer",
     *     description="ID рейса",
     *     required=true
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Отдает Строимость маршурта.",
     *     )
     * )
     * @SWG\Tag(name="Рейсы")
     *
     * @ViewAnnotation(serializerGroups={"Default", "List"})
     * @SerializerGroups({"Default", "List"})
     *
     * @return JsonResponse
     */
    public function getRouteTariffToTender(
        int $route_way_id,
        Request $request,
        RouteTariffService $routeTariffService
    ) {
        $response = $this->prepareResponse($routeTariffService->getApiTenderRouteTariff($route_way_id), $request);

        return $this->view($response->body, $response->code, $response->headers);
    }

    /**
     * Получение тарифа для маршрута аукциона.
     *
     * @Route(
     *     "/route-tariff/auction/route/{route_id}",
     *     name="get_route_tariff_to_auction",
     *     methods={"GET"}
     * )
     *
     * @SWG\Parameter(
     *     name="route_id",
     *     in="path",
     *     type="integer",
     *     description="ID рейса",
     *     required=true
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Отдает Строимость рейса.",
     *     )
     * )
     * @SWG\Tag(name="Рейсы")
     *
     * @ViewAnnotation(serializerGroups={"Default", "List"})
     * @SerializerGroups({"Default", "List"})
     *
     * @return JsonResponse
     */
    public function getRouteTariffToAuction(
        int $route_id,
        Request $request,
        RouteTariffService $routeTariffService
    ) {
        $response = $this->prepareResponse($routeTariffService->getApiAuctionRouteTariff($route_id), $request);

        return $this->view($response->body, $response->code, $response->headers);
    }

    /**
     * Получение событий грузопровода.
     *
     * @Route("/cargo-pipeline-event", name="get_cargo_pipeline_event_list", methods={"GET"})
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
     * @SWG\Response(
     *     response=200,
     *     description="Отдает список событий грузопровода.",
     *     @SWG\Schema(
     *         type="object",
     *         @SWG\Property(
     *              property="items",
     *              type="array",
     *              @SWG\Items(
     *                  ref=@Model(type=CargoPipelineEvent::class, groups={"Default", "Doc"})
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
    public function getCargoPipelineEvents(
        Request $request,
        AdditionalRouteService $additionalRouteService,
        RequestService $requestService
    ) {
        $params = $requestService->prepareGetRequest($request);
        $response = $this->prepareResponse(
            $additionalRouteService->getCargoPipelineEvents($params),
            $request
        );

        return $this->view($response->body, $response->code, $response->headers);
    }

    /**
     * Получение точек событий грузопровода.
     *
     * @Route("/cargo-pipeline-places-event", name="get_cargo_pipeline_places_event_list", methods={"GET"})
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
     *     description="Отдает список точек событий грузопровода.",
     *     @SWG\Schema(
     *         type="object",
     *         @SWG\Property(
     *              property="items",
     *              type="array",
     *              @SWG\Items(
     *                  ref=@Model(type=CargoPipelinePlacesOfEvent::class, groups={"Default", "Doc"})
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
    public function getCargoPipelinePlacesOfEvent(
        Request $request,
        AdditionalRouteService $additionalRouteService,
        RequestService $requestService
    ) {
        $params = $requestService->prepareGetRequest($request);
        $response = $this->prepareResponse(
            $additionalRouteService->getCargoPipelinePlacesOfEvent($params),
            $request
        );

        return $this->view($response->body, $response->code, $response->headers);
    }

    /**
     * Получение списка отделений c пагинацией.
     *
     * @Route(
     *     "/departments",
     *     name="get_departments_list",
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
     *     name="name",
     *     in="query",
     *     type="string",
     *     description="Строка поиска по названию отделения",
     * )
     *
     * @SWG\Parameter(
     *     name="extradition_point",
     *     in="query",
     *     type="boolean",
     *     description="Является ли точкой ПВЗ",
     * )
     * @SWG\Response(
     *     response=200,
     *     description="Отдает список маршрутов.",
     *     @SWG\Schema(
     *         type="object",
     *         @SWG\Property(
     *              property="items",
     *              type="array",
     *              @SWG\Items(
     *                  ref=@Model(type=Department::class, groups={"Default", "List"})
     *              ),
     *         ),
     *         @SWG\Property(property="current_page_number", type="integer"),
     *         @SWG\Property(property="num_items_per_page", type="integer"),
     *         @SWG\Property(property="total_count", type="integer"),
     *     )
     * )
     * @SWG\Tag(name="Рейсы")
     *
     * @ViewAnnotation(serializerGroups={"Default", "List"})
     * @SerializerGroups({"Default", "List"})
     *
     * @return JsonResponse
     */
    public function getDepartmentList(
        Request $request,
        RouteWayService $routeWayService,
        RequestService $requestService
    ) {
        $params = $requestService->prepareGetRequest($request);
        $response = $routeWayService->getDepartmentsList($params);

        return $this->view($response->body, $response->code, $response->headers);
    }

    /**
     * Получение списка владельцев c пагинацией.
     *
     * @Route(
     *     "/route-owners",
     *     name="get_route_owners",
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
     *                  ref=@Model(type=RouteOwner::class, groups={"Default", "Doc", "RouteOwner_info", "RouteOwner_base"})
     *              ),
     *         ),
     *         @SWG\Property(property="current_page_number", type="integer"),
     *         @SWG\Property(property="num_items_per_page", type="integer"),
     *         @SWG\Property(property="total_count", type="integer"),
     *     )
     * )
     * @SWG\Tag(name="Рейсы")
     *
     * @ViewAnnotation(serializerGroups={"Default", "RouteOwner_info", "RouteOwner_base"})
     * @SerializerGroups({"Default", "RouteOwner_info", "RouteOwner_base"})
     *
     * @return JsonResponse
     */
    public function getRouteOwnersList(
        Request $request,
        RouteService $routeService,
        RequestService $requestService
    ) {
        $params = $requestService->prepareGetRequest($request);
        $response = $routeService->getRouteOwnersList($params);

        return $this->view($response->body, $response->code, $response->headers);
    }

    /**
     * Получение списка типов перевозки.
     *
     * @Route(
     *     "/organizations",
     *     name="get_organizations",
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
     * @SWG\Response(
     *     response=200,
     *     description="Отдает список организаций.",
     *     @SWG\Schema(
     *         type="object",
     *         @SWG\Property(
     *              property="items",
     *              type="array",
     *              @SWG\Items(
     *                  ref=@Model(type=Organization::class, groups={"Default", "Doc"})
     *              ),
     *         ),
     *         @SWG\Property(property="current_page_number", type="integer"),
     *         @SWG\Property(property="num_items_per_page", type="integer"),
     *         @SWG\Property(property="total_count", type="integer"),
     *     )
     * )
     * @SWG\Tag(name="Договоры")
     *
     * @ViewAnnotation(serializerGroups={"Default"})
     * @SerializerGroups({"Default", "RouteOwner_info", "RouteOwner_base"})
     */
    public function getOrganizationList(
        Request $request,
        AgreementService $agreementService,
        RequestService $requestService
    ) {
        $params = $requestService->prepareGetRequest($request);

        $response = $this->prepareResponse(
            $agreementService->getOrganizationList($params),
            $request
        );

        return $this->view($response->body, $response->code, $response->headers);
    }

    /**
     * Создание/обновление настроек типа перевозки
     *
     * @Route("/transportations/{transportation_id}/settings",
     *      name="update_transportation_settings",
     *      methods={"PUT"},
     *      requirements={"transportation_id":"[0-9]+"}
     * )
     *
     * @SWG\Tag(name="Рейсы")
     *
     * @SWG\Parameter(
     *     name="transportation_id",
     *     in="path",
     *     type="integer",
     *     required=true
     * )
     *
     * @SWG\Parameter(
     *     name="body",
     *     in="body",
     *     description="Данные в теле запроса",
     *     required=true,
     *     format="application/json",
     *     @SWG\Schema(
     *         type="object",
     *         ref=@Model(type=TransportationSettings::class, groups={"Default"})
     *     )
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Отдает объект настройки типа перевозки",
     *     @Model(type=TransportationSettings::class, groups={"Default"})
     * )
     *
     *
     * @SerializerGroups({"Default", "Create_update_transportation_settings"})
     *
     * @return JsonResponse
     */
    public function updateTransportationFirstBid(
        int $transportation_id,
        Request $request,
        AdditionalRouteService $additionalRouteService,
        TransportationSettingsDeserializer $transportationFirstBidDeserializer
    ) {
        $params = $request->getContent();
        $settingsDto = $transportationFirstBidDeserializer->deserializeToApiDto($params);
        $response = $this->prepareResponse(
            $additionalRouteService->createOrUpdateTransportationSettings($transportation_id, $settingsDto),
            $request
        );

        return $this->view($response->body, $response->code, $response->headers);
    }
}

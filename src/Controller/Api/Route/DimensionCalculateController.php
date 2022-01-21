<?php

namespace App\Controller\Api\Route;

use App\Controller\Api\BaseApiController;
use App\Entity\Vehicle\DimensionCalculate;
use App\Service\Dimension\DimensionCalculateDeserializer;
use App\Service\RequestResponse\RequestService;
use App\Service\Route\RouteWayDimensionService;
use FOS\RestBundle\Controller\Annotations\View as ViewAnnotation;
use App\Annotations\SerializerGroups;
use \Symfony\Component\HttpFoundation\JsonResponse;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DimensionCalculateController extends BaseApiController
{
    /**
     * Получение коэффициентов отношения опциональных габаритов к основному.
     *
     * @Route("/customers/{customer_id}/dimension/{main_dimension_id}/calculate",
     *     name="get_dimension_calculate_relation",
     *     methods={"GET"}
     * )
     *
     * @SWG\Parameter(
     *     name="main_dimension_id",
     *     in="path",
     *     type="integer",
     *     description="ID габарита",
     *     required=true
     * )
     *
     * @SWG\Parameter(
     *     name="optionalDimensionIds",
     *     items=@SWG\Schema(
     *         type="integer"
     *     ),
     *     in="query",
     *     type="array",
     *     description="Массив ID опциональных габаритов",
     *     required=false
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Отдает коэффициенты опциональных габаритов к основному.",
     *     @SWG\Schema(
     *         type="object",
     *         @SWG\Property(
     *              property="items",
     *              type="array",
     *              @SWG\Items(
     *                  ref=@Model(type=DimensionCalculate::class, groups={"Default"})
     *              ),
     *         ),
     *     )
     * )
     * @SWG\Tag(name="Габариты")
     *
     * @ViewAnnotation(serializerGroups={"Default"})
     * @SerializerGroups({"Default"})
     *
     * @return JsonResponse
     */
    public function getDimensionCalculatesByMainDimension(
        int $customer_id,
        int $main_dimension_id,
        Request $request,
        RouteWayDimensionService $routeWayDimensionService,
        RequestService $requestService
    ): JsonResponse {
        $params = $requestService->prepareGetRequest($request);
        $response = $this->prepareResponse(
            $routeWayDimensionService->getDimensionCalculatesByMainDimension($customer_id, $main_dimension_id, $params),
            $request
        );

        return $this->view($response->body, $response->code, $response->headers);
    }

    /**
     * Получение списка коэффициентов отношения габаритов друг к другу.
     *
     * @Route("/customers/{customer_id}/dimension/calculate/",
     *      name="get_dimension_calculate_list", methods={"GET"},
     *      requirements={"customer_id":"\d+"}
     *     )
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
     *     description="Отдает список коэффициентов отношения габаритов друг к дургу.",
     *     @SWG\Schema(
     *         type="object",
     *         @SWG\Property(
     *              property="items",
     *              type="array",
     *              @SWG\Items(
     *                  ref=@Model(type=DimensionCalculate::class, groups={"Default"})
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
    public function getDimensionsCalculateList(
        int $customer_id,
        Request $request,
        RouteWayDimensionService $routeWayDimensionService,
        RequestService $requestService
    ) {
        $params = $requestService->prepareGetRequest($request);
        $response = $this->prepareResponse(
            $routeWayDimensionService->getDimensionCalculateList($params),
            $request
        );

        return $this->view($response->body, $response->code, $response->headers);
    }

    /**
     * Создание/обновление коэфициента отношения габаритов по основному габариту.
     *
     * @Route("/customers/{customer_id}/dimension/{main_dimension_id}/calculate",
     *      name="create_update_dimension_calculate",
     *      methods={"PUT"},
     *      requirements={"customer_id":"\d+", "main_dimension_id": "\d+"}
     *     )
     *
     * @SWG\Parameter(
     *     name="customer_id",
     *     in="path",
     *     type="integer",
     *     description="ID заказчика, от лица которого происходит обновление габарита ТС",
     *     required=true
     * )
     *
     * @SWG\Parameter(
     *     name="main_dimension_id",
     *     in="path",
     *     type="integer",
     *     description="ID габарита ТС",
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
     *         type="array",
     *           items=@SWG\Schema(
     *           type="object",
     *           ref=@Model(type=DimensionCalculate::class, groups={"Create_update_demision_calculate"})
     *         )
     *     )
     * )
     *
     * @SWG\Response(
     *     response=200,
     *     description="Возвращает коллецию отношений габаритов",
     *     @SWG\Schema(
     *         type="array",
     *           items=@SWG\Schema(
     *           type="object",
     *           ref=@Model(type=DimensionCalculate::class)
     *         )
     *     )
     * )
     *
     * @SWG\Tag(name="Габариты")
     *
     * @ViewAnnotation(serializerGroups={"Default"})
     * @SerializerGroups({"Default"})
     *
     * @return JsonResponse
     */
    public function updateDimensionCalculatesByMainDimension(
        int $customer_id,
        int $main_dimension_id,
        Request $request,
        DimensionCalculateDeserializer $deserializer,
        RouteWayDimensionService $routeWayDimensionService
    ): JsonResponse {
        $params = $request->getContent();
        $dimensionCalculateDtoCollection = $deserializer->deserializeToApiDtoCollection($params);
        $response = $this->prepareResponse(
            $routeWayDimensionService->updateDimensionCalculatesByMainDimension($main_dimension_id, $dimensionCalculateDtoCollection),
            $request
        );

        return $this->view($response->body, $response->code, $response->headers);
    }
}

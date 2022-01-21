<?php

namespace App\Controller\Api;

use App\Annotations\SerializerGroups;
use App\Classes\Api\RestResponse;
use App\Classes\RequestResponse\ServiceResponse;
use Doctrine\Common\Annotations\AnnotationReader;
use FOS\RestBundle\Context\Context;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

class BaseApiController extends AbstractController
{
    protected const GROUPS_BY_LEVEL = [
        'minimum' => ['Minimum'],
    ];

    /**
     * Возвращает  контекст с группами на основе параметра detalizationLevel.
     *
     * @param ParameterBag $params
     *
     * @return Context $context
     */
    public function getContextFromRequest(Request $request)
    {
        $context = new Context();
        $detalizationLevel = $request->get('detalizationLevel');
        if ($detalizationLevel && \array_key_exists($detalizationLevel, self::GROUPS_BY_LEVEL)) {
            $context->setGroups(self::GROUPS_BY_LEVEL[$detalizationLevel]);
        }

        return $context;
    }

    public function prepareResponse(ServiceResponse $serviceResponse, Request $request)
    {
        $restResponse = new RestResponse();
        if ($serviceResponse->hasErrors()) {
            $restResponse->setCode(422);
            $output = [
                'code' => 422,
                'title' => 'Содержатся ошибки валидации',
                'errors' => [],
            ];
            foreach ($serviceResponse->getErrors() as $error) {
                $output['errors'][] = [
                    'propertyPath' => $error->getPropertyPath(),
                    'message' => $error->getMessage(),
                    'value' => $error->getInvalidValue(),
                ];
            }
            $restResponse->setBody($output);
        } else {
            if ('POST' === $request->getMethod()) {
                $restResponse->setCode(201);
            } else {
                $restResponse->setCode(200);
            }
            $restResponse->setBody($serviceResponse->getData());
        }

        return $restResponse;
    }

    protected function view($body, int $code, array $headers, $context = null)
    {
        if (!$context) {
            $reader = new AnnotationReader();
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            $groups = [];
            foreach ($trace as $backCall) {
                $reflClass = new \ReflectionMethod($backCall['class'], $backCall['function']);
                /** @var SerializerGroups|null $methodAnnotations */
                $methodAnnotations = $reader->getMethodAnnotation($reflClass, SerializerGroups::class);
                if ($methodAnnotations) {
                    $groups = $methodAnnotations->getGroups();

                    break;
                }
            }
            $context = [
                AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
                'groups' => $groups,
                AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object, $format, $context) {
                    return $object->getId();
                },
            ];
        }
        return $this->json($body, $code, $headers, $context);
    }
}

<?php

namespace App\Dto\Route;

use App\Dto\DtoInterface;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as JMS;

/**
 * @Assert\Callback(
 *     callback = {
 *          "App\Validator\Callbacks\Route\RouteDtoValidator",
 *          "validateCargoPipelines",
 *     }
 * )
 * @Assert\Callback(
 *     callback = {
 *          "App\Validator\Callbacks\Route\RouteDtoValidator",
 *          "validateRoute",
 *     }
 * )
 */
class ApiContractorRouteDto extends BaseRouteDto implements DtoInterface
{
    /**
     * @var array
     * @JMS\Exclude()
     */
    public $updateFields = [
        'updatedFrom',
        'transport',
        'trailer',
        'driverOne',
        'driverTwo',
    ];
}

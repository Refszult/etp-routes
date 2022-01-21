<?php

namespace App\Command\Node;

use App\Classes\RequestResponse\IBMMQResponse;
use App\Classes\RequestResponse\ServiceResponse;
use App\Classes\XmlTransformation\XmlTransformationFactory;
use App\Dto\Route\CommandRouteDto;
use App\Entity\Route\Route;
use App\Entity\Route\RouteWay;
use App\Entity\Tender\RouteTemplate;
use App\Entity\Tender\Tender;
use App\Exceptions\WrongObjectException;
use App\Model\Route\CustomerRouteModel;
use App\Service\Helper\FinderHelper;
use App\Service\IBMMQ\IBMMQPusher;
use App\Service\Route\CommandRouteService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Workflow\Registry;

class GenerateRoutesFromTenderCommand extends Command
{
    protected ValidatorInterface $validator;

    protected EntityManagerInterface $entityManager;

    protected ContainerInterface $container;

    protected ConstraintViolationList $errors;

    protected FinderHelper $finderHelper;

    protected Registry $workflowRegistry;

    protected CommandRouteService $routeService;

    protected static $defaultName = 'node:generate_tender_routes';

    public function __construct(
        EntityManagerInterface $entityManager,
        ContainerInterface $container,
        ValidatorInterface $validator,
        Registry $workflowRegistry,
        FinderHelper $finderHelper,
        CommandRouteService $routeService
    ) {
        $this->validator = $validator;
        $this->entityManager = $entityManager;
        $this->container = $container;
        $this->workflowRegistry = $workflowRegistry;
        $this->finderHelper = $finderHelper;
        $this->routeService = $routeService;
        $this->errors = new ConstraintViolationList();

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDescription('Generate tender`s routes')
            ->setHelp('This command generate routes for tender.')
            ->addArgument(
                'id',
                InputArgument::REQUIRED,
                'Tender ID'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tenderId = $input->getArgument('id');
        $io = new SymfonyStyle($input, $output);
        /** @var Tender $tender */
        $tender = $this->entityManager->getRepository(Tender::class)
            ->findOneBy(['id' => $tenderId]);
        if (Tender::STATUS_GENERATE_ROUTES === $tender->getStatus()) {
            $serial = 0;
            $tenderWorkflow = $this->workflowRegistry->get($tender);
            foreach ($tender->getRouteTemplates() as $routeTemplate) {
                /** @var \DateTime $periodStart */
                $periodStart = $routeTemplate->getPeriodStart();
                /** @var \DateTime $periodStop */
                $periodStop = $routeTemplate->getPeriodStop();
                if ($periodStart && $periodStop) {
                    $dateTemplates = $routeTemplate->getRouteDateTemplates();
                    if ($dateTemplates->count() > 0) {
                        foreach ($dateTemplates as $dateTemplate) {
                            $format = $dateTemplate->getDateTemplate();
                            $step = clone $periodStart;
                            $run = true;
                            while ($run) {
                                $step->modify($format);
                                if ($step < $periodStart) {
                                    $step->modify('monday next week');
                                    continue;
                                } else {
                                    if ($step > $periodStop) {
                                        $run = false;
                                    } else {
                                        ++$serial;
                                        $result = $this->createRoute($routeTemplate, $step);
                                        if ($result->hasErrors()) {
                                            foreach ($result->getErrors() as $error) {
                                                dump($error->getPropertyPath());
                                                dd($error->getMessage());
                                            }
                                            throw new WrongObjectException('Ошибка при генерации рейса из тендера - '. $tenderId);

                                        }
                                        $tender->addRoute($result->getData());
                                    }
                                }
                                $step->modify('monday next week');
                            }
                        }
                    }
                }
            }
            $tenderWorkflow->apply($tender, 'to_close');
            $this->entityManager->flush();
            $io->success('Генерация рейсов закончена. Создано рейсов: '.$serial);
            if (count($tender->getRoutes())) {
                $mqPusher = $this->container->get(IBMMQPusher::class);
                if ($mqPusher->canSend()) {
                    try {
                        $response = new IBMMQResponse();
                        $transformer = $this->container
                            ->get(XmlTransformationFactory::class)
                            ->build('CreateUpdateRoute', $response);
                        $queue = $mqPusher->connectToPush();
                        foreach ($tender->getRoutes() as $entity) {
                            if ($entity instanceof Route) {
                                try {
                                    $message = $transformer->createXmlFromObject($entity);
                                    $mqPusher->justPush($message, $queue);
                                } catch (\Throwable $exception) {
                                    throw new WrongObjectException('Не удалось провести обновление рейса.', $exception);
                                }
                            }
                        }
                        $mqPusher->disconnect($queue);
                    } catch (\Throwable $exception) {
                        $this->getApplication()->renderThrowable($exception, $io);
                    }
                    $io->success('Информация о рейсах успешно отправлена в очередь.');
                }
            } else {
                $io->error('Нет рейсов для отправки в очередь.');

                return 2;
            }
        } else {
            throw new WrongObjectException('Статус данного тендера не позволяет генерировать рейсы.');
        }

        return 1;
    }

    /**
     * Создание рейса на основе шаблона тендера.
     *
     * @throws Exception
     */
    protected function createRoute(RouteTemplate $routeTemplate, \DateTime $dateTime): ServiceResponse
    {
        $routeDto = new CommandRouteDto();
        $routeDto->setRouteWay($routeTemplate->getRouteWay());
        $routeDto->setPlanDateOfFirstPointArrive(clone $dateTime);
        $routeDto->setContractor($routeTemplate->getTender()->getWinner());
        $routeDto->setOrganization($routeTemplate->getTender()->getOrganization());

        return $this->routeService->createRoute($routeDto, $routeTemplate);
    }
}

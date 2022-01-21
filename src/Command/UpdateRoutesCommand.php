<?php

namespace App\Command;

use App\Classes\RequestResponse\IBMMQResponse;
use App\Entity\Route\Route;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateRoutesCommand extends BaseCommand
{
    protected static $defaultName = 'app:update_routes';

    protected function configure()
    {
        $this
            ->setDescription('Udpate Routes')
            ->setHelp('Update routes.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $response = new IBMMQResponse();
        $response->setConsoleType($io);
        try {
            $io->text('Start updating Routes');
            $flag = true;
            $limit = 5000;
            $offset = 0;
            $i = 0;
            while (true === $flag) {
                $routes = $this->entityManager->getRepository(Route::class)
                    ->findAllWithLimit($limit, $offset);
                if ($routes) {
                    /** @var Route $route */
                    foreach ($routes as $route) {
                        $route->setUpdatedOn(new \DateTime());
                        $this->entityManager->persist($route);
                    }
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                    $offset += $limit;
                    ++$i;
                    $io->text('Block '.$i.' finished');
                } else {
                    $flag = false;
                }
            }
            $this->entityManager->flush();
            $io->success('Routes updated Successfully.');
        } catch (\Throwable $exception) {
            if ($exception instanceof \Exception) {
                $this->getApplication()->renderException($exception, $io);
            } else {
                $io->text($exception);
            }

            $io->error('Error while updating Routes.');
        }
    }
}

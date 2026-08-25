<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Command;

use Agentur\SmartJobFinder\Event\JobPublishedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;

final class NotifyTestCommand extends Command
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ConnectionPool $connectionPool,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('uid', null, InputOption::VALUE_REQUIRED, 'Job uid to pretend was published');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $uid = (int)$input->getOption('uid');
        $record = $uid > 0 ? (BackendUtility::getRecord('tx_smartjobfinder_domain_model_job', $uid) ?? []) : [];

        if ($record === []) {
            $record = $this->connectionPool
                ->getConnectionForTable('tx_smartjobfinder_domain_model_job')
                ->select(
                    ['*'],
                    'tx_smartjobfinder_domain_model_job',
                    ['deleted' => 0],
                    [],
                    ['uid' => 'DESC'],
                    1,
                )
                ->fetchAssociative() ?: [];
            $uid = (int)($record['uid'] ?? 0);
        }

        if ($record === []) {
            $record = [
                'title' => 'Demo: Senior TYPO3 Engineer',
                'location' => 'Hamburg',
                'employment_type' => 'FULL_TIME',
                'slug' => 'demo-senior-typo3-engineer',
            ];
        }

        $this->eventDispatcher->dispatch(new JobPublishedEvent($uid, $record, 'new'));
        $output->writeln(sprintf(
            '<info>Dispatched JobPublishedEvent for uid %d (%s).</info>',
            $uid,
            (string)($record['title'] ?? 'demo'),
        ));

        return Command::SUCCESS;
    }
}

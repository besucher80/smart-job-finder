<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Service;

use Agentur\SmartJobFinder\Event\JobPublishedEvent;
use Agentur\SmartJobFinder\Notification\NotificationResult;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

final class NotificationLogWriter
{
    public const TABLE = 'tx_smartjobfinder_notification_log';

    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    public function write(JobPublishedEvent $event, NotificationResult $result): void
    {
        $this->connectionPool->getConnectionForTable(self::TABLE)->insert(
            self::TABLE,
            [
                'pid' => 0,
                'tstamp' => time(),
                'crdate' => time(),
                'job_uid' => $event->getUid(),
                'job_title' => mb_substr($event->getTitle(), 0, 255),
                'channel' => $result->channel,
                'status' => $result->status,
                'payload' => $result->payload,
                'message' => mb_substr($result->message, 0, 255),
            ],
            [
                Connection::PARAM_INT,
                Connection::PARAM_INT,
                Connection::PARAM_INT,
                Connection::PARAM_INT,
                Connection::PARAM_STR,
                Connection::PARAM_STR,
                Connection::PARAM_STR,
                Connection::PARAM_STR,
                Connection::PARAM_STR,
            ],
        );
    }
}

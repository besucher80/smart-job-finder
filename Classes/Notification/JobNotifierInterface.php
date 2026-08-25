<?php

declare(strict_types=1);

namespace Agentur\SmartJobFinder\Notification;

use Agentur\SmartJobFinder\Event\JobPublishedEvent;

interface JobNotifierInterface
{
    public function notify(JobPublishedEvent $event): NotificationResult;
}

<?php

require __DIR__ . '/vendor/autoload.php';

use Joli\JoliNotif\DefaultNotifier;
use Symfony\Component\Notifier\Bridge\JoliNotif\JoliNotifTransport;
use Symfony\Component\Notifier\Message\DesktopMessage;
use Symfony\Component\Notifier\Texter;

$texter = new Texter(new JoliNotifTransport(new DefaultNotifier()));

$message = new DesktopMessage(
    'New number ' . rand(0, 5000),
    'You have a new subscription on your website.',
);

$texter->send($message);

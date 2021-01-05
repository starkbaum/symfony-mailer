<?php


namespace App\EventListener;


use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\NamedAddress;

class SetFromListener implements EventSubscriberInterface
{
    public function onMessage(MessageEvent $event)
    {
        $email = $event->getMessage();

        if (!$email instanceof Email) {
            return;
        }

        $email->from(new NamedAddress('starkbaum.stefan@gmail.com', 'Stefan Starkbaum'));
    }

    public static function getSubscribedEvents()
    {
        return [
            MessageEvent::class => 'onMessage',
        ];
    }
}
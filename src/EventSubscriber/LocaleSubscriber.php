<?php

namespace App\EventSubscriber;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/** Applies the language chosen by the visitor, kept in the session. French by default. */
final class LocaleSubscriber implements EventSubscriberInterface
{
    public const SESSION_KEY = '_locale';

    /**
     * @param list<string> $enabledLocales
     */
    public function __construct(
        #[Autowire('%kernel.enabled_locales%')]
        private readonly array $enabledLocales,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$request->hasPreviousSession()) {
            return;
        }

        $locale = $request->getSession()->get(self::SESSION_KEY);
        if (\in_array($locale, $this->enabledLocales, true)) {
            $request->setLocale($locale);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Must run before Symfony's own LocaleListener (priority 16).
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }
}

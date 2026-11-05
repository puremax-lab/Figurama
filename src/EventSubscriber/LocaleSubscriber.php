<?php

namespace App\EventSubscriber;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Applies the language chosen by the visitor, kept in the session. Before any choice it
 * follows the browser: English stays English, any other language gives French, the default.
 */
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
        $locale = $request->hasPreviousSession() ? $request->getSession()->get(self::SESSION_KEY) : null;

        if (\in_array($locale, $this->enabledLocales, true)) {
            $request->setLocale($locale);
        } elseif (preg_match('/^\s*en\b/i', (string) $request->headers->get('Accept-Language'))) {
            $request->setLocale('en');
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

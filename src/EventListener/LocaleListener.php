<?php

namespace App\EventListener;

use App\Entity\User;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;

/**
 * Manage locale and timezone for a request, based on user's setting.
 *
 * @see https://symfony.com/doc/current/session/locale_sticky_session.html
 */
final class LocaleListener {
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var TranslatorInterface|LocaleAwareInterface
     */
    private $translator;

    /**
     * @var string[]
     */
    private $availableLocales;

    /**
     * @var string
     */
    private $defaultLocale;

    public function __construct(
        SessionInterface $session,
        TokenStorageInterface $tokenStorage,
        TranslatorInterface $translator,
        array $availableLocales,
        string $defaultLocale
    ) {
        if (!$translator instanceof LocaleAwareInterface) {
            throw new \InvalidArgumentException(
                '$translator must be instance of '.LocaleAwareInterface::class
            );
        }

        $this->session = $session;
        $this->tokenStorage = $tokenStorage;
        $this->translator = $translator;
        $this->availableLocales = $availableLocales;
        $this->defaultLocale = $defaultLocale;
    }

    public function onKernelRequest(RequestEvent $event) {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();

        if ($request->hasPreviousSession()) {
            $locale = $this->session->get('_locale');
            $timezone = $this->session->get('_tz');
        }

        if (!isset($locale)) {
            // Default locale must be first, or the wrong locale is used if
            // the Accept-Language header doesn't contain an available locale.
            $default = [$this->defaultLocale];

            $locale = $request->getPreferredLanguage(
                \array_merge($default, \array_diff($this->availableLocales, $default))
            );
        }

        if (!isset($timezone)) {
            $timezone = \ini_get('date.timezone') ?: 'UTC';
        }

        if (isset($locale)) {
            $request->setLocale($locale);
        }

        \date_default_timezone_set($timezone);
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event) {
        $user = $event->getAuthenticationToken()->getUser();

        if ($user instanceof User) {
            $locale = $user->getLocale();
            $this->session->set('_locale', $locale);
            $event->getRequest()->setLocale($locale);

            // Because security.interactive_login runs after kernel.request,
            // where the translator gets its locale, we must manually set the
            // locale on the translator. There is no way around this.
            $this->translator->setLocale($locale);

            $timezone = $user->getTimezone();
            $this->session->set('_tz', $timezone->getName());
            \date_default_timezone_set($timezone->getName());
        }
    }

    public function postUpdate(LifecycleEventArgs $args) {
        $user = $args->getEntity();

        if ($user instanceof User) {
            $token = $this->tokenStorage->getToken();

            if ($token && $token->getUser() === $user) {
                $this->session->set('_locale', $user->getLocale());
                $this->session->set('_tz', $user->getTimezone()->getName());
            }
        }
    }
}

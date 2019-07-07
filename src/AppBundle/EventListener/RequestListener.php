<?php

namespace AppBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Translation\TranslatorInterface;

class RequestListener
{
    protected $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $this->translator->setlocale(
            $event->getRequest()->get('locale')
        );
    }
}

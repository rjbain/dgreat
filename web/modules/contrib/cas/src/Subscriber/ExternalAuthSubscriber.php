<?php

namespace Drupal\cas\Subscriber;

use Drupal\externalauth\Event\ExternalAuthAuthmapAlterEvent;
use Drupal\externalauth\Event\ExternalAuthEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides a ExternalAuthSubscriber.
 */
class ExternalAuthSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ExternalAuthEvents::AUTHMAP_ALTER][] = ['stripCasPrefix'];
    return $events;
  }

  /**
   * The entry point for our subscriber.
   *
   * Externalauth module will add a "cas_" prefix to usernames that are
   * registered using the externalauth module. We don't want that, so remove
   * the prefix.
   *
   * @param \Drupal\externalauth\Event\ExternalAuthAuthmapAlterEvent $event
   *   The authmap alter event from the externalauth module.
   *
   * @see https://www.drupal.org/node/2798323
   */
  public function stripCasPrefix(ExternalAuthAuthmapAlterEvent $event) {
    if (strpos($event->getUsername(), 'cas_') === 0) {
      $event->setUsername(substr($event->getUsername(), 4));
    }
  }

}

<?php

namespace Drupal\recurring_donation\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\recurring_donation\Event\IPNMessageEvents;
use Drupal\recurring_donation\Event\IPNMessageReceivedEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;
use PayPal\IPN\PPIPNMessage;

/**
 * Class IPNController.
 *
 * @package Drupal\recurring_donation\Controller
 */
class IPNController extends ControllerBase {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(EventDispatcherInterface $event_dispatcher) {
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('event_dispatcher')
    );
  }

  /**
   * IPN Listener.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Response.
   */
  public function listen() {

    // Get current mode.
    $config = $this->config('recurring_donation.settings');

    if ($config->get('ipn.enabled') !== TRUE) {
      return new Response('', 401);
    }

    // Build IPN Message from POST data.
    $mode = $config->get('mode') === 'live' ? 'live' : 'sandbox';
    $ipnMessage = new PPIPNMessage(NULL, compact('mode'));
    $event = new IPNMessageReceivedEvent($ipnMessage);

    if ($config->get('ipn.logging') === TRUE) {
      $logMessage = 'IPN:<br/>' . PHP_EOL;
      foreach ($ipnMessage->getRawData() as $key => $value) {
        $logMessage .= $this->t('@key => @value', ['@key' => $key, '@value' => $value]) . '<br/>' . PHP_EOL;
      }
      $this->getLogger('recurring_donation')->info($logMessage);
    }

    // Validate & fire IPN message received event.
    if ($ipnMessage->validate()) {
      $responseMessage = 'Got valid IPN data';
      $this->getLogger('recurring_donation')->notice($responseMessage);
      $this->eventDispatcher->dispatch(IPNMessageEvents::VALID, $event);
    }
    else {
      $responseMessage = 'Got invalid IPN data';
      $this->getLogger('recurring_donation')->error($responseMessage);
      $this->eventDispatcher->dispatch(IPNMessageEvents::INVALID, $event);
    }

    return new Response($responseMessage, 200);
  }

}

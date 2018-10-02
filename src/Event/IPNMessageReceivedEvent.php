<?php

namespace Drupal\recurring_donation\Event;

use PayPal\IPN\PPIPNMessage;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class IPNMessageReceivedEvent.
 *
 * @package Drupal\recurring_donation\Event
 */
class IPNMessageReceivedEvent extends Event {

  /**
   * The IPN message.
   *
   * @var \PayPal\IPN\PPIPNMessage
   *   The IPN message.
   */
  protected $ipnMessage;

  /**
   * Constructs a new IPNMessageEvent.
   *
   * @param \PayPal\IPN\PPIPNMessage $ipnMessage
   *   The IPN message.
   */
  public function __construct(PPIPNMessage $ipnMessage) {
    $this->ipnMessage = $ipnMessage;
  }

  /**
   * Gets the IPN message.
   *
   * @return \PayPal\IPN\PPIPNMessage
   *   The IPN message.
   */
  public function getIpnMessage() {
    return $this->ipnMessage;
  }

}

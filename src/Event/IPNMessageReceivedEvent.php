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

  const EVENT_NAME = 'ipn_message_received';

  /**
   * The IPN message.
   *
   * @var \PayPal\IPN\PPIPNMessage
   *   The IPN message.
   */
  protected $ipnMessage;

  /**
   * Valid or invalid data.
   *
   * @var bool
   */
  protected $valid;

  /**
   * Constructs a new IPNMessageEvent.
   *
   * @param \PayPal\IPN\PPIPNMessage $ipnMessage
   *   The IPN message.
   * @param bool $valid
   *   TRUE or FALSE.
   */
  public function __construct(PPIPNMessage $ipnMessage, $valid) {
    $this->ipnMessage = $ipnMessage;
    $this->valid = $valid;
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

  /**
   * Gets the IPN message.
   *
   * @return bool
   *   TRUE or FALSE.
   */
  public function ipnMessageIsValid() {
    return $this->valid;
  }

}

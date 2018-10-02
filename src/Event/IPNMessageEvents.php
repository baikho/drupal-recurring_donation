<?php

namespace Drupal\recurring_donation\Event;

/**
 * Defines IPN events for the recurring_donation module.
 */
final class IPNMessageEvents {

  /**
   * Valid IPN message received event.
   *
   * @Event
   */
  const VALID = 'ipn.message.valid';

  /**
   * Invalid IPN message received event.
   *
   * @Event
   */
  const INVALID = 'ipn.message.invalid';

}

<?php

namespace Drupal\recurring_donation;

/**
 * Class DonationTypes.
 *
 * @package Drupal\recurring_donation
 */
class DonationTypes {

  /**
   * One-off donation type.
   */
  const SINGLE = 'single';

  /**
   * Recurring donation type.
   */
  const RECURRING = 'recurring';

  /**
   * {@inheritdoc}
   */
  public static function getTypes() {
    return [
      self::SINGLE,
      self::RECURRING,
    ];
  }

}

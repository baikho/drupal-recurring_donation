<?php

namespace Drupal\recurring_donation;

/**
 * Class DonationType.
 *
 * @package Drupal\recurring_donation
 */
class DonationType {

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
  public static function getAll() {
    return [
      self::SINGLE,
      self::RECURRING,
    ];
  }

}

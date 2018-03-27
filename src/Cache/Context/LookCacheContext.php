<?php

namespace Drupal\look\Cache\Context;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\look\LookConfig;

/**
 * Defines the LookCacheContext service, for "per look" caching.
 *
 * Cache context ID: 'look'.
 */
class LookCacheContext implements CacheContextInterface {

  /**
   * The look config.
   *
   * @var \Drupal\look\LookConfig
   */
  protected $lookConfig;

  /**
   * Constructs a new LookCacheContext instance.
   *
   * @param \Drupal\look\LookConfig $look_config
   *   The look config service.
   */
  public function __construct(LookConfig $look_config) {
    $this->lookConfig = $look_config;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Look');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    $look = $this->lookConfig->get();
    return Crypt::hashBase64(serialize($look));
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}

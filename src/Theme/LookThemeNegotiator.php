<?php

namespace Drupal\look\Theme;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Drupal\look\LookConfig;

/**
 * Determines the current look theme.
 */
class LookThemeNegotiator implements ThemeNegotiatorInterface {

  /**
   * The look config.
   *
   * @var \Drupal\look\LookConfig
   */
  protected $lookConfig;

  /**
   * Constructs a new LookThemeNegotiator instance.
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
  public function applies(RouteMatchInterface $route_match) {
    $look = $this->lookConfig->get();
    return !empty($look['config']['look_theme']);
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    $look = $this->lookConfig->get();
    return $look['config']['look_theme'];
  }

}

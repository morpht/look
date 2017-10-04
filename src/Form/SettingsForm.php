<?php

namespace Drupal\look\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides configuration form for Look module.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Constructs a new SettingsForm instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Connection $connection, EntityFieldManagerInterface $entity_field_manager, ThemeHandlerInterface $theme_handler) {
    parent::__construct($config_factory);

    $this->connection = $connection;
    $this->entityFieldManager = $entity_field_manager;
    $this->themeHandler = $theme_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('database'),
      $container->get('entity_field.manager'),
      $container->get('theme_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'look.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'look_settings_form';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $theme
   *   The theme name.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $theme = '') {
    $config = $this->config('look.settings');

    if (empty($theme)) {

      // Get all looks ordered by name.
      $query = $this->connection->select('look_field_data', 'l')
        ->fields('l', ['id', 'name'])
        ->orderBy('l.name', 'ASC');
      $result = $query->execute()->fetchAllKeyed();

      $form['default'] = [
        '#type' => 'select',
        '#title' => $this->t('Default look'),
        '#options' => ['' => $this->t('- None -')] + $result,
        '#default_value' => $config->get('default'),
        '#description' => $this->t('Select the default look to be applied.'),
        '#required' => FALSE,
      ];

      $form['default_admin'] = [
        '#type' => 'select',
        '#title' => $this->t('Default administration look'),
        '#options' => ['' => $this->t('- None -')] + $result,
        '#default_value' => $config->get('default_admin'),
        '#description' => $this->t('Select the default look to be applied for administration pages.'),
        '#required' => FALSE,
      ];
    }
    else {

      $form['theme'] = [
        '#type' => 'hidden',
        '#value' => $theme,
      ];

      $form['mapping'] = [
        '#type' => 'table',
        '#header' => [
          $this->t('Label'),
          $this->t('Machine name'),
          $this->t('Selector'),
          $this->t('Weight'),
        ],
        '#tabledrag' => [
          [
            'action' => 'order',
            'relationship' => 'sibling',
            'group' => 'mapping-order-weight',
          ],
        ],
      ];

      // Get mappings for specific theme.
      $mapping = $config->get('mapping.' . $theme);
      // Get all fields on look entity.
      $look_fields = $this->entityFieldManager->getFieldDefinitions('look', 'look');

      $fields = [];
      // Add existing look fields for rendering inside table.
      if (!empty($mapping)) {
        foreach ($mapping as $name => $field) {
          if (isset($look_fields[$name])) {
            $fields[$name] = $look_fields[$name];
            unset($look_fields[$name]);
          }
        }
      }
      // Add new look fields for rendering inside table.
      foreach ($look_fields as $name => $field) {
        $fields[$name] = $field;
      }

      /** @var \Drupal\Core\Field\FieldDefinitionInterface $field */
      foreach ($fields as $name => $field) {
        $definition = $field->getFieldStorageDefinition();

        // Support only entity reference fields.
        if (!$definition->isBaseField() && $definition->getMainPropertyName() === 'target_id') {
          $label = $field->getLabel();

          // Get current field weight and selector.
          $weight = isset($mapping[$name]['weight']) ? $mapping[$name]['weight'] : 50;
          $selector = !empty($mapping[$name]['selector']) ? $mapping[$name]['selector'] : '';

          $form['mapping'][$name] = [
            '#attributes' => ['class' => ['draggable']],
            '#weight' => $weight,
            'label' => [
              '#plain_text' => $label,
            ],
            'name' => [
              '#plain_text' => $name,
            ],
            'selector' => [
              '#type' => 'textfield',
              '#title' => $this->t('Selector for @title', ['@title' => $label]),
              '#title_display' => 'invisible',
              '#default_value' => $selector,
            ],
            'weight' => [
              '#type' => 'weight',
              '#title' => $this->t('Weight for @title', ['@title' => $label]),
              '#title_display' => 'invisible',
              '#default_value' => $weight,
              '#attributes' => ['class' => ['mapping-order-weight']],
              '#delta' => 50,
            ],
          ];
        }
      }
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('look.settings');

    if ($form_state->hasValue('theme')) {
      $theme = $form_state->getValue('theme');
      $config->set('mapping.' . $theme, $form_state->getValue('mapping'));
    }
    else {
      $config->set('default', $form_state->getValue('default'));
      $config->set('default_admin', $form_state->getValue('default_admin'));
    }
    $config->save();
  }

  /**
   * Gets the form title with human readable name of a given theme.
   *
   * @param string $theme
   *   The machine name of the theme.
   *
   * @return string
   *   Returns the form title.
   */
  public function getFormTitle($theme) {
    $name = $this->themeHandler->getName($theme);
    return $this->t('Look mappings') . ' - ' . $name;
  }

}

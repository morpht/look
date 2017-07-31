<?php

namespace Drupal\look\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\look\Entity\Look;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides look collection form for Look module.
 */
class LookCollectionForm extends FormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new LookCollectionForm instance.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(Connection $connection, RendererInterface $renderer) {
    $this->connection = $connection;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'look_collection_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Get all looks ordered by weight.
    $query = $this->connection->select('look_field_data', 'l')
      ->fields('l', ['id', 'name', 'parent', 'weight'])
      ->orderBy('l.weight', 'ASC');
    $result = $query->execute()->fetchAll();

    $form['looks'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Name'),
        $this->t('Weight'),
        $this->t('Operations'),
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'look-weight',
        ],
        [
          'action' => 'match',
          'relationship' => 'parent',
          'group' => 'look-parent',
          'subgroup' => 'look-parent',
          'source' => 'look-id',
          'hidden' => FALSE,
        ],
        [
          'action' => 'depth',
          'relationship' => 'group',
          'group' => 'look-depth',
          'hidden' => FALSE,
        ],
      ],
      '#attributes' => [
        'id' => 'look',
      ],
      '#empty' => $this->t('No looks available.'),
    ];

    // Get basic information about form state.
    $errors = $form_state->getErrors();
    $destination = $this->getDestinationArray();
    // Count weight element range based on number of looks.
    $delta = count($result);
    // Initialize variable with parent structure.
    $path = [];

    // Process all rows of table.
    foreach ($result as $key => $look) {

      // Reset depth and path for first level items.
      if (empty($look->parent)) {
        $depth = 0;
        $path = [$look->id];
      }
      else {
        // Count depth and parent path for current look.
        $parent_key = array_search($look->parent, $path);
        $depth = $parent_key + 1;
        $path[$depth] = $look->id;
        $path = array_slice($path, 0, $depth + 1);
      }

      // Add indentation for child looks.
      if ($depth > 0) {
        $indentation = [
          '#theme' => 'indentation',
          '#size' => $depth,
        ];
      }
      $form['looks'][$key] = [
        'look' => [
          '#prefix' => !empty($indentation) ? $this->renderer->render($indentation) : '',
          '#type' => 'link',
          '#title' => $look->name,
          '#url' => Url::fromRoute('entity.look.canonical', ['look' => $look->id]),
          'id' => [
            '#type' => 'hidden',
            '#value' => $look->id,
            '#attributes' => [
              'class' => ['look-id'],
            ],
          ],
          'parent' => [
            '#type' => 'hidden',
            '#default_value' => $look->parent,
            '#attributes' => [
              'class' => ['look-parent'],
            ],
          ],
          'depth' => [
            '#type' => 'hidden',
            '#default_value' => $depth,
            '#attributes' => [
              'class' => ['look-depth'],
            ],
          ],
        ],
        'weight' => [
          '#type' => 'weight',
          '#title' => $this->t('Weight for @title', ['@title' => $look->name]),
          '#title_display' => 'invisible',
          '#default_value' => $look->weight,
          '#attributes' => ['class' => ['look-weight']],
          '#delta' => $delta,
        ],
        'operations' => [
          '#type' => 'operations',
          '#links' => [
            'edit' => [
              'title' => $this->t('Edit'),
              'query' => $destination,
              'url' => Url::fromRoute('entity.look.edit_form', ['look' => $look->id]),
            ],
            'delete' => [
              'title' => $this->t('Delete'),
              'query' => $destination,
              'url' => Url::fromRoute('entity.look.delete_form', ['look' => $look->id]),
            ],
          ],
        ],
        '#attributes' => ['class' => ['draggable']],
      ];

      // Add an error class if this row contains a form error.
      foreach ($errors as $error_key => $error) {
        if (strpos($error_key, $key) === 0) {
          $form['looks'][$key]['#attributes']['class'][] = 'error';
        }
      }
    }

    $form['actions'] = [
      '#type' => 'actions',
      '#tree' => FALSE,
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $rows = $form_state->getValue('looks');
    $weight = 1;

    // Get all looks ordered by weight.
    $query = $this->connection->select('look_field_data', 'l')
      ->fields('l', ['id', 'name', 'parent', 'weight'])
      ->orderBy('l.weight', 'ASC');
    $result = $query->execute()->fetchAllAssoc('id');

    // Process all rows of table.
    foreach ($rows as $row) {
      // Get current look ID and new parent ID.
      $id = $row['look']['id'];
      $parent = (int) $row['look']['parent'];
      // Get existing values of current look.
      $look = $result[$id];

      // Update look entity only if parent or weight was changed.
      if ((int) $look->parent !== $parent || $look->weight !== $weight) {
        /** @var \Drupal\look\Entity\Look $look */
        $look = Look::load($id);
        $look->setParent($parent);
        $look->setWeight($weight);
        $look->save();
      }
      $weight++;
    }
  }

}

<?php

namespace Drupal\look\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\look\Entity\LookInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LookController.
 *
 * Returns responses for Look routes.
 */
class LookController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new LookController instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer, DateFormatterInterface $date_formatter) {
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('renderer'),
      $container->get('date.formatter')
    );
  }

  /**
   * Displays a Look revision.
   *
   * @param int $look_revision
   *   The Look revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($look_revision) {
    $look = $this->entityTypeManager->getStorage('look')
      ->loadRevision($look_revision);
    $view_builder = $this->entityTypeManager->getViewBuilder('look');

    return $view_builder->view($look);
  }

  /**
   * Page title callback for a Look revision.
   *
   * @param int $look_revision
   *   The Look revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($look_revision) {
    $look = $this->entityTypeManager->getStorage('look')
      ->loadRevision($look_revision);
    return $this->t('Revision of %title from %date', [
      '%title' => $look->label(),
      '%date' => $this->dateFormatter->format($look->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a Look .
   *
   * @param \Drupal\look\Entity\LookInterface $look
   *   A Look object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(LookInterface $look) {
    $account = $this->currentUser();
    $langcode = $look->language()->getId();
    $langname = $look->language()->getName();
    $languages = $look->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $look_storage = $this->entityTypeManager->getStorage('look');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', [
      '@langname' => $langname,
      '%title' => $look->label(),
    ]) : $this->t('Revisions for %title', ['%title' => $look->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all look revisions") || $account->hasPermission('administer look entities')));
    $delete_permission = (($account->hasPermission("delete all look revisions") || $account->hasPermission('administer look entities')));

    $rows = [];

    $vids = $look_storage->revisionIds($look);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\look\Entity\LookInterface $revision */
      $revision = $look_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)
        ->isRevisionTranslationAffected()
      ) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = $this->dateFormatter->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $look->getRevisionId()) {
          $link = Link::createFromRoute($date, 'entity.look.revision', [
            'look' => $look->id(),
            'look_revision' => $vid,
          ])->toString();
        }
        else {
          $link = $look->link($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => $this->renderer->renderPlain($username),
              'message' => [
                '#markup' => $revision->getRevisionLogMessage(),
                '#allowed_tags' => Xss::getHtmlTagList(),
              ],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => $has_translations ?
              Url::fromRoute('entity.look.revision_revert_translation_confirm', [
                'look' => $look->id(),
                'look_revision' => $vid,
                'langcode' => $langcode,
              ]) :
              Url::fromRoute('entity.look.revision_revert_confirm', [
                'look' => $look->id(),
                'look_revision' => $vid,
              ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.look.revision_delete_confirm', [
                'look' => $look->id(),
                'look_revision' => $vid,
              ]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['look_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}

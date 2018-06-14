<?php

namespace Drupal\look\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Look entity.
 *
 * @ingroup look
 *
 * @ContentEntityType(
 *   id = "look",
 *   label = @Translation("Look"),
 *   handlers = {
 *     "storage" = "Drupal\look\LookStorage",
 *     "storage_schema" = "Drupal\look\LookStorageSchema",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\look\Entity\LookViewsData",
 *     "translation" = "Drupal\look\LookTranslationHandler",
 *     "form" = {
 *       "default" = "Drupal\look\Form\LookForm",
 *       "add" = "Drupal\look\Form\LookForm",
 *       "edit" = "Drupal\look\Form\LookForm",
 *       "delete" = "Drupal\look\Form\LookDeleteForm",
 *     },
 *     "access" = "Drupal\look\LookAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "look",
 *   data_table = "look_field_data",
 *   revision_table = "look_revision",
 *   revision_data_table = "look_field_revision",
 *   show_revision_ui = TRUE,
 *   translatable = TRUE,
 *   admin_permission = "administer look entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/look/{look}",
 *     "add-form" = "/admin/structure/look/add",
 *     "edit-form" = "/admin/structure/look/{look}/edit",
 *     "delete-form" = "/admin/structure/look/{look}/delete",
 *     "version-history" = "/admin/structure/look/{look}/revisions",
 *     "revision" = "/admin/structure/look/{look}/revisions/{look_revision}/view",
 *     "collection" = "/admin/structure/look",
 *   },
 *   field_ui_base_route = "entity.look.collection"
 * )
 */
class Look extends RevisionableContentEntityBase implements LookInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }

    // If no revision author has been set explicitly, make the look owner the
    // revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    // Check if some deleted entity has children for reassigning.
    foreach ($entities as $entity) {
      /** @var \Drupal\look\LookStorageInterface $storage */
      $children = $storage->loadChildren($entity->id());

      /** @var \Drupal\look\Entity\Look $child */
      foreach ($children as $child) {
        // Reassign child to the parent of deleted entity.
        $child->setParent($entity->getParent());
        $child->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getParent() {
    return $this->get('parent')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setParent($parent) {
    $this->set('parent', $parent);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->get('weight')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->set('weight', $weight);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Look entity.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 255,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -10,
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Look entity.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setSettings([
        'target_type' => 'user',
        'handler' => 'default',
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['parent'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Parent'))
      ->setDescription(t('The parent of this look in relation to other looks.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setSettings([
        'target_type' => 'look',
        'handler' => 'default',
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Weight'))
      ->setDescription(t('The weight of this look in relation to other looks.'))
      ->setRequired(TRUE)
      ->setDefaultValue(1000000)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Look is published.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['revision_translation_affected'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Revision translation affected'))
      ->setDescription(t('Indicates if the last edit of a translation belongs to current revision.'))
      ->setReadOnly(TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    return $fields;
  }

}

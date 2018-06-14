<?php

namespace Drupal\look\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Look edit forms.
 *
 * @ingroup look
 */
class LookForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $insert = $entity->isNew();
    $entity->save();

    if ($insert) {
      drupal_set_message($this->t('Created the %label Look.', [
        '%label' => $entity->label(),
      ]));
    }
    else {
      drupal_set_message($this->t('Saved the %label Look.', [
        '%label' => $entity->label(),
      ]));
    }
    $form_state->setRedirect('entity.look.canonical', ['look' => $entity->id()]);
  }

}

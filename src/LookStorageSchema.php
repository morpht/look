<?php

namespace Drupal\look;

use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the look schema handler.
 */
class LookStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();

    if ($table_name === 'look_field_data') {
      switch ($field_name) {
        case 'name':
          $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE);
          break;

        case 'weight':
          $schema['fields'][$field_name]['not null'] = TRUE;
          break;
      }
    }

    return $schema;
  }

}

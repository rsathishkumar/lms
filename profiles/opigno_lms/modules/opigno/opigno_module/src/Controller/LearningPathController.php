<?php

namespace Drupal\opigno_module\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\opigno_module\Entity\OpignoModule;
use Symfony\Component\HttpFoundation\Request;
use Drupal\opigno_module\Entity\OpignoActivity;

/**
 * Class LearningPathController.
 */
class LearningPathController extends ControllerBase {

  /**
   * Add index.
   */
  public function addIndex() {
    $opigno_module = OpignoModule::create();
    $form = \Drupal::service('entity.form_builder')->getForm($opigno_module);
    return $form;
  }

  /**
   * Edit index.
   */
  public function editIndex($opigno_module) {
    return \Drupal::service('entity.form_builder')->getForm($opigno_module);
  }

  /**
   * Duplicate index.
   */
  public function duplicateModule($opigno_module) {
    $duplicate = $opigno_module->createDuplicate();
    $current_name = $duplicate->label();
    $duplicate->setName($this->t('Duplicate of ') . $current_name);
    $activities = $opigno_module->getModuleActivities();
    $current_time = \Drupal::time()->getCurrentTime();
    $add_activities = [];

    foreach ($activities as $activity) {
      $add_activities[] = OpignoActivity::load($activity->id);
    }

    $duplicate->setOwnerId(\Drupal::currentUser()->id());
    $duplicate->set('created', $current_time);
    $duplicate->set('changed', $current_time);
    $duplicate->save();
    $duplicate_id = $duplicate->id();
    $opigno_module_obj = \Drupal::service('opigno_module.opigno_module');
    $opigno_module_obj->activitiesToModule($add_activities, $duplicate);

    return $this->redirect('opigno_module.edit', [
      'opigno_module' => $duplicate_id,
    ]);
  }

  /**
   * Modules index.
   */
  public function modulesIndex($opigno_module, Request $request) {
    return [
      '#theme' => 'opigno_learning_path_modules',
      '#attached' => ['library' => ['opigno_group_manager/manage_app']],
      '#base_path' => $request->getBasePath(),
      '#base_href' => $request->getPathInfo(),
      '#learning_path_id' => $opigno_module->id(),
      '#module_context' => 'true',
    ];
  }

  /**
   * Activities bank.
   */
  public function activitiesBank($opigno_module) {
    // Output activities bank view.
    $activities_bank['activities_bank'] = views_embed_view('opigno_activities_bank_lp_interface');

    $build[] = $activities_bank;

    return $build;
  }

}

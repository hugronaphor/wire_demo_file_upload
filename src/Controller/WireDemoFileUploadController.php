<?php

namespace Drupal\wire_demo_file_upload\Controller;

use Drupal\Core\Controller\ControllerBase;

class WireDemoFileUploadController extends ControllerBase {

  public function build() {

    $build['content'] = [
      '#type' => 'wire',
      '#id' => 'drag_and_drop_file_upload',
    ];

    return $build;
  }

}

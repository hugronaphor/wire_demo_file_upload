<?php

namespace Drupal\wire_demo_file_upload\Plugin\WireComponent;

use Drupal\file\Entity\File;
use Drupal\wire\TemporaryUploadedFile;
use Drupal\wire\View;
use Drupal\wire\WireComponent;
use Drupal\wire\WithFileUploads;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Implementation for DragAndDropFileUpload Wire Component.
 *
 * @WireComponent(
 *   id = "drag_and_drop_file_upload",
 *   label = @Translation("DragAndDropFileUpload"),
 * )
 */
class DragAndDropFileUpload extends WireComponent {

  use WithFileUploads;

  protected ?AccountInterface $account;

  public $myFile = NULL;

  public string $message;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->account = $container->get('current_user');
    return $instance;
  }

  public function save(): void {

    $this->resetValidation();
    $this->message = '';

    try {
      if ($file = $this->getFile()) {

        $file->save();
        $this->myFile = NULL;

        if (!$file->id()) {
          $this->myFile = NULL;
          throw new \Exception('File could not be saved.');
        }

        $this->message = sprintf('File created successfully with ID: %s.', $file->id());
      }
      else {
        $this->addError('myFile', 'Please select a file.');
      }

    } catch (\Exception $e) {
      watchdog_exception('wire_demo_file_upload', $e);
      $this->addError('myFile', 'Something went wrong while saving the file.');
    }

  }

  public function updatingMyFile(): void {
    $this->message = '';
  }

  public function removeMyFile(): void {
    isset($this->myFile) && $this->removeUpload('myFile', $this->myFile->getFilename());
  }

  private function getFile(): ?File {

    $tempFile = $this->myFile ?? NULL;
    if (!$tempFile instanceof TemporaryUploadedFile) {
      return NULL;
    }

    $filepath = $tempFile->store();

    return File::create([
      'filename' => basename($filepath),
      'uri' => $filepath,
      'status' => 0,
      'uid' => $this->account->id(),
    ]);

  }

  public function render(): ?View {
    return View::fromTpl('drag_and_drop_file_upload');
  }

}

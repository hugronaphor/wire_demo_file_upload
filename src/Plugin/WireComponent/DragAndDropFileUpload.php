<?php

namespace Drupal\wire_demo_file_upload\Plugin\WireComponent;

use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\wire\TemporaryUploadedFile;
use Drupal\wire\View;
use Drupal\wire\WireComponent;
use Drupal\wire\WithFileUploads;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;
use function Hugronaphor\PhpHelpers\rescue;

/**
 * Implementation for DragAndDropFileUpload Wire Component.
 *
 * @WireComponent(
 *   id = "drag_and_drop_file_upload",
 *   label = @Translation("Drag And Drop File Upload"),
 * )
 */
class DragAndDropFileUpload extends WireComponent {

  use WithFileUploads;

  protected ?AccountInterface $account;

  public $myFile = NULL;

  public ?string $successMessage;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->account = $container->get('current_user');
    return $instance;
  }

  public function save(): void {

    $this->resetValidation();

    $this->successMessage = rescue(
      callback: function () {
        $tempFile = $this->myFile;
        $this->myFile = NULL;

        if (!$file = $this->getFile($tempFile)) {
          $this->addError('myFile', 'Please select a file.');
          return NULL;
        }

        $file->save();
        throw_if(!$file instanceof FileInterface, new \Exception('File could not be saved.'));

        return sprintf('File created successfully with ID: %s.', $file->id());
      },
      rescue: function () {
        $this->myFile = NULL;
        $this->addError('myFile', 'Something went wrong while saving the file.');
      },
    );

  }

  public function updatingMyFile(): void {
    $this->successMessage = NULL;
  }

  public function removeMyFile(): void {
    isset($this->myFile) && $this->removeUpload('myFile', $this->myFile->getFilename());
  }

  private function getFile($tempFile): ?File {

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

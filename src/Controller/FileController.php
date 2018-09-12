<?php

namespace Drupal\itc_jsonapi\Controller;

use Drupal\Component\Uuid\Php;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 */
class FileController implements ContainerInjectionInterface {

  /**
   * @var \Drupal\Core\Flood\FloodInterface
   */
  protected $flood;

  /**
   * @var \Drupal\Component\Uuid\Php
   */
  protected $uuid;

  /**
   * @var \Symfony\Component\HttpKernel\Kernel
   */
  protected $kernel;

  /**
   *
   */
  public function __construct(FloodInterface $flood, Php $uuid, DrupalKernel $kernel) {
    $this->flood = $flood;
    $this->uuid = $uuid;
    $this->kernel = $kernel;
  }

  /**
   *
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('flood'),
      $container->get('uuid'),
      $container->get('kernel')
    );
  }

  /**
   *
   */
  public function fileUpload(Request $request) {
    $method = $request->getMethod();
    if ($method !== 'POST') {
      $response = new JsonResponse([
        'errors' => [
          'Only POST request allowed.',
        ],
      ], 405);
      $response->headers->set('content-type', 'application/vnd.api+json');
      return $response;
    }
    $threshold = 20;
    $request_files = $request->files->get('files', []);
    if (
      $this->flood->isAllowed(__METHOD__, $threshold)
      && (!is_array($request_files) || count($request_files) <= $threshold)
    ) {
      $destination = 'public://user/uploads/' . $this->uuid->generate();
      file_prepare_directory($destination, FILE_CREATE_DIRECTORY);
      $files = file_save_upload('file', [
        'file_validate_extensions' => ['doc docx odt odf jpeg jpg png pdf'],
        // Max size 8MB.
        'file_validate_size'       => [8388608],
      ], $destination);
      if (!empty($files)) {
        $data = [];
        foreach ($files as $file) {
          if (!empty($file)) {
            $this->flood->register(__METHOD__);
            $uri = Url::fromRoute('jsonapi.file--file.individual', ['file' => $file->uuid()])
              ->toString(TRUE)
              ->getGeneratedUrl();
            $response = $this->kernel->handle(Request::create($uri), 'GET');
            $data[] = json_decode($response->getContent(), TRUE)['data'];
          }
        }
        $response = new JsonResponse(['data' => $data]);
        $response->headers->set('content-type', 'application/vnd.api+json');
        return $response;
      }
    }
    $response = new JsonResponse([
      'errors' => [
        'Limit reached. You can upload ' . $threshold . ' files per hour.',
      ],
    ], 403);
    $response->headers->set('content-type', 'application/vnd.api+json');
    return $response;
  }

}

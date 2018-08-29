<?php
/**
 * Created by PhpStorm.
 * User: bertrand
 * Date: 04/05/18
 * Time: 17:38
 */

namespace Drupal\itc_jsonapi\Controller;


use Drupal\Core\Entity\EntityInterface;
use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformSubmissionForm;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class WebformController {

  const JSON_API_TYPE = 'application/vnd.api+json';

  protected function jsonApiResponse($content = NULL, $status = 200) {
    $response = new JsonResponse($content, $status);
    $response->headers->set('Content-Type', self::JSON_API_TYPE);
    return $response;
  }

  protected function getJsonBodyOrErrorResponse(Request $request) {
    $data = json_decode($request->getContent(), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
      return $this->jsonApiResponse([
        'errors' => [
          'Invalid data type. Only json data supported',
        ]
      ]);
    }
    return $data;
  }

  public function validate(Request $request, Webform $webform) {
    $data = $this->getJsonBodyOrErrorResponse($request);
    if ($data instanceof JsonResponse) {
      return $data;
    }
    $values = [
      'webform_id' => $webform->id(),
      'data' => $data,
    ];
    return $this->jsonApiResponse([
      'errors' => WebformSubmissionForm::validateFormValues($values)
    ]);
  }

  public function submit(Request $request, Webform $webform) {
    $data = $this->getJsonBodyOrErrorResponse($request);
    if ($data instanceof JsonResponse) {
      return $data;
    }
   
    $data['data'] = $this->formatEmptyFields($data['data'], $webform);
    $values = [
      'webform_id' => $webform->id(),
      'data' => $data['data'],
    ];
    $confirmation_message = $webform->getSetting('confirmation_message');
    $result = WebformSubmissionForm::submitFormValues($values);
    if ($result instanceof EntityInterface) {
      return $this->jsonApiResponse([
        'meta' => [
          'confirmation_message' => $confirmation_message,
        ],
      ]);
    }
    return $this->jsonApiResponse([
      'errors' => $result,
    ], 400);
  }

  private function formatEmptyFields($submission, Webform $webform) {
    $fields = $webform->getElementsDecoded();
    foreach($fields as $key => $settings) {
      // for non mandatory fields only
      if(empty($settings['#required'])){
        !empty($submission[$key]) ?: $submission[$key] = '';
      }
    }
    return $submission;
  }

}
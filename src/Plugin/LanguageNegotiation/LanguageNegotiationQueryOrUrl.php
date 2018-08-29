<?php

namespace Drupal\itc_jsonapi\Plugin\LanguageNegotiation;

use Drupal\Core\Language\LanguageInterface;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class for identifying language via query parameter or URL prefix or domain.
 *
 * @LanguageNegotiation(
 *   id = \Drupal\itc_jsonapi\Plugin\LanguageNegotiation\LanguageNegotiationQueryOrUrl::METHOD_ID,
 *   types = {\Drupal\Core\Language\LanguageInterface::TYPE_INTERFACE,
 *   \Drupal\Core\Language\LanguageInterface::TYPE_CONTENT,
 *   \Drupal\Core\Language\LanguageInterface::TYPE_URL},
 *   weight = -8,
 *   name = @Translation("Query or URL"),
 *   description = @Translation("Language from the query or URL (Path prefix or domain)."),
 *   config_route_name = "language.negotiation_url"
 * )
 */
class LanguageNegotiationQueryOrUrl extends LanguageNegotiationUrl {

  /**
   * The language negotiation method id.
   */
  const METHOD_ID = 'language-query-url';

  public function getLangcode(Request $request = NULL) {
    if ($request) {
      $pathInfo = $request->getPathInfo();
      if (strpos($pathInfo, '/jsonapi') === 0) {
        if ($request->query->has('langcode')) {
          $language = $this->languageManager->getLanguage($request->query->get('langcode'));
          if ($language instanceof LanguageInterface) {
            return $language->getId();
          }
        }
        if ($request->query->has('filter')) {
          $filter = $request->query->get('filter');
          if (is_array($filter)) {
            if (isset($filter['langcode']['value'])) {
              $langcode = $filter['langcode']['value'];
              $language = $this->languageManager->getLanguage($langcode);
              if ($language instanceof LanguageInterface) {
                return $language->getId();
              }
            }
          }
        }
      }
    }
    return parent::getLangcode($request);
  }
}
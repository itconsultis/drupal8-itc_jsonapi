<?php
/**
 * Created by PhpStorm.
 * User: bertrand
 * Date: 13/12/17
 * Time: 15:53
 */

namespace Drupal\itc_jsonapi\Plugin\jsonapi\FieldEnhancer;


use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerBase;
use Shaper\Util\Context;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Better address normalizer. Add labels do subdivisions.
 *
 * @ResourceFieldEnhancer(
 *   id = "address",
 *   label = @Translation("Address"),
 *   description = @Translation("Add labels to subdivisions")
 * )
 */
class AddressEnhancer extends ResourceFieldEnhancerBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\address\Repository\SubdivisionRepository
   */
  protected $subdivisionRepository;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $subdivision_repository = $container->has('address.subdivision_repository') ?
      $container->get('address.subdivision_repository') :
      NULL;
    return new static($configuration, $plugin_id, $plugin_definition, $subdivision_repository);
  }

  /**
   * AddressEnhancer constructor.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param null $subdivision_repository
   *   No type checking. Address module might not be installed.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $subdivision_repository = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->subdivisionRepository = $subdivision_repository;
  }

  /**
   * @param \CommerceGuys\Addressing\Subdivision\Subdivision $subdivision
   */
  protected function normalizeSubdivision($subdivision) {
    return [
      'name' => $subdivision->getName(),
      'localName' => $subdivision->getLocalName(),
      'code' => $subdivision->getCode(),
      'localCode' => $subdivision->getLocalCode(),
      'isoCode' => $subdivision->getIsoCode(),
    ];
  }

  protected function doUndoTransform($value, Context $context) {
    $administrative_area = $value['administrative_area'];
    if (!empty($administrative_area)) {
      $administrative_area_subdivision = $this->subdivisionRepository->get($administrative_area, [$value['country_code']]);
      if (!empty($administrative_area_subdivision)) {
        $value['administrative_area'] = $this->normalizeSubdivision($administrative_area_subdivision);
        $locality = $value['locality'];
        $locality_subdivision = $this->subdivisionRepository->get($locality, [
          $value['country_code'],
          $administrative_area_subdivision->getCode(),
        ]);
        if (!empty($locality_subdivision)) {
          $value['locality'] = $this->normalizeSubdivision($locality_subdivision);
          $dependent_locality = $value['dependent_locality'] ?? NULL;
          $dependent_locality_subdivision = $this->subdivisionRepository->get($dependent_locality, [
            $value['country_code'],
            $administrative_area_subdivision->getCode(),
            $locality_subdivision->getCode()
          ]);
          if (!empty($dependent_locality_subdivision)) {
            $value['dependent_locality'] = $this->normalizeSubdivision($dependent_locality_subdivision);
          }
        }
      }
    }
    return $value;
  }

  protected function doTransform($value) {
    throw new \TypeError();
  }

  public function getJsonSchema() {
    return [
      'type' => 'object',
      'properties' => [
        'additional_name' => [
          'type' => 'string',
          'title' => 'The additional name.'
        ],
        'address_line1' => [
          'type' => 'string',
          'title' => 'The first line of the address block.'
        ],
        'address_line2' => [
          'type' => 'string',
          'title' => 'The second line of the address block.'
        ],
        'administrative_area' => [
          'type' => 'object',
          'properties' => [
            'name' => [
              'type' => 'string',
            ],
            'localName' => [
              'type' => 'string',
            ],
            'code' => [
              'type' => 'string',
            ],
            'localCode' => [
              'type' => 'string',
            ],
            'isoCode' => [
              'type' => 'string',
            ],
          ],
        ],
        'country_code' => [
          'type' => 'string',
          'title' => 'The two-letter country code.'
        ],
        'dependent_locality' => [
          'type' => 'string',
          'title' => 'The dependent locality (i.e. neighbourhood).'
        ],
        'family_name' => [
          'type' => 'string',
          'title' => 'The family name.'
        ],
        'given_name' => [
          'type' => 'string',
          'title' => 'The given name.'
        ],
        'langcode' => [
          'type' => 'string',
          'title' => 'The language code.'
        ],
        'locality' => [
          'type' => 'object',
          'properties' => [
            'name' => [
              'type' => 'string',
            ],
            'localName' => [
              'type' => 'string',
            ],
            'code' => [
              'type' => 'string',
            ],
            'localCode' => [
              'type' => 'string',
            ],
            'isoCode' => [
              'type' => 'string',
            ],
          ],
        ],
        'organization' => [
          'type' => 'string',
          'title' => 'The organization'
        ],
        'postal_code' => [
          'type' => 'string',
          'title' => 'The postal code.'
        ],
        'sorting_code' => [
          'type' => 'string',
          'title' => 'The sorting code.'
        ],
      ]
    ];
  }

  public function getSettingsForm(array $resource_field_info) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }
}
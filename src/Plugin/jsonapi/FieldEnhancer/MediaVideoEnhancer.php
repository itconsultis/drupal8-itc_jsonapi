<?php
/**
 * Created by PhpStorm.
 * User: bertrand
 * Date: 17/01/18
 * Time: 17:51
 */

namespace Drupal\itc_jsonapi\Plugin\jsonapi\FieldEnhancer;


use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerBase;
use Shaper\Util\Context;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Return embed and thumbnail url instead of user input
 *
 * @ResourceFieldEnhancer(
 *   id = "media_video",
 *   label = @Translation("Media video"),
 *   description = @Translation("Return embed and thumbnail url instead of user input")
 * )
 */
class MediaVideoEnhancer extends ResourceFieldEnhancerBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\video_embed_field\ProviderManager
   */
  protected $providerManager;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $provider_manager = NULL;
    if ($container->has('video_embed_field.provider_manager')) {
      $provider_manager = $container->get('video_embed_field.provider_manager');
    }

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $provider_manager
    );
  }

  public function __construct(array $configuration, string $plugin_id, $plugin_definition, $provider_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->providerManager = $provider_manager;
  }

  protected function doUndoTransform($value, Context $context) {
    $provider = $this->providerManager->loadProviderFromInput($value);
    $thumbnail = $provider->getRemoteThumbnailUrl();
    $embed_code = $provider->renderEmbedCode(0, 0, 0);
    $video_url = $this->getVideoUrl($embed_code);
    return [
      'thumbnail_url' => $thumbnail,
      'video_url' => $video_url,
    ];
  }

  protected function doTransform($value, Context $context) {
    throw new \TypeError();
  }

  public function getOutputJsonSchema() {
    return [
      'type' => 'object',
      'properties' => [
        'video_url' => 'string',
        'thumbnail_url' => 'string',
      ],
    ];
  }

  protected function getVideoUrl($embed_code) {
    switch ($embed_code['#type']) {
      case 'video_embed_iframe':
        return $embed_code['#url'];

      case 'html_tag':
        if ($embed_code['#tag'] === 'iframe') {
          return $embed_code['#attributes']['src'];
        }
        throw new \Exception('Unsupported embed code tag.');

      default:
        throw new \Exception('Unsupported embed code type.');
    }
  }
}
<?php
/**
 * @copyright Copyright (c) Work With Tom SRL
 */

namespace workwithtom\elasticemail;

use Craft;
use craft\behaviors\EnvAttributeParserBehavior;
use craft\mail\transportadapters\BaseTransportAdapter;
use workwithtom\elasticemail\services\ElasticEmailTransport;
use Swift_Events_SimpleEventDispatcher;
use ElasticEmailClient;
use ElasticEmail\Configuration;

/**
 * ElasticEmailAdapter implements a ElasticEmail transport adapter into Craft’s mailer.
 *
 * @property mixed $settingsHtml
 * @author Work With Tom SRL <tom@workwithtom.ro>
 * @since 1.0
 */
class ElasticEmailAdapter extends BaseTransportAdapter
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return 'Elastic Email';
    }

    /**
     * @var string The API key that should be used
     */
    public $apiKey;

    /**
     * @var string The API endpoint that should be used
     */
    public $endpoint;

    /**
     * @inheritdoc
     */
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['parser'] = [
            'class' => EnvAttributeParserBehavior::class,
            'attributes' => [
                'apiKey',
                'endpoint',
            ],
        ];
        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'apiKey' => Craft::t('elasticemail', 'API Key'),
            'endpoint' => Craft::t('elasticemail', 'Endpoint'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['apiKey', 'endpoint'], 'required'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('elasticemail/settings', [
            'adapter' => $this
        ]);
    }

    /**
     * @inheritdoc
     */
    public function defineTransport(): \Symfony\Component\Mailer\Transport\AbstractTransport|array
    {
        // Configure API key authorization: apikey
        $config = \ElasticEmail\Configuration::getDefaultConfiguration()->setApiKey('X-ElasticEmail-ApiKey', Craft::parseEnv($this->apiKey));

        $apiInstance = new \ElasticEmail\Api\EmailsApi(
            new \GuzzleHttp\Client(),
            $config
        );

        return [
            'class' => ElasticEmailTransport::class,
            'constructArgs' => [
                [
                    'class' => Swift_Events_SimpleEventDispatcher::class
                ],
                $apiInstance
            ],
        ];
    }
}

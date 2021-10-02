<?php
/**
 * @copyright Copyright (c) Work With Tom SRL
 */

namespace workwithtom\elasticemail;

use craft\events\RegisterComponentTypesEvent;
use craft\helpers\MailerHelper;
use yii\base\Event;

/**
 * Plugin represents the ElasticEmail plugin.
 *
 * @author Work With Tom SRL <tom@workwithtom.ro>
 * @since 1.0
 */
class Plugin extends \craft\base\Plugin
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        Event::on(MailerHelper::class, MailerHelper::EVENT_REGISTER_MAILER_TRANSPORT_TYPES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = ElasticEmailAdapter::class;
        });
    }
}

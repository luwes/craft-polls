<?php
/**
 * @link      https://wesleyluyten.com
 * @copyright Copyright (c) Wesley Luyten
 * @license   https://git.io/craft-polls-license
 */

namespace luwes\polls;

use Craft;
use craft\base\Plugin;
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use yii\base\Event;
use luwes\polls\models\Settings;

class Polls extends Plugin
{
    /**
     * @inheritdoc
     */
    public $hasCpSettings = true;

    /**
     * @inheritdoc
     */
    public $hasCpSection = true;

    /**
     * @var \luwes\polls\Polls The plugin instance.
     */
    public static $plugin;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $this->setComponents([
            'polls' => \luwes\polls\services\Polls::class,
        ]);

        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            //$event->rules['cocktails/new'] = ['template' => 'cocktails/_edit'];
            //$event->rules['cocktails/<widgetId:\d+>'] = 'cocktails/edit-cocktail';
            $rules = [
                'polls' => 'polls/polls/index',
                'polls/new' => 'polls/polls/edit-poll',
                'polls/<pollId:\d+>' => 'polls/polls/edit-poll',
            ];

            $event->rules = array_merge($event->rules, $rules);
        });
    }

    /**
     * @inheritdoc
     */
    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): string
    {
        // Get and pre-validate the settings
        $settings = $this->getSettings();
        $settings->validate();

        // Get the settings that are being defined by the config file
        $overrides = Craft::$app->getConfig()->getConfigFromFile(strtolower($this->handle));

        return Craft::$app->view->renderTemplate('polls/settings', [
            'settings' => $settings,
            'overrides' => array_keys($overrides),
        ]);
    }
}

  // public function registerCpRoutes()
  // {
  //   return array(

  //     'polls/(?P<pollHandle>{handle})/questions/(?P<questionId>\d+)/answers'
  //       => array('action' => 'polls/answers/answersIndex'),

  //     'polls/(?P<pollHandle>{handle})/questions/(?P<questionId>\d+)/options/(?P<optionId>\d+)/(?P<localeId>\w+)'
  //       => array('action' => 'polls/options/editOption'),

  //     'polls/(?P<pollHandle>{handle})/questions/(?P<questionId>\d+)/options/new/(?P<localeId>\w+)'
  //       => array('action' => 'polls/options/editOption'),

  //     'polls/(?P<pollHandle>{handle})/questions/(?P<questionId>\d+)/options/(?P<optionId>\d+)'
  //       => array('action' => 'polls/options/editOption'),

  //     'polls/(?P<pollHandle>{handle})/questions/(?P<questionId>\d+)/options/new'
  //       => array('action' => 'polls/options/editOption'),

  //     'polls/(?P<pollHandle>{handle})/questions/(?P<questionId>\d+)/options'
  //       => array('action' => 'polls/options/optionsIndex'),

  //     'polls/(?P<pollHandle>{handle})/questions/new/(?P<localeId>\w+)'
  //       => array('action' => 'polls/questions/editQuestion'),

  //     'polls/(?P<pollHandle>{handle})/questions/(?P<questionId>\d+)/(?P<localeId>\w+)'
  //       => array('action' => 'polls/questions/editQuestion'),

  //     'polls/(?P<pollHandle>{handle})/questions/new'
  //       => array('action' => 'polls/questions/editQuestion'),

  //     'polls/(?P<pollHandle>{handle})/questions/(?P<questionId>\d+)'
  //       => array('action' => 'polls/questions/editQuestion'),

  //     'polls/(?P<pollId>\d+)/questiontypes/(?P<questionTypeId>\d+)'
  //       => array('action' => 'polls/editQuestionType'),

  //     'polls/(?P<pollId>\d+)/optiontypes/(?P<optionTypeId>\d+)'
  //       => array('action' => 'polls/editOptionType'),

  //     'polls/questions'
  //       => array('action' => 'polls/questions/questionsIndex'),

  //     'polls/new'
  //       => array('action' => 'polls/editPoll'),

  //     'polls/(?P<pollHandle>{handle})'
  //       => array('action' => 'polls/editPoll'),

  //     'polls'
  //       => array('action' => 'polls/pollsIndex'),
  //   );
  // }

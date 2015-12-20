<?php
namespace Craft;

/**
 * Polls plugin class
 */
class PollsPlugin extends BasePlugin
{
	public function getName()
	{
	    return 'Polls';
	}

	public function getVersion()
	{
	    return '0.0.1';
	}

	public function getDeveloper()
	{
	    return 'luwes';
	}

	public function getDeveloperUrl()
	{
	    return 'http://luwes.co';
	}

	public function hasCpSection()
	{
		return true;
	}

	public function init()
	{
	}

	public function getSettingsHtml()
	{
		return craft()->templates->render('polls/_settings', array(
			'settings' => $this->getSettings()
		));
	}

	protected function defineSettings()
	{
		return array(
			'requireLogin' => array(AttributeType::Bool, 'default' => false),
		);
	}

	public function registerCpRoutes()
	{
		return array(

			'polls/(?P<pollHandle>{handle})/questions/(?P<questionId>\d+)/answers'	
				=> array('action' => 'polls/answers/answersIndex'),

			'polls/(?P<pollHandle>{handle})/questions/(?P<questionId>\d+)/options/(?P<optionId>\d+)/(?P<localeId>\w+)'	
				=> array('action' => 'polls/options/editOption'),

			'polls/(?P<pollHandle>{handle})/questions/(?P<questionId>\d+)/options/new/(?P<localeId>\w+)'	
				=> array('action' => 'polls/options/editOption'),

			'polls/(?P<pollHandle>{handle})/questions/(?P<questionId>\d+)/options/(?P<optionId>\d+)'	
				=> array('action' => 'polls/options/editOption'),

			'polls/(?P<pollHandle>{handle})/questions/(?P<questionId>\d+)/options/new'	
				=> array('action' => 'polls/options/editOption'),

			'polls/(?P<pollHandle>{handle})/questions/(?P<questionId>\d+)/options'	
				=> array('action' => 'polls/options/optionsIndex'),

			'polls/(?P<pollHandle>{handle})/questions/new/(?P<localeId>\w+)' 
				=> array('action' => 'polls/questions/editQuestion'),

			'polls/(?P<pollHandle>{handle})/questions/(?P<questionId>\d+)/(?P<localeId>\w+)'	
				=> array('action' => 'polls/questions/editQuestion'),

			'polls/(?P<pollHandle>{handle})/questions/new' 
				=> array('action' => 'polls/questions/editQuestion'),

			'polls/(?P<pollHandle>{handle})/questions/(?P<questionId>\d+)'
				=> array('action' => 'polls/questions/editQuestion'),

			'polls/(?P<pollId>\d+)/questiontypes/(?P<questionTypeId>\d+)'	
				=> array('action' => 'polls/editQuestionType'),

			'polls/(?P<pollId>\d+)/optiontypes/(?P<optionTypeId>\d+)'	
				=> array('action' => 'polls/editOptionType'),

			'polls/questions' 
				=> array('action' => 'polls/questions/questionsIndex'),

			'polls/new' 
				=> array('action' => 'polls/editPoll'),

			'polls/(?P<pollHandle>{handle})' 
				=> array('action' => 'polls/editPoll'),

			'polls' 
				=> array('action' => 'polls/pollsIndex'),
		);
	}
}

<?php
namespace Craft;

/**
 * Polls controller
 */
class PollsController extends BaseController
{
	/**
	 * Polls index
	 */
	public function actionPollsIndex()
	{
		$variables['polls'] = craft()->polls->getAllPolls();

		$this->renderTemplate('polls/_index', $variables);
	}

	/**
	 * Edit a poll.
	 *
	 * @param array $variables
	 * @throws HttpException
	 * @throws Exception
	 */
	public function actionEditPoll(array $variables = array())
	{
		$variables['brandNewPoll'] = false;

		if (!empty($variables['pollHandle']))
		{
			if (empty($variables['poll']))
			{
				$variables['poll'] = craft()->polls->getPollByHandle($variables['pollHandle']);

				if (!$variables['poll'])
				{
					throw new HttpException(404);
				}
			}

			$variables['title'] = $variables['poll']->name;
		}
		elseif (!empty($variables['pollId']))
		{
			if (empty($variables['poll']))
			{
				$variables['poll'] = craft()->polls->getPollById($variables['pollId']);

				if (!$variables['poll'])
				{
					throw new HttpException(404);
				}
			}

			$variables['title'] = $variables['poll']->name;
		}
		else
		{
			if (empty($variables['poll']))
			{
				$variables['poll'] = new Polls_PollModel();
				$variables['brandNewPoll'] = true;
			}

			$variables['title'] = Craft::t('Create a new poll');
		}

		$variables['crumbs'] = array(
			array('label' => Craft::t('Polls'), 'url' => UrlHelper::getUrl('polls')),
		);

		$this->renderTemplate('polls/_edit', $variables);
	}

	/**
	 * Saves a poll
	 */
	public function actionSavePoll()
	{
		$this->requirePostRequest();

		$poll = new Polls_PollModel();

		// Shared attributes
		$poll->id         = craft()->request->getPost('pollId');
		$poll->name       = craft()->request->getPost('name');
		$poll->handle     = craft()->request->getPost('handle');

		// Locale-specific attributes
		$locales = array();

		if (craft()->isLocalized())
		{
			$localeIds = craft()->request->getPost('locales', array());
		}
		else
		{
			$primaryLocaleId = craft()->i18n->getPrimarySiteLocaleId();
			$localeIds = array($primaryLocaleId);
		}

		foreach ($localeIds as $localeId)
		{

			$locales[$localeId] = new Polls_PollLocaleModel(array(
				'locale'           => $localeId,
				'enabledByDefault' => (bool) craft()->request->getPost('defaultLocaleStatuses.'.$localeId),
			));
		}

		$poll->setLocales($locales);

		// Save it
		if (craft()->polls->savePoll($poll))
		{
			craft()->userSession->setNotice(Craft::t('Poll saved.'));
			$this->redirectToPostedUrl($poll);
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t save poll.'));
		}

		// Send the poll back to the template
		craft()->urlManager->setRouteVariables(array(
			'poll' => $poll
		));
	}

	/**
	 * Deletes a poll.
	 */
	public function actionDeletePoll()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$pollId = craft()->request->getRequiredPost('id');

		craft()->polls->deletePollById($pollId);
		$this->returnJson(array('success' => true));
	}

	/**
	 * Edit an question type
	 *
	 * @param array $variables
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function actionEditQuestionType(array $variables = array())
	{
		if (empty($variables['pollId']))
		{
			throw new HttpException(400);
		}

		$variables['poll'] = craft()->polls->getPollById($variables['pollId']);

		if (!$variables['poll'])
		{
			throw new HttpException(404);
		}

		if (!empty($variables['questionTypeId']))
		{
			if (empty($variables['questionType']))
			{
				$variables['questionType'] = craft()->polls->getQuestionTypeById($variables['questionTypeId']);

				if (!$variables['questionType'] || $variables['questionType']->pollId != $variables['poll']->id)
				{
					throw new HttpException(404);
				}
			}

			$variables['title'] = 'Edit question type';
		}
		else
		{
			if (empty($variables['questionType']))
			{
				$variables['questionType'] = new Polls_QuestionTypeModel();
				$variables['questionType']->pollId = $variables['poll']->id;
			}

			$variables['title'] = Craft::t('Create a new {poll} question type', array('poll' => Craft::t($variables['poll']->name)));
		}

		$variables['crumbs'] = array(
			array('label' => Craft::t('Polls'), 'url' => UrlHelper::getUrl('polls')),
			array('label' => $variables['poll']->name, 'url' => UrlHelper::getUrl('polls/'.$variables['poll']->handle)),
			array('label' => Craft::t('Question Type'), 'url' => UrlHelper::getUrl('polls/'.$variables['pollId'].'/questiontypes/'.$variables['questionTypeId'])),
		);

		$this->renderTemplate('polls/_questiontypes/edit', $variables);
	}

	/**
	 * Saves an question type.
	 *
	 * @throws Exception
	 * @throws HttpException
	 * @throws \Exception
	 * @return null
	 */
	public function actionSaveQuestionType()
	{
		$this->requirePostRequest();

		$questionTypeId = craft()->request->getPost('questionTypeId');

		if ($questionTypeId)
		{
			$questionType = craft()->polls->getQuestionTypeById($questionTypeId);

			if (!$questionType)
			{
				throw new Exception(Craft::t('No question type exists with the ID “{id}”.', array('id' => $questionTypeId)));
			}
		}
		else
		{
			$questionType = new QuestionTypeModel();
		}

		// Set the simple stuff
		$questionType->pollId     = craft()->request->getRequiredPost('pollId', $questionType->pollId);
		// $questionType->name          = craft()->request->getPost('name', $questionType->name);
		// $questionType->handle        = craft()->request->getPost('handle', $questionType->handle);
		// $questionType->hasTitleField = (bool) craft()->request->getPost('hasTitleField', $questionType->hasTitleField);
		// $questionType->titleLabel    = craft()->request->getPost('titleLabel', $questionType->titleLabel);
		// $questionType->titleFormat   = craft()->request->getPost('titleFormat', $questionType->titleFormat);

		// Set the field layout
		$fieldLayout = craft()->fields->assembleLayoutFromPost();
		$fieldLayout->type = Polls_ElementType::Question;
		$questionType->setFieldLayout($fieldLayout);

		// Save it
		if (craft()->polls->saveQuestionType($questionType))
		{
			craft()->userSession->setNotice(Craft::t('Question type saved.'));
			$this->redirectToPostedUrl($questionType);
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t save question type.'));
		}

		// Send the question type back to the template
		craft()->urlManager->setRouteVariables(array(
			'questionType' => $questionType
		));
	}

	/**
	 * Edit an option type
	 *
	 * @param array $variables
	 *
	 * @throws HttpException
	 * @return null
	 */
	public function actionEditOptionType(array $variables = array())
	{
		if (empty($variables['pollId']))
		{
			throw new HttpException(400);
		}

		$variables['poll'] = craft()->polls->getPollById($variables['pollId']);

		if (!$variables['poll'])
		{
			throw new HttpException(404);
		}

		if (!empty($variables['optionTypeId']))
		{
			if (empty($variables['optionType']))
			{
				$variables['optionType'] = craft()->polls->getOptionTypeById($variables['optionTypeId']);

				if (!$variables['optionType'] || $variables['optionType']->pollId != $variables['poll']->id)
				{
					throw new HttpException(404);
				}
			}

			$variables['title'] = 'Edit option type';
		}
		else
		{
			if (empty($variables['optionType']))
			{
				$variables['optionType'] = new Polls_OptionTypeModel();
				$variables['optionType']->pollId = $variables['poll']->id;
			}

			$variables['title'] = Craft::t('Create a new {poll} option type', array('poll' => Craft::t($variables['poll']->name)));
		}

		$variables['crumbs'] = array(
			array('label' => Craft::t('Polls'), 'url' => UrlHelper::getUrl('polls')),
			array('label' => $variables['poll']->name, 'url' => UrlHelper::getUrl('polls/'.$variables['poll']->handle)),
			array('label' => Craft::t('Option Type'), 'url' => UrlHelper::getUrl('polls/'.$variables['pollId'].'/optiontypes/'.$variables['optionTypeId'])),
		);

		$this->renderTemplate('polls/_optiontypes/edit', $variables);
	}

	/**
	 * Saves an option type.
	 *
	 * @throws Exception
	 * @throws HttpException
	 * @throws \Exception
	 * @return null
	 */
	public function actionSaveOptionType()
	{
		$this->requirePostRequest();

		$optionTypeId = craft()->request->getPost('optionTypeId');

		if ($optionTypeId)
		{
			$optionType = craft()->polls->getOptionTypeById($optionTypeId);

			if (!$optionType)
			{
				throw new Exception(Craft::t('No option type exists with the ID “{id}”.', array('id' => $optionTypeId)));
			}
		}
		else
		{
			$optionType = new OptionTypeModel();
		}

		// Set the simple stuff
		$optionType->pollId     = craft()->request->getRequiredPost('pollId', $optionType->pollId);
		// $optionType->name          = craft()->request->getPost('name', $optionType->name);
		// $optionType->handle        = craft()->request->getPost('handle', $optionType->handle);
		// $optionType->hasTitleField = (bool) craft()->request->getPost('hasTitleField', $optionType->hasTitleField);
		// $optionType->titleLabel    = craft()->request->getPost('titleLabel', $optionType->titleLabel);
		// $optionType->titleFormat   = craft()->request->getPost('titleFormat', $optionType->titleFormat);

		// Set the field layout
		$fieldLayout = craft()->fields->assembleLayoutFromPost();
		$fieldLayout->type = Polls_ElementType::Option;
		$optionType->setFieldLayout($fieldLayout);

		// Save it
		if (craft()->polls->saveOptionType($optionType))
		{
			craft()->userSession->setNotice(Craft::t('Option type saved.'));
			$this->redirectToPostedUrl($optionType);
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t save option type.'));
		}

		// Send the option type back to the template
		craft()->urlManager->setRouteVariables(array(
			'optionType' => $optionType
		));
	}

}

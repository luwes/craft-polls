<?php
namespace Craft;

/**
 * Questions controller
 */
class Polls_QuestionsController extends BaseController
{
	/**
	 * Questions index
	 */
	public function actionQuestionsIndex()
	{
		$variables['polls'] = craft()->polls->getAllPolls();

		$this->renderTemplate('polls/questions/index', $variables);
	}

	/**
	 * Edit a question.
	 *
	 * @param array $variables
	 * @throws HttpException
	 */
	public function actionEditQuestion(array $variables = array())
	{
		if (!empty($variables['pollHandle']))
		{
			$variables['poll'] = craft()->polls->getPollByHandle($variables['pollHandle']);
		}
		else if (!empty($variables['pollId']))
		{
			$variables['poll'] = craft()->polls->getPollById($variables['pollId']);
		}

		$variables['questionType'] = craft()->polls->getQuestionTypesByPollId($variables['poll']->id)[0];

		if (empty($variables['poll']))
		{
			throw new HttpException(404);
		}

		// Get the locale
		// ---------------------------------------------------------------------

		if (craft()->isLocalized())
		{
			// Only use the locales that the user has access to
			$pollLocaleIds = array_keys($variables['poll']->getLocales());
			$editableLocaleIds = craft()->i18n->getEditableLocaleIds();
			$variables['localeIds'] = array_merge(array_intersect($pollLocaleIds, $editableLocaleIds));
		}
		else
		{
			$variables['localeIds'] = array(craft()->i18n->getPrimarySiteLocaleId());
		}

		if (!$variables['localeIds'])
		{
			throw new HttpException(403, Craft::t('Your account doesn’t have permission to edit any of this poll’s locales.'));
		}

		if (empty($variables['localeId']))
		{
			$variables['localeId'] = craft()->language;

			if (!in_array($variables['localeId'], $variables['localeIds']))
			{
				$variables['localeId'] = $variables['localeIds'][0];
			}
		}
		else
		{
			// Make sure they were requesting a valid locale
			if (!in_array($variables['localeId'], $variables['localeIds']))
			{
				throw new HttpException(404);
			}
		}

		// Now let's set up the actual question
		if (empty($variables['question']))
		{
			if (!empty($variables['questionId']))
			{
				$variables['question'] = craft()->polls_questions->getQuestionById($variables['questionId'], $variables['localeId']);

				if (!$variables['question'])
				{
					throw new HttpException(404);
				}
			}
			else
			{
				$variables['question'] = new Polls_QuestionModel();
				$variables['question']->pollId = $variables['poll']->id;

				if (!empty($variables['localeId']))
				{
					$variables['question']->locale = $variables['localeId'];
				}

				if (craft()->isLocalized())
				{
					// Set the default locale status based on the poll's settings
					foreach ($variables['poll']->getLocales() as $locale)
					{
						if ($locale->locale == $variables['question']->locale)
						{
							$variables['question']->localeEnabled = $locale->enabledByDefault;
							break;
						}
					}
				}
			}
		}

		if (!$variables['question']->id)
		{
			$variables['title'] = Craft::t('Create a new question');
		}
		else
		{
			$variables['title'] = $variables['question']->title;
		}

		// Enabled locales
		// ---------------------------------------------------------------------

		if (craft()->isLocalized())
		{
			if ($variables['question']->id)
			{
				$variables['enabledLocales'] = craft()->elements->getEnabledLocalesForElement($variables['question']->id);
			}
			else
			{
				$variables['enabledLocales'] = array();

				foreach ($variables['poll']->getLocales() as $locale)
				{
					if ($locale->enabledByDefault)
					{
						$variables['enabledLocales'][] = $locale->locale;
					}
				}
			}
		}

		// Define the content tabs
		// ---------------------------------------------------------------------

		$variables['tabs'] = array();

		foreach ($variables['questionType']->getFieldLayout()->getTabs() as $index => $tab)
		{
			// Do any of the fields on this tab have errors?
			$hasErrors = false;

			if ($variables['question']->hasErrors())
			{
				foreach ($tab->getFields() as $field)
				{
					if ($variables['question']->getErrors($field->getField()->handle))
					{
						$hasErrors = true;
						break;
					}
				}
			}

			$variables['tabs'][] = array(
				'label' => Craft::t($tab->name),
				'url'   => '#tab'.($index+1),
				'class' => ($hasErrors ? 'error' : null)
			);
		}

		// Breadcrumbs
		$variables['crumbs'] = array(
			array('label' => Craft::t('Polls'), 'url' => UrlHelper::getUrl('polls')),
			array('label' => $variables['poll']->name, 'url' => UrlHelper::getUrl('polls/'.$variables['poll']->handle)),
			array('label' => Craft::t('Questions'), 'url' => UrlHelper::getUrl('polls/questions')),
		);

		// Set the "Continue Editing" URL
		$variables['continueEditingUrl'] = 'polls/'.$variables['poll']->handle.'/questions/{id}';

		// Render the template!
		craft()->templates->includeCssResource('polls/css/cp.css');
		$this->renderTemplate('polls/questions/edit', $variables);
	}

	/**
	 * Saves a question.
	 */
	public function actionSaveQuestion()
	{
		$this->requirePostRequest();

		$questionId = craft()->request->getPost('questionId');
		$localeId = craft()->request->getPost('locale');

		if ($questionId)
		{
			$question = craft()->polls_questions->getQuestionById($questionId, $localeId);

			if (!$question)
			{
				throw new Exception(Craft::t('No question exists with the ID “{id}”', array('id' => $questionId)));
			}
		}
		else
		{
			$question = new Polls_QuestionModel();
		}

		// Set the question attributes, defaulting to the existing values for whatever is missing from the post data
		$question->pollId = craft()->request->getPost('pollId', $question->pollId);
		$question->typeId = craft()->request->getPost('typeId', $question->typeId);
		$question->multipleOptions = craft()->request->getPost('multipleOptions', $question->multipleOptions);
		$question->multipleVotes = craft()->request->getPost('multipleVotes', $question->multipleVotes);
		$question->answerRequired = craft()->request->getPost('answerRequired', $question->answerRequired);

		$question->getContent()->title = craft()->request->getPost('title', $question->title);
		$question->setContentFromPost('fields');

		if (craft()->polls_questions->saveQuestion($question))
		{
			craft()->userSession->setNotice(Craft::t('Question saved.'));
			$this->redirectToPostedUrl($question);
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t save question.'));

			// Send the question back to the template
			craft()->urlManager->setRouteVariables(array(
				'question' => $question
			));
		}
	}

	/**
	 * Deletes an question.
	 */
	public function actionDeleteQuestion()
	{
		$this->requirePostRequest();

		$questionId = craft()->request->getRequiredPost('questionId');
		$localeId = craft()->request->getPost('locale');
		$question = craft()->polls_questions->getQuestionById($questionId, $localeId);

		if (craft()->polls_questions->deleteQuestion($question))
		{
			if (craft()->request->isAjaxRequest())
			{
				$this->returnJson(array('success' => true));
			}
			else
			{
				craft()->userSession->setNotice(Craft::t('Question deleted.'));
				$this->redirectToPostedUrl($question);
			}
		}
		else
		{
			if (craft()->request->isAjaxRequest())
			{
				$this->returnJson(array('success' => false));
			}
			else
			{
				craft()->userSession->setError(Craft::t('Couldn’t delete question.'));

				// Send the question back to the template
				craft()->urlManager->setRouteVariables(array(
					'question' => $question
				));
			}
		}
	}
}

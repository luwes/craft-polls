<?php
namespace Craft;

/**
 * Options controller
 */
class Polls_OptionsController extends BaseController
{
	/**
	 * Options index
	 */
	public function actionOptionsIndex(array $variables = array())
	{
		if (!empty($variables['pollHandle']))
		{
			$variables['poll'] = craft()->polls->getPollByHandle($variables['pollHandle']);
		}
		else if (!empty($variables['pollId']))
		{
			$variables['poll'] = craft()->polls->getPollById($variables['pollId']);
		}

		if (!empty($variables['questionId']))
		{
			$variables['question'] = craft()->polls_questions->getQuestionById($variables['questionId']);

			if (!$variables['question'])
			{
				throw new HttpException(404);
			}
		}

		// Breadcrumbs
		$variables['crumbs'] = array(
			array('label' => Craft::t('Polls'), 'url' => UrlHelper::getUrl('polls')),
			array('label' => $variables['poll']->name, 'url' => UrlHelper::getUrl('polls/'.$variables['poll']->handle)),
			array('label' => Craft::t('Questions'), 'url' => UrlHelper::getUrl('polls/questions')),
			array('label' => $variables['question']->title, 'url' => UrlHelper::getUrl('polls/'.$variables['poll']->handle.'/questions/'.$variables['question']->id)),
		);

		$this->renderTemplate('polls/options/index', $variables);
	}

	/**
	 * Edit a option.
	 *
	 * @param array $variables
	 * @throws HttpException
	 */
	public function actionEditOption(array $variables = array())
	{
		if (!empty($variables['pollHandle']))
		{
			$variables['poll'] = craft()->polls->getPollByHandle($variables['pollHandle']);
		}
		else if (!empty($variables['pollId']))
		{
			$variables['poll'] = craft()->polls->getPollById($variables['pollId']);
		}

		if (!empty($variables['questionId']))
		{
			$variables['question'] = craft()->polls_questions->getQuestionById($variables['questionId']);

			if (!$variables['question'])
			{
				throw new HttpException(404);
			}
		}

		$variables['optionType'] = craft()->polls->getOptionTypesByPollId($variables['poll']->id)[0];

		if (empty($variables['poll']))
		{
			throw new HttpException(404);
		}

		// Option kinds

		$kinds = array(Polls_OptionKind::Defined, Polls_OptionKind::Other);
		$variables['kindOptions'] = array();

		foreach ($kinds as $kind)
		{
			$variables['kindOptions'][$kind] = Craft::t(ucfirst($kind));
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

		// Now let's set up the actual option
		if (empty($variables['option']))
		{
			if (!empty($variables['optionId']))
			{
				$variables['option'] = craft()->polls_options->getOptionById($variables['optionId'], $variables['localeId']);

				if (!$variables['option'])
				{
					throw new HttpException(404);
				}
			}
			else
			{
				$variables['option'] = new Polls_OptionModel();
				$variables['option']->questionId = $variables['question']->id;

				if (!empty($variables['localeId']))
				{
					$variables['option']->locale = $variables['localeId'];
				}

				if (craft()->isLocalized())
				{
					// Set the default locale status based on the poll's settings
					foreach ($variables['poll']->getLocales() as $locale)
					{
						if ($locale->locale == $variables['option']->locale)
						{
							$variables['option']->localeEnabled = $locale->enabledByDefault;
							break;
						}
					}
				}
			}
		}

		if (!$variables['option']->id)
		{
			$variables['title'] = Craft::t('Create a new option');
		}
		else
		{
			$variables['title'] = $variables['option']->title;
		}

		// Enabled locales
		// ---------------------------------------------------------------------

		if (craft()->isLocalized())
		{
			if ($variables['option']->id)
			{
				$variables['enabledLocales'] = craft()->elements->getEnabledLocalesForElement($variables['option']->id);
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

		foreach ($variables['optionType']->getFieldLayout()->getTabs() as $index => $tab)
		{
			// Do any of the fields on this tab have errors?
			$hasErrors = false;

			if ($variables['option']->hasErrors())
			{
				foreach ($tab->getFields() as $field)
				{
					if ($variables['option']->getErrors($field->getField()->handle))
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
			array('label' => $variables['question']->title, 'url' => UrlHelper::getUrl('polls/'.$variables['poll']->handle.'/questions/'.$variables['question']->id)),
			array('label' => Craft::t('Options'), 'url' => UrlHelper::getUrl('polls/'.$variables['poll']->handle.'/questions/'.$variables['question']->id.'/options')),
		);

		// Set the "Continue Editing" URL
		$variables['continueEditingUrl'] = 'polls/'.$variables['poll']->handle.'/questions/'.$variables['question']->id.'/options/{id}';

		// Render the template!
		craft()->templates->includeCssResource('polls/css/cp.css');
		$this->renderTemplate('polls/options/edit', $variables);
	}

	/**
	 * Saves a option.
	 */
	public function actionSaveOption()
	{
		$this->requirePostRequest();

		$optionId = craft()->request->getPost('optionId');
		$localeId = craft()->request->getPost('locale');

		if ($optionId)
		{
			$option = craft()->polls_options->getOptionById($optionId, $localeId);

			if (!$option)
			{
				throw new Exception(Craft::t('No option exists with the ID “{id}”', array('id' => $optionId)));
			}
		}
		else
		{
			$option = new Polls_OptionModel();
		}

		// Set the option attributes, defaulting to the existing values for whatever is missing from the post data
		$option->questionId = craft()->request->getPost('questionId', $option->questionId);
		$option->typeId = craft()->request->getPost('typeId', $option->typeId);
		$option->kind = craft()->request->getPost('kind', $option->kind);

		$option->getContent()->title = craft()->request->getPost('title', $option->title);
		$option->setContentFromPost('fields');

		if (craft()->polls_options->saveOption($option))
		{
			craft()->userSession->setNotice(Craft::t('Option saved.'));
			$this->redirectToPostedUrl($option);
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t save option.'));

			// Send the option back to the template
			craft()->urlManager->setRouteVariables(array(
				'option' => $option
			));
		}
	}

	/**
	 * Deletes an option.
	 */
	public function actionDeleteOption()
	{
		$this->requirePostRequest();

		$optionId = craft()->request->getRequiredPost('optionId');
		$localeId = craft()->request->getPost('locale');
		$option = craft()->polls_options->getOptionById($optionId, $localeId);

		if (craft()->polls_options->deleteOption($option))
		{
			if (craft()->request->isAjaxRequest())
			{
				$this->returnJson(array('success' => true));
			}
			else
			{
				craft()->userSession->setNotice(Craft::t('Option deleted.'));
				$this->redirectToPostedUrl($option);
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
				craft()->userSession->setError(Craft::t('Couldn’t delete option.'));

				// Send the option back to the template
				craft()->urlManager->setRouteVariables(array(
					'option' => $option
				));
			}
		}
	}

}

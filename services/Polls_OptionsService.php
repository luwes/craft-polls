<?php
namespace Craft;

/**
 * Polls options service
 */
class Polls_OptionsService extends BaseApplicationComponent
{
	/**
	 * Returns an option by its ID.
	 *
	 * @param int $optionId
	 * @return Polls_OptionModel|null
	 */
	public function getOptionById($optionId, $localeId = null)
	{
		return craft()->elements->getElementById($optionId, Polls_ElementType::Option, $localeId);
	}

	public function getOptionsByQuestionId($questionId)
	{
		$params = array('questionId' => $questionId);
		$criteria = craft()->elements->getCriteria(Polls_ElementType::Option, $params);
		return $criteria->find();
	}

	/**
	 * Saves an option.
	 *
	 * @param Polls_OptionModel $option
	 * @throws Exception
	 * @return bool
	 */
	public function saveOption(Polls_OptionModel $option)
	{
		$isNewOption = !$option->id;

		// Option data
		if (!$isNewOption)
		{
			$optionRecord = Polls_OptionRecord::model()->findById($option->id);

			if (!$optionRecord)
			{
				throw new Exception(Craft::t('No option exists with the ID “{id}”', array('id' => $option->id)));
			}
		}
		else
		{
			$optionRecord = new Polls_OptionRecord();
		}

		$optionRecord->questionId = $option->questionId;
		$optionRecord->typeId = $option->typeId;
		$optionRecord->kind = $option->kind;

		$optionRecord->validate();
		$option->addErrors($optionRecord->getErrors());

		if (!$option->hasErrors())
		{
			$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
			try
			{
				// Fire an 'onBeforeSavePollOption' option
				$this->onBeforeSavePollOption(new Event($this, array(
					'option'      => $option,
					'isNewOption' => $isNewOption
				)));

				if (craft()->elements->saveElement($option))
				{
					// Now that we have an element ID, save it on the other stuff
					if ($isNewOption)
					{
						$optionRecord->id = $option->id;
					}

					$optionRecord->save(false);

					// Fire an 'onSavePollOption' option
					$this->onSavePollOption(new Event($this, array(
						'option'      => $option,
						'isNewOption' => $isNewOption
					)));

					if ($transaction !== null)
					{
						$transaction->commit();
					}

					return true;
				}
			}
			catch (\Exception $e)
			{
				if ($transaction !== null)
				{
					$transaction->rollback();
				}

				throw $e;
			}
		}

		return false;
	}

	/**
	 * Deletes a option(s).
	 *
	 * @param OptionModel|OptionModel[] $options A option, or an array of options, to be deleted.
	 *
	 * @throws \Exception
	 * @return bool Whether the option deletion was successful.
	 */
	public function deleteOption($options)
	{
		if (!$options)
		{
			return false;
		}

		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

		try
		{
			if (!is_array($options))
			{
				$options = array($options);
			}

			$optionIds = array();

			foreach ($options as $option)
			{
				// Fire an 'onBeforeDeletePollOption' event
				$event = new Event($this, array(
					'option' => $option
				));

				$this->onBeforeDeletePollOption($event);

				if ($event->performAction)
				{
					$optionIds[] = $option->id;
				}
			}

			if ($optionIds)
			{
				// Delete 'em
				$success = craft()->elements->deleteElementById($optionIds);
			}
			else
			{
				$success = false;
			}

			if ($transaction !== null)
			{
				$transaction->commit();
			}
		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}

			throw $e;
		}

		if ($success)
		{
			foreach ($options as $option)
			{
				// Fire an 'onDeleteOption' event
				$this->onDeletePollOption(new Event($this, array(
					'option' => $option
				)));
			}

			return true;
		}
		else
		{
			return false;
		}
	}

	// Options

	/**
	 * Fires an 'onBeforeSavePollOption' option.
	 *
	 * @param Event $option
	 */
	public function onBeforeSavePollOption(Event $option)
	{
		$this->raiseEvent('onBeforeSavePollOption', $option);
	}

	/**
	 * Fires an 'onSavePollOption' option.
	 *
	 * @param Event $option
	 */
	public function onSavePollOption(Event $option)
	{
		$this->raiseEvent('onSavePollOption', $option);
	}

	/**
	 * Fires an 'onBeforeDeletePollOption' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onBeforeDeletePollOption(Event $event)
	{
		$this->raiseEvent('onBeforeDeletePollOption', $event);
	}

	/**
	 * Fires an 'onDeletePollOption' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onDeletePollOption(Event $event)
	{
		$this->raiseEvent('onDeletePollOption', $event);
	}
}

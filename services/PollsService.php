<?php
namespace Craft;

/**
 * Polls service
 */
class PollsService extends BaseApplicationComponent
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_allPollIds;

	/**
	 * @var
	 */
	private $_pollsById;

	/**
	 * @var
	 */
	private $_fetchedAllPolls = false;

	/**
	 * @var
	 */
	private $_questionTypesById;

	/**
	 * @var
	 */
	private $_optionTypesById;

	/**
	 * Returns all of the poll IDs.
	 *
	 * @return array
	 */
	public function getAllPollIds()
	{
		if (!isset($this->_allPollIds))
		{
			if ($this->_fetchedAllPolls)
			{
				$this->_allPollIds = array_keys($this->_pollsById);
			}
			else
			{
				$this->_allPollIds = craft()->db->createCommand()
					->select('id')
					->from('polls')
					->queryColumn();
			}
		}

		return $this->_allPollIds;
	}

	/**
	 * Returns all polls.
	 *
	 * @param string|null $indexBy
	 * @return array
	 */
	public function getAllPolls($indexBy = null)
	{
		if (!$this->_fetchedAllPolls)
		{
			$pollRecords = Polls_PollRecord::model()->ordered()->findAll();
			$this->_pollsById = Polls_PollModel::populateModels($pollRecords, 'id');
			$this->_fetchedAllPolls = true;
		}

		if ($indexBy == 'id')
		{
			return $this->_pollsById;
		}
		else if (!$indexBy)
		{
			return array_values($this->_pollsById);
		}
		else
		{
			$polls = array();

			foreach ($this->_pollsById as $poll)
			{
				$polls[$poll->$indexBy] = $poll;
			}

			return $polls;
		}
	}

	// /**
	//  * Gets the total number of polls.
	//  *
	//  * @return int
	//  */
	public function getTotalPolls()
	{
		return count($this->getAllPollIds());
	}

	/**
	 * Returns a poll by its ID.
	 *
	 * @param $pollId
	 * @return Polls_PollModel|null
	 */
	public function getPollById($pollId)
	{
		if (!isset($this->_pollsById) || !array_key_exists($pollId, $this->_pollsById))
		{
			$pollRecord = Polls_PollRecord::model()->findById($pollId);

			if ($pollRecord)
			{
				$this->_pollsById[$pollId] = Polls_PollModel::populateModel($pollRecord);
			}
			else
			{
				$this->_pollsById[$pollId] = null;
			}
		}

		return $this->_pollsById[$pollId];
	}

	/**
	 * Gets a poll by its handle.
	 *
	 * @param string $pollHandle
	 * @return Polls_PollModel|null
	 */
	public function getPollByHandle($pollHandle)
	{
		$pollRecord = Polls_PollRecord::model()->findByAttributes(array(
			'handle' => $pollHandle
		));

		if ($pollRecord)
		{
			return Polls_PollModel::populateModel($pollRecord);
		}
	}

	/**
	 * Returns a poll’s locales.
	 *
	 * @param int         $pollId
	 * @param string|null $indexBy
	 *
	 * @return Polls_PollLocaleModel[] The poll’s locales.
	 */
	public function getPollLocales($pollId, $indexBy = null)
	{
		$records = craft()->db->createCommand()
			->select('*')
			->from('polls_i18n polls_i18n')
			->join('locales locales', 'locales.locale = polls_i18n.locale')
			->where('polls_i18n.pollId = :pollId', array(':pollId' => $pollId))
			->order('locales.sortOrder')
			->queryAll();

		return Polls_PollLocaleModel::populateModels($records, $indexBy);
	}

	/**
	 * Saves a poll.
	 *
	 * @param Polls_PollModel $poll
	 * @throws \Exception
	 * @return bool
	 */
	public function savePoll(Polls_PollModel $poll)
	{
		if ($poll->id)
		{
			$pollRecord = Polls_PollRecord::model()->findById($poll->id);

			if (!$pollRecord)
			{
				throw new Exception(Craft::t('No poll exists with the ID “{id}”', array('id' => $poll->id)));
			}

			$oldPoll = Polls_PollModel::populateModel($pollRecord);
			$isNewPoll = false;
		}
		else
		{
			$pollRecord = new Polls_PollRecord();
			$isNewPoll = true;
		}

		$pollRecord->name       = $poll->name;
		$pollRecord->handle     = $poll->handle;

		$pollRecord->validate();
		$poll->addErrors($pollRecord->getErrors());

		$pollLocales = $poll->getLocales();

		if (!$pollLocales)
		{
			$poll->addError('localeErrors', Craft::t('At least one locale must be selected for the poll.'));
		}

		if (!$poll->hasErrors())
		{
			$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
			try
			{
				// Save it!
				$pollRecord->save(false);

				// Now that we have a poll ID, save it on the model
				if (!$poll->id)
				{
					$poll->id = $pollRecord->id;
				}

				// Might as well update our cache of the poll while we have it.
				$this->_pollsById[$poll->id] = $poll;

				// Update the polls_i18n table
				$newLocaleData = array();

				if (!$isNewPoll)
				{
					// Get the old poll locales
					$oldPollLocaleRecords = Polls_PollLocaleRecord::model()->findAllByAttributes(array(
						'pollId' => $poll->id
					));

					$oldPollLocales = Polls_PollLocaleModel::populateModels($oldPollLocaleRecords, 'locale');
				}

				foreach ($pollLocales as $localeId => $locale)
				{
					// Was this already selected?
					if (!$isNewPoll && isset($oldPollLocales[$localeId]))
					{
						$oldLocale = $oldPollLocales[$localeId];

						// Has anything changed?
						if ($locale->enabledByDefault != $oldLocale->enabledByDefault)
						{
							craft()->db->createCommand()->update('polls_i18n', array(
								'enabledByDefault' => (int)$locale->enabledByDefault,
							), array(
								'id' => $oldLocale->id
							));
						}
					}
					else
					{
						$newLocaleData[] = array($poll->id, $localeId, (int)$locale->enabledByDefault);
					}
				}

				// Insert the new locales
				craft()->db->createCommand()->insertAll('polls_i18n',
					array('pollId', 'locale', 'enabledByDefault'),
					$newLocaleData
				);

				if (!$isNewPoll)
				{
					// Drop any locales that are no longer being used, as well as the associated entry/element locale
					// rows

					$droppedLocaleIds = array_diff(array_keys($oldPollLocales), array_keys($pollLocales));

					if ($droppedLocaleIds)
					{
						craft()->db->createCommand()->delete('polls_i18n',
							array('and', 'pollId = :pollId', array('in', 'locale', $droppedLocaleIds)),
							array(':pollId' => $poll->id)
						);
					}
				}

				// Make sure there's at least one question type for this poll
				$questionTypeId = null;

				if (!$isNewPoll)
				{
					// Let's grab all of the question type IDs to save ourselves a query down the road if this is a Single
					$questionTypeIds = craft()->db->createCommand()
						->select('id')
						->from('polls_questiontypes')
						->where('pollId = :pollId', array(':pollId' => $poll->id))
						->queryColumn();

					if ($questionTypeIds)
					{
						$questionTypeId = array_shift($questionTypeIds);
					}
				}

				if (!$questionTypeId)
				{
					$questionType = new Polls_QuestionTypeModel();
					$questionType->pollId = $poll->id;
					$this->saveQuestionType($questionType);
					$questionTypeId = $questionType->id;
				}

				// Make sure there's at least one option type for this poll
				$optionTypeId = null;

				if (!$isNewPoll)
				{
					// Let's grab all of the option type IDs to save ourselves a query down the road if this is a Single
					$optionTypeIds = craft()->db->createCommand()
						->select('id')
						->from('polls_optiontypes')
						->where('pollId = :pollId', array(':pollId' => $poll->id))
						->queryColumn();

					if ($optionTypeIds)
					{
						$optionTypeId = array_shift($optionTypeIds);
					}
				}

				if (!$optionTypeId)
				{
					$optionType = new Polls_OptionTypeModel();
					$optionType->pollId = $poll->id;
					$this->saveOptionType($optionType);
					$optionTypeId = $optionType->id;
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

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Saves an question type.
	 *
	 * @param Polls_QuestionTypeModel $questionType
	 *
	 * @throws Exception
	 * @throws \CDbException
	 * @throws \Exception
	 * @return bool
	 */
	public function saveQuestionType(Polls_QuestionTypeModel $questionType)
	{
		if ($questionType->id)
		{
			$questionTypeRecord = Polls_QuestionTypeRecord::model()->findById($questionType->id);

			if (!$questionTypeRecord)
			{
				throw new Exception(Craft::t('No question type exists with the ID “{id}”.', array('id' => $questionType->id)));
			}

			$isNewQuestionType = false;
			$oldQuestionType = Polls_QuestionTypeModel::populateModel($questionTypeRecord);
		}
		else
		{
			$questionTypeRecord = new Polls_QuestionTypeRecord();
			$isNewQuestionType = true;
		}

		$questionTypeRecord->pollId     = $questionType->pollId;

		$questionTypeRecord->validate();
		$questionType->addErrors($questionTypeRecord->getErrors());

		if (!$questionType->hasErrors())
		{
			$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

			try
			{
				// Fire an 'onBeforeSaveQuestionType' event
				$event = new Event($this, array(
					'questionType'      => $questionType,
					'isNewQuestionType' => $isNewQuestionType
				));

				$this->onBeforeSaveQuestionType($event);

				// Is the event giving us the go-ahead?
				if ($event->performAction)
				{
					if (!$isNewQuestionType && $oldQuestionType->fieldLayoutId)
					{
						// Drop the old field layout
						craft()->fields->deleteLayoutById($oldQuestionType->fieldLayoutId);
					}

					// Save the new one
					$fieldLayout = $questionType->getFieldLayout();
					craft()->fields->saveLayout($fieldLayout);

					// Update the question type record/model with the new layout ID
					$questionType->fieldLayoutId = $fieldLayout->id;
					$questionTypeRecord->fieldLayoutId = $fieldLayout->id;

					$questionTypeRecord->save(false);

					// Now that we have an question type ID, save it on the model
					if (!$questionType->id)
					{
						$questionType->id = $questionTypeRecord->id;
					}

					// Might as well update our cache of the question type while we have it.
					$this->_questionTypesById[$questionType->id] = $questionType;

					$success = true;
				}
				else
				{
					$success = false;
				}

				// Commit the transaction regardless of whether we saved the user, in case something changed
				// in onBeforeSaveQuestionType
				if ($transaction !== null)
				{
					$transaction->commit();
				}
			} catch (\Exception $e)
			{
				if ($transaction !== null)
				{
					$transaction->rollback();
				}

				throw $e;
			}
		}
		else
		{
			$success = false;
		}

		if ($success)
		{
			// Fire an 'onSaveQuestionType' event
			$this->onSaveQuestionType(new Event($this, array(
				'questionType'      => $questionType,
				'isNewQuestionType' => $isNewQuestionType
			)));
		}

		return $success;
	}

	/**
	 * Saves an option type.
	 *
	 * @param Polls_OptionTypeModel $optionType
	 *
	 * @throws Exception
	 * @throws \CDbException
	 * @throws \Exception
	 * @return bool
	 */
	public function saveOptionType(Polls_OptionTypeModel $optionType)
	{
		if ($optionType->id)
		{
			$optionTypeRecord = Polls_OptionTypeRecord::model()->findById($optionType->id);

			if (!$optionTypeRecord)
			{
				throw new Exception(Craft::t('No option type exists with the ID “{id}”.', array('id' => $optionType->id)));
			}

			$isNewOptionType = false;
			$oldOptionType = Polls_OptionTypeModel::populateModel($optionTypeRecord);
		}
		else
		{
			$optionTypeRecord = new Polls_OptionTypeRecord();
			$isNewOptionType = true;
		}

		$optionTypeRecord->pollId     = $optionType->pollId;

		$optionTypeRecord->validate();
		$optionType->addErrors($optionTypeRecord->getErrors());

		if (!$optionType->hasErrors())
		{
			$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

			try
			{
				// Fire an 'onBeforeSaveOptionType' event
				$event = new Event($this, array(
					'optionType'      => $optionType,
					'isNewOptionType' => $isNewOptionType
				));

				$this->onBeforeSaveOptionType($event);

				// Is the event giving us the go-ahead?
				if ($event->performAction)
				{
					if (!$isNewOptionType && $oldOptionType->fieldLayoutId)
					{
						// Drop the old field layout
						craft()->fields->deleteLayoutById($oldOptionType->fieldLayoutId);
					}

					// Save the new one
					$fieldLayout = $optionType->getFieldLayout();
					craft()->fields->saveLayout($fieldLayout);

					// Update the option type record/model with the new layout ID
					$optionType->fieldLayoutId = $fieldLayout->id;
					$optionTypeRecord->fieldLayoutId = $fieldLayout->id;

					$optionTypeRecord->save(false);

					// Now that we have an option type ID, save it on the model
					if (!$optionType->id)
					{
						$optionType->id = $optionTypeRecord->id;
					}

					// Might as well update our cache of the option type while we have it.
					$this->_optionTypesById[$optionType->id] = $optionType;

					$success = true;
				}
				else
				{
					$success = false;
				}

				// Commit the transaction regardless of whether we saved the user, in case something changed
				// in onBeforeSaveOptionType
				if ($transaction !== null)
				{
					$transaction->commit();
				}
			} catch (\Exception $e)
			{
				if ($transaction !== null)
				{
					$transaction->rollback();
				}

				throw $e;
			}
		}
		else
		{
			$success = false;
		}

		if ($success)
		{
			// Fire an 'onSaveOptionType' event
			$this->onSaveOptionType(new Event($this, array(
				'optionType'      => $optionType,
				'isNewOptionType' => $isNewOptionType
			)));
		}

		return $success;
	}

	/**
	 * Deletes a poll by its ID.
	 *
	 * @param int $pollId
	 * @throws \Exception
	 * @return bool
	 */
	public function deletePollById($pollId)
	{
		if (!$pollId)
		{
			return false;
		}

		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
		try
		{
			// Delete the field layout
			// $fieldLayoutId = craft()->db->createCommand()
			// 	->select('fieldLayoutId')
			// 	->from('polls')
			// 	->where(array('id' => $pollId))
			// 	->queryScalar();

			// if ($fieldLayoutId)
			// {
			// 	craft()->fields->deleteLayoutById($fieldLayoutId);
			// }

			// Grab the question ids so we can clean the elements table.
			$questionIds = craft()->db->createCommand()
				->select('id')
				->from('polls_questions')
				->where(array('pollId' => $pollId))
				->queryColumn();

			craft()->elements->deleteElementById($questionIds);

			$affectedRows = craft()->db->createCommand()->delete('polls', array('id' => $pollId));

			if ($transaction !== null)
			{
				$transaction->commit();
			}

			return (bool) $affectedRows;
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

	// Question Types
	// -------------------------------------------------------------------------

	/**
	 * Returns a poll’s question types.
	 *
	 * @param int         $pollId
	 * @param string|null $indexBy
	 *
	 * @return array
	 */
	public function getQuestionTypesByPollId($pollId, $indexBy = null)
	{
		$records = Polls_QuestionTypeRecord::model()->ordered()->findAllByAttributes(array(
			'pollId' => $pollId
		));

		return Polls_QuestionTypeModel::populateModels($records, $indexBy);
	}

	/**
	 * Returns an question type by its ID.
	 *
	 * @param int $questionTypeId
	 *
	 * @return QuestionTypeModel|null
	 */
	public function getQuestionTypeById($questionTypeId)
	{
		if (!isset($this->_questionTypesById) || !array_key_exists($questionTypeId, $this->_questionTypesById))
		{
			$questionTypeRecord = Polls_QuestionTypeRecord::model()->findById($questionTypeId);

			if ($questionTypeRecord)
			{
				$this->_questionTypesById[$questionTypeId] = Polls_QuestionTypeModel::populateModel($questionTypeRecord);
			}
			else
			{
				$this->_questionTypesById[$questionTypeId] = null;
			}
		}

		return $this->_questionTypesById[$questionTypeId];
	}

	// Option Types
	// -------------------------------------------------------------------------

	/**
	 * Returns a poll’s option types.
	 *
	 * @param int         $pollId
	 * @param string|null $indexBy
	 *
	 * @return array
	 */
	public function getOptionTypesByPollId($pollId, $indexBy = null)
	{
		$records = Polls_OptionTypeRecord::model()->ordered()->findAllByAttributes(array(
			'pollId' => $pollId
		));

		return Polls_OptionTypeModel::populateModels($records, $indexBy);
	}

	/**
	 * Returns an option type by its ID.
	 *
	 * @param int $optionTypeId
	 *
	 * @return OptionTypeModel|null
	 */
	public function getOptionTypeById($optionTypeId)
	{
		if (!isset($this->_optionTypesById) || !array_key_exists($optionTypeId, $this->_optionTypesById))
		{
			$optionTypeRecord = Polls_OptionTypeRecord::model()->findById($optionTypeId);

			if ($optionTypeRecord)
			{
				$this->_optionTypesById[$optionTypeId] = Polls_OptionTypeModel::populateModel($optionTypeRecord);
			}
			else
			{
				$this->_optionTypesById[$optionTypeId] = null;
			}
		}

		return $this->_optionTypesById[$optionTypeId];
	}

	// Events
	// -------------------------------------------------------------------------

	/**
	 * Fires an 'onBeforeSaveQuestionType' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onBeforeSaveQuestionType(Event $event)
	{
		$this->raiseEvent('onBeforeSaveQuestionType', $event);
	}

	/**
	 * Fires an 'onSaveQuestionType' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onSaveQuestionType(Event $event)
	{
		$this->raiseEvent('onSaveQuestionType', $event);
	}

	/**
	 * Fires an 'onBeforeSaveOptionType' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onBeforeSaveOptionType(Event $event)
	{
		$this->raiseEvent('onBeforeSaveOptionType', $event);
	}

	/**
	 * Fires an 'onSaveOptionType' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onSaveOptionType(Event $event)
	{
		$this->raiseEvent('onSaveOptionType', $event);
	}

}

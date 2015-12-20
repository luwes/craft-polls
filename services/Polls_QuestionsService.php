<?php
namespace Craft;

/**
 * Polls questions service
 */
class Polls_QuestionsService extends BaseApplicationComponent
{

  public function getCriteria(array $attributes = array())
  {
  	return craft()->elements->getCriteria(Polls_ElementType::Question, $attributes);
  }

	/**
	 * Returns an question by its ID.
	 *
	 * @param int $questionId
	 * @return Polls_QuestionModel|null
	 */
	public function getQuestionById($questionId, $localeId = null)
	{
		return craft()->elements->getElementById($questionId, Polls_ElementType::Question, $localeId);
	}

	/**
	 * Saves an question.
	 *
	 * @param Polls_QuestionModel $question
	 * @throws Exception
	 * @return bool
	 */
	public function saveQuestion(Polls_QuestionModel $question)
	{
		$isNewQuestion = !$question->id;

		// Question data
		if (!$isNewQuestion)
		{
			$questionRecord = Polls_QuestionRecord::model()->findById($question->id);

			if (!$questionRecord)
			{
				throw new Exception(Craft::t('No question exists with the ID “{id}”', array('id' => $question->id)));
			}
		}
		else
		{
			$questionRecord = new Polls_QuestionRecord();
		}

		$questionRecord->pollId = $question->pollId;
		$questionRecord->typeId = $question->typeId;
		$questionRecord->multipleOptions = $question->multipleOptions;
		$questionRecord->multipleVotes = $question->multipleVotes;
		$questionRecord->answerRequired = $question->answerRequired;

		$questionRecord->validate();
		$question->addErrors($questionRecord->getErrors());

		if (!$question->hasErrors())
		{
			$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
			try
			{
				// Fire an 'onBeforeSavePollQuestion' question
				$this->onBeforeSavePollQuestion(new Event($this, array(
					'question'      => $question,
					'isNewQuestion' => $isNewQuestion
				)));

				if (craft()->elements->saveElement($question))
				{
					// Now that we have an element ID, save it on the other stuff
					if ($isNewQuestion)
					{
						$questionRecord->id = $question->id;
					}

					$questionRecord->save(false);

					// Fire an 'onSavePollQuestion' question
					$this->onSavePollQuestion(new Event($this, array(
						'question'      => $question,
						'isNewQuestion' => $isNewQuestion
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
	 * Deletes a question(s).
	 *
	 * @param QuestionModel|QuestionModel[] $questions An question, or an array of questions, to be deleted.
	 *
	 * @throws \Exception
	 * @return bool Whether the question deletion was successful.
	 */
	public function deleteQuestion($questions)
	{
		if (!$questions)
		{
			return false;
		}

		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

		try
		{
			if (!is_array($questions))
			{
				$questions = array($questions);
			}

			$questionIds = array();

			foreach ($questions as $question)
			{
				// Fire an 'onBeforeDeletePollQuestion' event
				$event = new Event($this, array(
					'question' => $question
				));

				$this->onBeforeDeletePollQuestion($event);

				if ($event->performAction)
				{
					$questionIds[] = $question->id;
				}
			}

			if ($questionIds)
			{
				// Delete 'em
				$success = craft()->elements->deleteElementById($questionIds);
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
			foreach ($questions as $question)
			{
				// Fire an 'onDeleteQuestion' event
				$this->onDeletePollQuestion(new Event($this, array(
					'question' => $question
				)));
			}

			return true;
		}
		else
		{
			return false;
		}
	}

	// Questions

	/**
	 * Fires an 'onBeforeSavePollQuestion' question.
	 *
	 * @param Event $question
	 */
	public function onBeforeSavePollQuestion(Event $question)
	{
		$this->raiseEvent('onBeforeSavePollQuestion', $question);
	}

	/**
	 * Fires an 'onSavePollQuestion' question.
	 *
	 * @param Event $question
	 */
	public function onSavePollQuestion(Event $question)
	{
		$this->raiseEvent('onSavePollQuestion', $question);
	}

	/**
	 * Fires an 'onBeforeDeletePollQuestion' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onBeforeDeletePollQuestion(Event $event)
	{
		$this->raiseEvent('onBeforeDeletePollQuestion', $event);
	}

	/**
	 * Fires an 'onDeletePollQuestion' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onDeletePollQuestion(Event $event)
	{
		$this->raiseEvent('onDeletePollQuestion', $event);
	}
}

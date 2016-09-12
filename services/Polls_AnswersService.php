<?php
namespace Craft;

/**
 * Polls answers service
 */
class Polls_AnswersService extends BaseApplicationComponent
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_answersById;

	public $userCookie = 'PollsAnswers';
	public $userCookieLifespan = 315360000;


	// Public Methods
	// =========================================================================

	public function getAnswersByOptionId($optionId)
	{
		$answers = Polls_AnswerRecord::model()->findAllByAttributes(array(
			'optionId' => $optionId
		));

		if ($answers)
		{
			return Polls_AnswerModel::populateModels($answers);
		}
	}

	public function getAnswersByQuestionId($questionId)
	{
		$answers = Polls_AnswerRecord::model()->findAllByAttributes(array(
			'questionId' => $questionId
		));

		if ($answers)
		{
			return Polls_AnswerModel::populateModels($answers);
		}
	}

	public function getAnswersCookie()
	{
		$cookie = craft()->userSession->getStateCookieValue($this->userCookie);
		return $cookie ?: array();
	}

	public function setAnswersCookie($cookie=array())
	{
		craft()->userSession->saveCookie($this->userCookie, $cookie, $this->userCookieLifespan);
	}

	public function getAnswersByUserQuestion($user, $question)
	{
		$answers = Polls_AnswerRecord::model()->findAllByAttributes(array(
			'userId' => $user->id,
			'questionId' => $question->id
		));

		if ($answers)
		{
			return Polls_AnswerModel::populateModels($answers);
		}
	}

	public function hasAnswered($user, $question)
	{
		$alreadyAnswered = false;
		
		if ($user)
		{
			$oldAnswers = craft()->polls_answers->getAnswersByUserQuestion($user, $question);
			$alreadyAnswered = count($oldAnswers) > 0;
		}
		
		//if the user didn't answer yet, maybe he wasn't logged in at the time
		if (!$alreadyAnswered)
		{
			$history =& craft()->polls_answers->getAnswersCookie();
			$alreadyAnswered = isset($history[$question->id]) && count($history[$question->id]) > 0;
		}

		return $alreadyAnswered;
	}

	/**
	 * Saves an answer.
	 *
	 * @param Polls_Answer $answer
	 *
	 * @throws \Exception
	 * @return bool
	 */
	public function saveAnswer($answer)
	{
		if ($answer->id)
		{
			$answerRecord = Polls_AnswerRecord::model()->findById($answer->id);

			if (!$answerRecord)
			{
				throw new Exception(Craft::t('No answer exists with the ID “{id}”.', array('id' => $answer->id)));
			}

			$oldanswer = Polls_AnswerModel::populateModel($answerRecord);
			$isNewanswer = false;
		}
		else
		{
			$answerRecord = new Polls_AnswerRecord();
			$isNewanswer = true;
		}

		$answerRecord->questionId = $answer->questionId;
		$answerRecord->optionId   = $answer->optionId;
		$answerRecord->userId     = $answer->userId;
		$answerRecord->ipAddress 	= $answer->ipAddress;
		$answerRecord->userAgent 	= $answer->userAgent;
		$answerRecord->text 			= $answer->text;

		$answerRecord->validate();
		$answer->addErrors($answerRecord->getErrors());

		if (!$answer->hasErrors())
		{
			$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
			try
			{
				// Save it!
				$answerRecord->save(false);

				// Now that we have a answer ID, save it on the model
				if (!$answer->id)
				{
					$answer->id = $answerRecord->id;
				}

				// Might as well update our cache of the answers while we have it.
				$this->_answersById[$answer->id] = $answer;

				$history =& $this->getAnswersCookie();
				$history[$answer->questionId][] = $answer->id;
				$this->setAnswersCookie($history);

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
	 * Deletes an answer by its ID.
	 *
	 * @param int $answerId
	 * @throws \Exception
	 * @return bool
	 */
	public function deleteAnswerById($answerId)
	{
		if (!$answerId)
		{
			return false;
		}

		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
		try
		{
			$affectedRows = craft()->db->createCommand()->delete('polls_answers', array('id' => $answerId));

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
}
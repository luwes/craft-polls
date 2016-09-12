<?php
namespace Craft;

class PollsVariable
{
	// Public Methods
	// =========================================================================

	/**
	 * Returns all polls.
	 *
	 * @param string|null $indexBy
	 *
	 * @return array
	 */
	public function getAllPolls($indexBy = null)
	{
		return craft()->polls->getAllPolls($indexBy);
	}

	/**
	 * Gets the total number of polls.
	 *
	 * @return int
	 */
	public function getTotalPolls()
	{
		return craft()->polls->getTotalPolls();
	}

	/**
	 * Returns a poll by its ID.
	 *
	 * @param int $pollId
	 *
	 * @return PollModel|null
	 */
	public function getPollById($pollId)
	{
		return craft()->polls->getPollById($pollId);
	}

	/**
	 * Returns a poll by its handle.
	 *
	 * @param string $handle
	 *
	 * @return PollModel|null
	 */
	public function getPollByHandle($handle)
	{
		return craft()->polls->getPollByHandle($handle);
	}

	/**
	 * Returns a questions criteria model
	 *
	 * @param array|null $criteria
	 *
	 * @return ElementCriteriaModel
	 */

	public function questions($criteria = null)
	{
		return craft()->elements->getCriteria(Polls_ElementType::Question, $criteria);
	}

	/**
	 * Returns if user/guest has answered this question already
	 *
	 * @param array $questions
	 *
	 * @return bool
	 */

	public function hasAnswered($questions)
	{
		$user = craft()->userSession->getUser();
		foreach ($questions as $question) 
		{
			if (!craft()->polls_answers->hasAnswered($user, $question))
			{
				return false;
			}
		}
		return true;
	}

	/**
	 * Returns twig markup
	 *
	 * @param array|null $variables
	 *
	 * @return Twig_Markup
	 */

	public function form($variables=null)
	{
		$templateFile = 'polls/forms/basic';

		$oldPath = craft()->path->getTemplatesPath();
		$newPath = craft()->path->getPluginsPath() . 'polls/templates';
		craft()->path->setTemplatesPath($newPath);
		$html = craft()->templates->render('forms/basic', $variables);
		craft()->path->setTemplatesPath($oldPath);

		return new \Twig_Markup($html, craft()->templates->getTwig()->getCharset());
	}
}

















<?php
namespace Craft;

/**
 * Polls - Question model
 */
class Polls_QuestionModel extends BaseElementModel
{
	// Properties
	// =========================================================================

	protected $elementType = Polls_ElementType::Question;

	// Public Methods
	// =========================================================================

	public function getOptions()
	{
		$criteria = array('questionId' => $this->id);
		return craft()->elements->getCriteria(Polls_ElementType::Option, $criteria);
	}

	/**
	 * @inheritDoc BaseElementModel::getFieldLayout()
	 *
	 * @return FieldLayoutModel|null
	 */
	public function getFieldLayout()
	{
		$questionType = $this->getType();

		if ($questionType)
		{
			return $questionType->getFieldLayout();
		}
	}

	/**
	 * @inheritDoc BaseElementModel::getLocales()
	 *
	 * @return array
	 */
	public function getLocales()
	{
		$locales = array();

		foreach ($this->getPoll()->getLocales() as $locale)
		{
			$locales[$locale->locale] = array('enabledByDefault' => $locale->enabledByDefault);
		}

		return $locales;
	}

	/**
	 * Returns whether the current user can edit the element.
	 *
	 * @return bool
	 */
	public function isEditable()
	{
		return true;
	}

	/**
	 * Returns the element's CP edit URL.
	 *
	 * @return string|false
	 */
	public function getCpEditUrl()
	{
		$poll = $this->getPoll();

		if ($poll)
		{
			$url = UrlHelper::getCpUrl('polls/'.$poll->handle.'/questions/'.$this->id);

			if (craft()->isLocalized() && $this->locale != craft()->language)
			{
				$url .= '/'.$this->locale;
			}

			return $url;
		}
	}

	/**
	 * Returns the question's poll.
	 *
	 * @return Polls_PollModel|null
	 */
	public function getPoll()
	{
		if ($this->pollId)
		{
			return craft()->polls->getPollById($this->pollId);
		}
	}

	/**
	 * Returns the type of question.
	 *
	 * @return QuestionTypeModel|null
	 */
	public function getType()
	{
		$poll = $this->getPoll();

		if ($poll)
		{
			$pollQuestionTypes = $poll->getQuestionTypes('id');

			if ($pollQuestionTypes)
			{
				if ($this->typeId && isset($pollQuestionTypes[$this->typeId]))
				{
					return $pollQuestionTypes[$this->typeId];
				}
				else
				{
					// Just return the first one
					return $pollQuestionTypes[array_shift(array_keys($pollQuestionTypes))];
				}
			}
		}
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseModel::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array_merge(parent::defineAttributes(), array(
			'pollId' 						=> AttributeType::Number,
			'typeId'     				=> AttributeType::Number,
			'multipleOptions'		=> array(AttributeType::Bool, 'default' => false),
			'multipleVotes'			=> array(AttributeType::Bool, 'default' => false),
			'answerRequired'		=> array(AttributeType::Bool, 'default' => true),
		));
	}
}

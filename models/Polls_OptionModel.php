<?php
namespace Craft;

/**
 * Polls - Option model
 */
class Polls_OptionModel extends BaseElementModel
{
	private $_answers;

	// Properties
	// =========================================================================

	protected $elementType = Polls_ElementType::Option;


	// Public Methods
	// =========================================================================

	public function getLabel()
	{
		return $this->title;
	}

	public function getOptionInputName()
	{
		$question = $this->getQuestion();
		$inputName = 'polls_options';
		return sprintf('%s[%d][]', $inputName, $question->id);
	}

	public function getTextInputName()
	{
		if ($this->kind == Polls_OptionKind::Other)
		{
			$question = $this->getQuestion();
			$inputName = 'polls_texts';
		}
		return sprintf('%s[%d][%d]', $inputName, $question->id, $this->id);
	}

	public function getAnswers()
	{
		if (!isset($this->_answers))
		{
			$this->_answers = craft()->polls_answers->getAnswersByOptionId($this->id);
		}
		return $this->_answers;
	}

	public function getTotalAnswers()
	{
		return count($this->getAnswers());
	}

	/**
	 * @inheritDoc BaseElementModel::getFieldLayout()
	 *
	 * @return FieldLayoutModel|null
	 */
	public function getFieldLayout()
	{
		$optionType = $this->getType();

		if ($optionType)
		{
			return $optionType->getFieldLayout();
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
			$url = UrlHelper::getCpUrl('polls/'.$poll->handle.'/questions/'.$this->questionId.'/options/'.$this->id);

			if (craft()->isLocalized() && $this->locale != craft()->language)
			{
				$url .= '/'.$this->locale;
			}

			return $url;
		}
	}

	/**
	 * Returns the option's poll.
	 *
	 * @return Polls_PollModel|null
	 */
	public function getPoll()
	{
		$question = $this->getQuestion();

		if ($question)
		{
			return $question->getPoll();
		}
	}

	/**
	 * Returns the option's questions.
	 *
	 * @return Array|null
	 */
	public function getQuestion()
	{
		return craft()->polls_questions->getQuestionById($this->questionId);
	}

	/**
	 * Returns the type of option.
	 *
	 * @return OptionTypeModel|null
	 */
	public function getType()
	{
		$poll = $this->getPoll();

		if ($poll)
		{
			$pollOptionTypes = $poll->getOptionTypes('id');

			if ($pollOptionTypes)
			{
				if ($this->typeId && isset($pollOptionTypes[$this->typeId]))
				{
					return $pollOptionTypes[$this->typeId];
				}
				else
				{
					// Just return the first one
					return $pollOptionTypes[array_shift(array_keys($pollOptionTypes))];
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
			'questionId' 		=> AttributeType::Number,
			'typeId'				=> AttributeType::Number,
			'kind'					=> array(AttributeType::Enum, 'values' => array(Polls_OptionKind::Defined, Polls_OptionKind::Other), 'default' => Polls_OptionKind::Defined, 'required' => true),
			'sortOrder' 		=> AttributeType::SortOrder,
			'questionId'		=> AttributeType::Number,

			//just used for submitting the form
			'selected'			=> array(AttributeType::Bool, 'default' => false),
			'value'					=> array(AttributeType::String, 'default' => ''),
		));
	}
}

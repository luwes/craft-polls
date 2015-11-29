<?php
namespace Craft;

/**
 * Polls - Poll model
 */
class Polls_PollModel extends BaseModel
{
	// Properties
	// =========================================================================

	/**
	 * @var
	 */
	private $_locales;

	/**
	 * @var
	 */
	private $_questionTypes;

	/**
	 * @var
	 */
	private $_optionTypes;

	// Public Methods
	// =========================================================================

	/**
	 * Use the translated poll name as the string representation.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return Craft::t($this->name);
	}

	/**
	 * Returns the poll's locale models
	 *
	 * @return array
	 */
	public function getLocales()
	{
		if (!isset($this->_locales))
		{
			if ($this->id)
			{
				$this->_locales = craft()->polls->getPollLocales($this->id, 'locale');
			}
			else
			{
				$this->_locales = array();
			}
		}

		return $this->_locales;
	}

	/**
	 * Sets the poll's locale models.
	 *
	 * @param array $locales
	 *
	 * @return null
	 */
	public function setLocales($locales)
	{
		$this->_locales = $locales;
	}

	/**
	 * Adds locale-specific errors to the model.
	 *
	 * @param array  $errors
	 * @param string $localeId
	 *
	 * @return null
	 */
	public function addLocaleErrors($errors, $localeId)
	{
		foreach ($errors as $attribute => $localeErrors)
		{
			$key = $attribute.'-'.$localeId;
			foreach ($localeErrors as $error)
			{
				$this->addError($key, $error);
			}
		}
	}

	/**
	 * Returns the poll's question types.
	 *
	 * @param string|null $indexBy
	 *
	 * @return array
	 */
	public function getQuestionTypes($indexBy = null)
	{
		if (!isset($this->_questionTypes))
		{
			if ($this->id)
			{
				$this->_questionTypes = craft()->polls->getQuestionTypesByPollId($this->id);
			}
			else
			{
				$this->_questionTypes = array();
			}
		}

		if (!$indexBy)
		{
			return $this->_questionTypes;
		}
		else
		{
			$questionTypes = array();

			foreach ($this->_questionTypes as $questionType)
			{
				$questionTypes[$questionType->$indexBy] = $questionType;
			}

			return $questionTypes;
		}
	}

	/**
	 * Returns the poll's option types.
	 *
	 * @param string|null $indexBy
	 *
	 * @return array
	 */
	public function getOptionTypes($indexBy = null)
	{
		if (!isset($this->_optionTypes))
		{
			if ($this->id)
			{
				$this->_optionTypes = craft()->polls->getOptionTypesByPollId($this->id);
			}
			else
			{
				$this->_optionTypes = array();
			}
		}

		if (!$indexBy)
		{
			return $this->_optionTypes;
		}
		else
		{
			$optionTypes = array();

			foreach ($this->_optionTypes as $optionType)
			{
				$optionTypes[$optionType->$indexBy] = $optionType;
			}

			return $optionTypes;
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
		return array(
			'id'            						=> AttributeType::Number,
			'name'          						=> AttributeType::String,
			'handle'        						=> AttributeType::String,
		);
	}
}

<?php
namespace Craft;

class Polls_OptionTypeModel extends BaseModel
{
	// Public Methods
	// =========================================================================

	/**
	 * Use the handle as the string representation.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->id;
	}

	/**
	 * @return array
	 */
	public function behaviors()
	{
		return array(
			'fieldLayout' => new FieldLayoutBehavior(Polls_ElementType::Option),
		);
	}

	/**
	 * @inheritDoc BaseElementModel::getCpEditUrl()
	 *
	 * @return string
	 */
	public function getCpEditUrl()
	{
		return UrlHelper::getCpUrl('polls/'.$this->pollId.'/optiontypes/'.$this->id);
	}

	/**
	 * Returns the entry type’s section.
	 *
	 * @return SectionModel|null
	 */
	public function getPoll()
	{
		if ($this->pollId)
		{
			return craft()->polls->getPollById($this->pollId);
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
			'id'							=> AttributeType::Number,
			'pollId'					=> AttributeType::Number,
			'fieldLayoutId'		=> AttributeType::Number,
		);
	}
}

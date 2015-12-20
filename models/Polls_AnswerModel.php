<?php
namespace Craft;

/**
 * Polls - Answer model
 */
class Polls_AnswerModel extends BaseModel
{

	// Public Methods
	// =========================================================================

	public function getOption()
	{
		return craft()->polls_options->getOptionById($this->optionId);
	}

	public function getUser()
	{
		return craft()->users->getUserById($this->userId);
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
			'id' 								=> AttributeType::Number,
			'questionId'				=> AttributeType::Number,
			'optionId'					=> AttributeType::Number,
			'userId'						=> AttributeType::Number,
			'text'							=> AttributeType::String,
			'name'							=> AttributeType::String, // for guests
			'email'							=> AttributeType::Email,	// for guests
			'ipAddress'					=> AttributeType::String,
			'userAgent'					=> AttributeType::String,
			'dateCreated'   		=> AttributeType::DateTime,
			'dateUpdated'   		=> AttributeType::DateTime,
		);
	}
}
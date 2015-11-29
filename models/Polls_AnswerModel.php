<?php
namespace Craft;

/**
 * Polls - Answer model
 */
class Polls_AnswerModel extends BaseModel
{

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
			'optionId'					=> AttributeType::Number,
			'userId'						=> AttributeType::Number,
			'text'							=> AttributeType::String,
			'type'        			=> array(AttributeType::Enum, 'values' => array(Polls_AnswerType::Option, Polls_AnswerType::Text), 'default' => Polls_AnswerType::Option, 'required' => true),
			'name'							=> AttributeType::String, // for guests
			'email'							=> AttributeType::Email,	// for guests
			'ipAddress'					=> AttributeType::String,
			'userAgent'					=> AttributeType::String,
		);
	}
}
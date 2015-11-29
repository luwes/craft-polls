<?php
namespace Craft;


class Polls_PollLocaleModel extends BaseModel
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
			'id'               	=> AttributeType::Number,
			'pollId'        		=> AttributeType::Number,
			'locale'           	=> AttributeType::Locale,
			'enabledByDefault'	 => array(AttributeType::Bool, 'default' => true),
		);
	}
}

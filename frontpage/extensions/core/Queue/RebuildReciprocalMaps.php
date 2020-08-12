<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                      https://www.devcu.com/donate/
 *
 * @brief		Background Task: Rebuild database reciprocal maps
 * @author      Gary Cornell for devCU Software Open Source Projects
 * @copyright   (c) <a href='https://www.devcu.com'>devCU Software Development</a>
 * @license     GNU General Public License v3.0
 * @package     Invision Community Suite 4.4.10 FINAL
 * @subpackage	FrontPage
 * @version     1.0.5 Stable
 * @source      https://github.com/devCU/IPS-FrontPage
 * @Issue Trak  https://www.devcu.com/devcu-tracker/
 * @Created     25 APR 2019
 * @Updated     12 AUG 2020
 *
 *                    GNU General Public License v3.0
 *    This program is free software: you can redistribute it and/or modify       
 *    it under the terms of the GNU General Public License as published by       
 *    the Free Software Foundation, either version 3 of the License, or          
 *    (at your option) any later version.                                        
 *                                                                               
 *    This program is distributed in the hope that it will be useful,            
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of             
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *                                                                               
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see http://www.gnu.org/licenses/
 */

namespace IPS\frontpage\extensions\core\Queue;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Background Task: Rebuild database reciprocal maps
 */
class _RebuildReciprocalMaps
{
	/**
	 * @brief Number of content items to rebuild per cycle
	 */
	public $rebuild	= \IPS\REBUILD_QUICK;
	
	/**
	 * Parse data before queuing
	 *
	 * @param	array	$data
	 * @return	array
	 */
	public function preQueueData( $data )
	{
		$databaseId = $data['database'];
		$fieldId    = $data['field'];

		try
		{
			$data['count'] = (int) \IPS\Db::i()->select( 'COUNT(*)', 'frontpage_custom_database_' . $databaseId, array( 'field_' . $fieldId . ' != \'\' or field_' . $fieldId . ' IS NOT NULL' ) )->first();
		}
		catch( \Exception $ex )
		{
			throw new \OutOfRangeException;
		}
		
		if( $data['count'] == 0 )
		{
			return null;
		}
		
		return $data;
	}
	/**
	 * Run Background Task
	 *
	 * @param	mixed					$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int						$offset	Offset
	 * @return	int|null				New offset or NULL if complete
	 * @throws	\OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function run( $data, $offset )
	{
		$databaseId = $data['database'];
		$fieldId	= $data['field'];
		$parsed = 0;
		
		if ( \IPS\Db::i()->checkForTable( 'frontpage_custom_database_' . $databaseId ) )
		{
			$fieldsClass = 'IPS\frontpage\Fields' . $databaseId;
			$field = $fieldsClass::load( $fieldId );
		
			foreach ( \IPS\Db::i()->select( '*', 'frontpage_custom_database_' . $databaseId, array( 'field_' . $fieldId . ' != \'\' or field_' . $fieldId . ' IS NOT NULL' ), 'primary_id_field asc', array( $offset, $this->rebuild ) ) as $row )
			{
				$extra = $field->extra;
				if ( $row[ 'field_' . $fieldId ] and ! empty( $extra['database'] ) )
				{
					foreach( explode( ',', $row[ 'field_' . $fieldId ] ) as $foreignId )
					{
						if ( $foreignId )
						{
							\IPS\Db::i()->insert( 'frontpage_database_fields_reciprocal_map', array(
								'map_origin_database_id'	=> $databaseId,
								'map_foreign_database_id'   => $extra['database'],
								'map_origin_item_id'		=> $row['primary_id_field'],
								'map_foreign_item_id'		=> $foreignId,
								'map_field_id'				=> $fieldId
							) );
						}
					}
				}
				
				$parsed++;
			}
		}
		
		if ( ! $parsed )
		{
			throw new \IPS\Task\Queue\OutOfRangeException;
		}
		
		return $offset + $this->rebuild;
	}
	
	/**
	 * Get Progress
	 *
	 * @param	mixed					$data	Data as it was passed to \IPS\Task::queue()
	 * @param	int						$offset	Offset
	 * @return	array( 'text' => 'Doing something...', 'complete' => 50 )	Text explaining task and percentage complete
	 * @throws	\OutOfRangeException	Indicates offset doesn't exist and thus task is complete
	 */
	public function getProgress( $data, $offset )
	{
		$databaseId = $data['database'];
		return array( 'text' => \IPS\Member::loggedIn()->language()->addToStack('rebuilding_frontpage_database_reciprocal_map', FALSE, array( 'sprintf' => array( \IPS\frontpage\Databases::load( $databaseId )->_title, $data['field'] ) ) ), 'complete' => $data['count'] ? ( round( 100 / $data['count'] * $offset, 2 ) ) : 100 );
	}	
}
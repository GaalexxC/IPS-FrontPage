<?php
/**
 *     Support this Project... Keep it free! Become an Open Source Patron
 *                       https://www.devcu.com/donate
 *
 * @brief       FrontPage fpagebuilderupload Widget
 * @author      Gary Cornell for devCU Software Open Source Projects
 * @copyright   (c) <a href='https://www.devcu.com'>devCU Software Development</a>
 * @license     GNU General Public License v3.0
 * @package     Invision Community Suite 4.5x
 * @subpackage	FrontPage
 * @version     1.0.5 Stable
 * @source      https://github.com/devCU/IPS-FrontPage
 * @Issue Trak  https://www.devcu.com/devcu-tracker/
 * @Created     25 APR 2019
 * @Updated     15 OCT 2020
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

namespace IPS\frontpage\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * fpagebuilderupload Widget
 */
class _fpagebuilderupload extends \IPS\Widget\StaticCache implements \IPS\Widget\Builder
{
	/**
	 * @brief	Widget Key
	 */
	public $key = 'fpagebuilderupload';
	
	/**
	 * @brief	App
	 */
	public $app = 'frontpage';
		
	/**
	 * @brief	Plugin
	 */
	public $plugin = '';
	
	/**
	 * Specify widget configuration
	 *
	 * @param	null|\IPS\Helpers\Form	$form	Form object
	 * @return	null|\IPS\Helpers\Form
	 */
	public function configuration( &$form=null )
	{
 		$form = parent::configuration( $form );
 		
 		$images = array();
 		$captions = array();
 		$urls = array();
 		
 		if ( ! empty( $this->configuration['fpagebuilderupload_upload'] ) )
 		{
	 		foreach( explode( ',', $this->configuration['fpagebuilderupload_upload'] ) as $img )
			{
				$images[] = \IPS\File::get( 'core_Attachment', $img );
			}
 		}
 		
 		if ( ! empty( $this->configuration['fpagebuilderupload_captions'] ) )
 		{
	 		foreach( $this->configuration['fpagebuilderupload_captions'] as $caption )
			{
				$captions[] = $caption;
			}
 		}
 		
 		if ( ! empty( $this->configuration['fpagebuilderupload_urls'] ) )
 		{
	 		foreach( json_decode( $this->configuration['fpagebuilderupload_urls'], TRUE ) as $url )
			{
				$urls[] = $url;
			}
 		}
 		
 		$form->add( new \IPS\Helpers\Form\Upload( 'fpagebuilderupload_upload', $images, FALSE, array( 'multiple' => true, 'storageExtension' => 'core_Attachment', 'allowStockPhotos' => TRUE, 'image' => true ) ) );
 		$form->add( new \IPS\Helpers\Form\YesNo( 'fpagebuilderupload_slideshow', ( isset( $this->configuration['fpagebuilderupload_slideshow'] ) ? $this->configuration['fpagebuilderupload_slideshow'] : FALSE ) ) );
 		$form->add( new \IPS\Helpers\Form\Stack( 'fpagebuilderupload_captions', $captions, FALSE, array( 'stackFieldType' => 'Text' ) ) );
		$form->add( new \IPS\Helpers\Form\Stack( 'fpagebuilderupload_urls', $urls, FALSE, array( 'stackFieldType' => 'Url' ) ) );
		
		$form->add( new \IPS\Helpers\Form\Number( 'fpagebuilderupload_height', ( isset( $this->configuration['fpagebuilderupload_height'] ) ? $this->configuration['fpagebuilderupload_height'] : 300 ), FALSE, array( 'unlimited' => 0 ) ) );
 		return $form;
 	}

	/**
	 * Before the widget is removed, we can do some clean up
	 *
	 * @return void
	 */
	public function delete()
	{
		foreach( explode( ',', $this->configuration['fpagebuilderupload_upload'] ) as $img )
		{
			try
			{
				\IPS\File::get( 'core_Attachment', $img )->delete();
			}
			catch( \Exception $e ) { }
		}
	}
 	
 	 /**
 	 * Ran before saving widget configuration
 	 *
 	 * @param	array	$values	Values from form
 	 * @return	array
 	 */
 	public function preConfig( $values )
 	{
	 	$images = array();
	 	$urls = array();
	 	
	 	foreach( $values['fpagebuilderupload_upload'] as $img )
	 	{
		 	$images[] = (string) $img;
	 	}
	 	
	 	foreach( $values['fpagebuilderupload_urls'] as $url )
	 	{
		 	$urls[] = (string) $url;
	 	}
	 	
	 	$values['fpagebuilderupload_upload'] = implode( ',', $images );
	 	$values['fpagebuilderupload_urls'] = json_encode( $urls );
 		return $values;
 	}

	/**
	 * Render a widget
	 *
	 * @return	string
	 */ 
	public function render()
	{
		$images = array();
		$captions = ( isset( $this->configuration['fpagebuilderupload_captions'] ) ) ? $this->configuration['fpagebuilderupload_captions'] : array();
		$urls = ( isset( $this->configuration['fpagebuilderupload_urls'] ) ) ? json_decode( $this->configuration['fpagebuilderupload_urls'], TRUE ) : array();
		$autoPlay = ( isset( $this->configuration['fpagebuilderupload_slideshow'] ) ) ? $this->configuration['fpagebuilderupload_slideshow'] : FALSE;
		$maxHeight = ( isset( $this->configuration['fpagebuilderupload_height'] ) ) ? $this->configuration['fpagebuilderupload_height'] : FALSE;
		
		if ( isset( $this->configuration['fpagebuilderupload_upload'] ) )
		{
			foreach( explode( ',', $this->configuration['fpagebuilderupload_upload'] ) as $img )
			{
				$images[] = (string) \IPS\File::get( 'core_Attachment', $img )->url;
			}

			return $this->output( ( \count( $images ) === 1 ? $images[0] : $images ), $captions, $urls, $autoPlay, $maxHeight );
		}
		
		return '';
	}
}
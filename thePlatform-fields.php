<?php

/* thePlatform Video Manager Wordpress Plugin
  Copyright (C) 2013-2014  thePlatform for Media Inc.

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License along
  with this program; if not, write to the Free Software Foundation, Inc.,
  51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA. */

/**
 * Not in use at the moment
 */
class ThePlatform_FieldTypes {

	public function write( $type, $structure, $value ) {
		switch ( strtolower( $type ) ) {
			case 'string':
				writeString( $value, $structure );
				break;
			case 'boolean':
				writeBoolean( $value, $structure );
				break;
			case 'date':
				writeDate( $value, $structure );
				break;
			case 'link':
				writeLink( $value, $structure );
				break;
			case 'uri':
				writeURI( $value, $structure );
				break;
			case 'datetime':
				writeDateTime( $value, $structure );
				break;
			case 'datetime':
				writeTime( $value, $structure );
				break;
			case 'integer':
			case 'decimal':
				writeNumber( $value, $structure );
				break;

			default:
				# code...
				break;
		}
	}

	private function writeString( $value, $structure ) {
		$html = '<textarea class="' . $structure . '" rows="1"></textarea>';
		return $html;
	}

	private function writeDateTime( $value, $structure ) {
		$html = '<input class="' . $structure . '" type="datetime-local"></input>';
		return $html;
	}

	private function writeDate( $value, $structure ) {
		$html = '<input class="' . $structure . '" type="date"></input>';
		return $html;
	}

	private function writeBoolean( $value, $structure ) {
		$html = '<select class="form-control"><option>Yes</option><option>No</option><option>Unset</option></select>';
		return $html;
	}

	private function writeURI( $value, $structure ) {
		$html = '<input class="' . $structure . '" type="url"></input>';
		return $html;
	}

	private function writeNumber( $value, $structure ) {
		$html = '<input class="' . $structure . '" type="number"></input>';
		return $html;
	}

	private function writeTime( $value, $structure ) {
		$html = '<input class="' . $structure . '" type="time"></input>';
		return $html;
	}

	private function writeLink( $value, $structure ) {
		$html = '<input type="text"></input><input type="url"></input>';
		return $html;
	}
}
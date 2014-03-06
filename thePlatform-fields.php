<?php 

/**
 * Not in use at the moment
 */

class ThePlatform_FieldTypes {

	public function write($type, $structure, $value)
	{
		switch (strtolower($type)) {
			case 'string':
				writeString($value, $structure);
				break;
			case 'boolean':
				writeBoolean($value, $structure);
				break;
			case 'date':
				writeDate($value, $structure);
				break;
			case 'link':
				writeLink($value, $structure);
				break;
			case 'uri':
				writeURI($value, $structure);
				break;
			case 'datetime':
				writeDateTime($value, $structure);
				break;
			case 'datetime':
				writeTime($value, $structure);
				break;
			case 'integer':
			case 'decimal':
				writeNumber($value, $structure);
				break;
			
			default:
				# code...
				break;
		}
	}

	private function writeString($value, $structure)
	{
		$html = '<textarea class="' . $structure . '" rows="1"></textarea>';
		return $html;
	}

	private function writeDateTime($value, $structure)
	{
		$html = '<input class="' . $structure . '" type="datetime-local"></input>';
		return $html;
	}	

	private function writeDate($value, $structure)
	{
		$html = '<input class="' . $structure . '" type="date"></input>';
		return $html;
	}	

	private function writeBoolean($value, $structure)
	{
		$html = '<select class="form-control"><option>Yes</option><option>No</option><option>Unset</option></select>';
		return $html;
	}	

	private function writeURI($value, $structure)
	{
		$html = '<input class="' . $structure . '" type="url"></input>';
		return $html;
	}	

	private function writeNumber($value, $structure)
	{
		$html = '<input class="' . $structure . '" type="number"></input>';
		return $html;
	}	

	private function writeTime($value, $structure)
	{
		$html = '<input class="' . $structure . '" type="time"></input>';
		return $html;
	}

	private function writeLink($value, $structure)
	{
		$html = '<input type="text"></input><input type="url"></input>';
		return $html;
	}	
}

?>


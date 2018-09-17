<?php
/**
 * @package SNIPF
 * @copyright Copyright (c) 2018 - 2018 Lucas Sanner
 * @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die; //No direct access to this file.
//JLoader::register('JFolder', JPATH_LIBRARIES . '/joomla/filesystem/folder.php');
//Uses include_once instead of require_once to prevent a fatal error in case the tcpdf
//librarie is not installed.
include_once(JPATH_LIBRARIES.'/tcpdf/tcpdf.php');



class TcpdfHelper
{

  public static function generatePDF($data, $templateName, $options = array())
  {
    //Gets the html template from the option parameters.
    $htmlTemplate = JComponentHelper::getParams('com_snipf')->get($templateName);
    $htmls = self::bindData($data, $htmlTemplate);

    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    // set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('SNIPF');
    $pdf->SetTitle('Résultat PDF');

    // set default header data
    //$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 006', PDF_HEADER_STRING);

    // set header and footer fonts
    $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

    // set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

    // set margins and auto page breaks
    if(isset($options['margins'])) {
      $pdf->SetMargins($options['margins']['left'], $options['margins']['top'],
		       $options['margins']['right'],$options['margins']['bottom']);
      $pdf->SetAutoPageBreak(TRUE, $options['margins']['bottom']);
    }
    else {
      $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
      $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    }

    // remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    if(isset($options['font_size'])) {
      $pdf->SetFont('dejavusans', '', $options['font_size']);
    }
    else {
      $pdf->SetFont('dejavusans', '', 10);
    }

    // set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    //Generates a pdf page for each set of data.
    foreach($htmls as $html) {
      if(isset($options['format'])) {
	$pdf->AddPage($options['format']['orientation'], $options['format']['type']);
      }
      else {
	$pdf->AddPage();
      }

      $pdf->writeHTML($html, true, false, true, false, '');
    }

    $pdf->lastPage();

    ob_end_clean();
    $pdf->Output('htmlout.pdf', 'I');
  }


  //Binds each set of data to the given html template.
  public static function bindData($data, $htmlTemplate)
  {
    $htmls = $patterns = array();
    //Extracts variable names from the html template.
    //Note: Variables are set within square brackets (eg: [VARIABLE_NAME])
    preg_match_all('#\[[A-Z0-9_]+\]#', $htmlTemplate, $matches);

    //Sets up a regex pattern for each variable name.
    foreach($matches[0] as $match) {
      //Puts an escape character before the closing square bracket. 
      $match = preg_replace('#\]$#', '\]', $match);
      $patterns[] = '#\\'.$match.'#';
    }

    //Loops through the data sets.
    foreach($data as $item) {
      $replacements = array();
      //Sets the corresponding value for each variable name.
      foreach($patterns as $key => $pattern) {
	//Extracts the variable name from the pattern.
	preg_match('#\[([A-Z-0-9_]+)#', $pattern, $matches);
	//Variable names are set with upper case characters in the html template.
	$attrName = strtolower($matches[1]);

	if(preg_match('#^additional_address#', $attrName) && !empty($item->$attrName)) {
	  //In case of additional address the value is displayed in a new line.
	  $item->$attrName = '<br />'.$item->$attrName;
	}

	$replacements[] = $item->$attrName;
      }

      //Gets the html code with the variable values properly set.
      $html = preg_replace($patterns, $replacements, $htmlTemplate);

      //In case of multi pages the html block has to be hashed in several parts.
      while(preg_match('#^(.+)(<br \s*class="pagebreak" \s*/>)#sU', $html, $matches)) {
	//Stores the fetched html part (without the <br> tag).
	$htmls[] = $matches[1];
	//Removes that part from the main html block.
	$html = preg_replace('#^(.+)(<br \s*class="pagebreak" \s*/>)#sU', '', $html);
      }

      if(!empty($html)) {
	//Stores the remaining html part or the whole html block if no multi pages tags
	//have been found.
	$htmls[] = $html;
      }
    }

    return $htmls;
  }
}


